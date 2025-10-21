<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\UserService;
use App\Service\ExpenseService;
use App\Service\AIService;
use App\Service\ConversationStateManager;
use App\Service\RegistrationHandler;
use Hyperf\Amqp\Producer;
use App\Amqp\Message\MessageSendProducer;
use Hyperf\Contract\StdoutLoggerInterface;

class ConversationService
{
    public function __construct(
        private UserService $userService,
        private ExpenseService $expenseService,
        private AIService $aiService,
        private ConversationStateManager $stateManager,
        private RegistrationHandler $registrationHandler,
        private Producer $producer,
        private StdoutLoggerInterface $logger
    ) {}

    public function processIncomingMessage(array $messageData): void
    {
        if (!isset($messageData['sender_number']) || !isset($messageData['message_body']) || !isset($messageData['message_id'])) {
            $this->logger->warning("Invalid message data structure", ['data' => $messageData]);
            return;
        }

        $phoneE164 = $messageData['sender_number'];
        $messageBody = $messageData['message_body'];
        $messageId = $messageData['message_id'];

        $this->logger->info("Processing message", ['phone' => $phoneE164, 'body' => $messageBody]);

        $user = $this->userService->findByPhone($phoneE164);
        
        if (!$user) {
            $this->logger->info("User not found, starting registration", ['phone' => $phoneE164]);
            $result = $this->registrationHandler->handle($phoneE164, $messageBody);
            $this->sendMessage($phoneE164, $result['message'], $messageId);
            return;
        }
        
        $this->logger->info("User found", ['user_id' => $user['id']]);
        $currentState = $this->stateManager->getState($phoneE164);
        $aiResult = $this->aiService->classifyMessage($messageBody);
        $this->logger->info("AI result", ['intent' => $aiResult['intent'] ?? 'unknown']);
        $response = $this->processAIResult($user, $aiResult, $currentState, $phoneE164, $messageBody);
        $this->logger->info("Response generated", ['response' => $response]);
        $this->sendMessage($phoneE164, $response, $messageId);
    }

    private function processAIResult(array $user, array $aiResult, array $state, string $phoneE164, string $messageBody): string
    {
        return match ($aiResult['intent']) {
            'expense_registration' => $this->processExpenseRegistration($user, $aiResult, $state, $phoneE164),
            'query_expenses' => $this->processExpenseQuery($user, $aiResult['data'] ?? []),
            'greeting' => $this->handleGreeting($user, $state, $phoneE164, $messageBody, $aiResult),
            default => $this->handleGreeting($user, $state, $phoneE164, $messageBody, $aiResult),
        };
    }

    private function handleGreeting(array $user, array $state, string $phoneE164, string $messageBody, array $aiResult = []): string
    {
        $data = $aiResult['data'] ?? [];
        
        if (isset($data['is_future_period']) && $data['is_future_period'] === true) {
            return "Não é possível consultar despesas futuras. Você pode consultar períodos passados ou o período atual.\n\nExemplos:\n• 'quanto gastei hoje?'\n• 'quanto gastei nos últimos 10 dias?'\n• 'quanto gastei semana passada?'\n• 'quanto gastei este mês?'";
        }
        
        if ($this->stateManager->isGreetingSent($phoneE164)) {
            return "Não consegui entender sua mensagem. Por favor, envie uma despesa completa (ex: 'gastei 25,50 no mercado') ou faça uma consulta (ex: 'quanto gastei este mês?')";
        }
        
        $this->stateManager->setGreetingSent($phoneE164);
        return $this->generateGreetingResponse($user['name'] ?? '');
    }

    private function generateGreetingResponse(string $name): string
    {
        return "Olá, {$name}! Sou seu assistente financeiro. O que gostaria de fazer?\n\n" .
               "Você pode:\n" .
               "• 📝 Registrar despesas: 'gastei 25,50 no mercado'\n" .
               "• 💰 Consultar despesas: 'quanto gastei este mês?'\n" .
               "• ⚠️ Registre apenas uma despesa por mensagem.";
    }

    private function processExpenseRegistration(array $user, array $aiResult, array $state, string $phoneE164): string
    {
        if (isset($aiResult['error'])) {
            return $this->getExpenseErrorResponse($phoneE164);
        }

        $data = $aiResult['data'] ?? [];
        
        if (empty($data['amount_cents']) || $data['amount_cents'] <= 0) {
            return $this->getExpenseErrorResponse($phoneE164);
        }

        if (empty($data['description'])) {
            return $this->getExpenseErrorResponse($phoneE164);
        }
        
        $categoryId = null;
        if (!empty($data['category_hint'])) {
            $category = $this->expenseService->findCategoryByCode($data['category_hint']);
            $categoryId = $category['id'] ?? null;
        }
        
        $expenseData = [
            'user_id' => $user['id'],
            'amount_cents' => $data['amount_cents'],
            'description' => $data['description'],
            'category_id' => $categoryId,
        ];

        $this->expenseService->createExpense($expenseData);
        $this->stateManager->setIdle($phoneE164);

        return "Despesa registrada com sucesso! 💰";
    }

    private function getExpenseErrorResponse(string $phoneE164): string
    {
        if ($this->stateManager->isGreetingSent($phoneE164)) {
            return "Não consegui identificar a despesa completa. Por favor, envie novamente informando o valor e a descrição.\n\nExemplo: 'gastei 25,50 no mercado'";
        }
        
        $this->stateManager->setGreetingSent($phoneE164);
        return $this->generateGreetingResponse($this->userService->findByPhone($phoneE164)['name'] ?? '');
    }

    private function processExpenseQuery(array $user, array $queryData): string
    {
        $startDate = $queryData['start_date'] ?? null;
        $endDate = $queryData['end_date'] ?? null;
        $period = $queryData['period'] ?? 'período';
        $categoryFilter = $queryData['category_filter'] ?? null;
        
        if ($categoryFilter === 'null' || $categoryFilter === '') {
            $categoryFilter = null;
        }
        
        $todayEnd = date('Y-m-d 23:59:59');
        $todayDateOnly = date('Y-m-d');
        $lastWeekEnd = date('Y-m-d 23:59:59', strtotime('sunday last week'));
        $lastWeekStart = date('Y-m-d 00:00:00', strtotime('monday last week'));
        
        if ($endDate) {
            $endDateOnly = date('Y-m-d', strtotime($endDate));
            
            if (stripos($period, 'semana passada') !== false) {
                if (strtotime($endDateOnly) > strtotime(date('Y-m-d', strtotime('sunday last week')))) {
                    $endDate = $lastWeekEnd;
                    $startDate = $lastWeekStart;
                }
            } elseif (strtotime($endDateOnly) > strtotime($todayDateOnly)) {
                $endDate = $todayEnd;
            }
        }
        
        $summary = $this->expenseService->getExpensesSummaryByCategory($user['id'], $startDate, $endDate, $categoryFilter);
        
        $dateRange = '';
        if ($startDate && $endDate) {
            $startFormatted = date('d/m/Y', strtotime($startDate));
            $endFormatted = date('d/m/Y', strtotime($endDate));
            $dateRange = " ({$startFormatted} a {$endFormatted})";
        }
        
        $response = "📊 Resumo das suas despesas ({$period}){$dateRange}:\n\n";
        
        $total = 0;
        foreach ($summary as $category) {
            $amount = number_format($category['total_brl'], 2, ',', '.');
            $response .= "• {$category['category_name']}: R$ {$amount}\n";
            $total += $category['total_brl'];
        }
        
        $response .= "\n💰 Total: R$ " . number_format($total, 2, ',', '.');
        
        return $response;
    }

    private function sendMessage(string $phoneE164, string $messageBody, ?string $quotedMessageId = null): void
    {
        $message = new MessageSendProducer([
            'message_type' => 'text',
            'recipient_number' => $phoneE164,
            'message_body' => $messageBody,
            'quoted_message_id' => $quotedMessageId,
            'transaction_id' => uniqid(),
        ]);

        try {
            $this->logger->info("Sending message", ['phone' => $phoneE164, 'body' => $messageBody]);
            $this->producer->produce($message);
            $this->logger->info("Message sent successfully");
        } catch (\Exception $e) {
            $this->logger->error("Failed to send message: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }
}
