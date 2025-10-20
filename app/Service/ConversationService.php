<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\UserService;
use App\Service\ExpenseService;
use App\Service\AIService;
use Hyperf\Redis\Redis;
use Hyperf\Amqp\Producer;
use App\Amqp\Message\MessageSendProducer;

class ConversationService
{
    public function __construct(
        private UserService $userService,
        private ExpenseService $expenseService,
        private AIService $aiService,
        private Redis $redis,
        private Producer $producer
    ) {}

    public function processIncomingMessage(array $messageData): void
    {
        $phoneE164 = $messageData['sender_number'];
        $messageBody = $messageData['message_body'];
        $messageId = $messageData['message_id'];

        // Get or create user
        $user = $this->userService->upsertByPhone($phoneE164, 'Usuário'); // Default name

        // Get conversation state
        $state = $this->getConversationState($phoneE164);

        // Send message to AI for classification
        $aiResult = $this->aiService->classifyMessage($messageBody);

        // Process based on AI result
        $response = $this->processAIResult($user, $aiResult, $state);

        // Update conversation state
        $this->updateConversationState($phoneE164, $state);

        // Send response
        $this->sendMessage($phoneE164, $response, $messageId);
    }

    private function getConversationState(string $phoneE164): array
    {
        $key = "convo:{$phoneE164}:state";
        $state = $this->redis->get($key);
        
        if (!$state) {
            return ['state' => 'idle', 'context' => []];
        }

        return json_decode($state, true);
    }

    private function updateConversationState(string $phoneE164, array $state): void
    {
        $key = "convo:{$phoneE164}:state";
        $this->redis->setex($key, 1200, json_encode($state)); // 20 minutes TTL
    }

    private function processAIResult(array $user, array $aiResult, array $state): string
    {
        switch ($aiResult['intent']) {
            case 'expense_registration':
                return $this->processExpenseRegistration($user, $aiResult, $state);
                
            case 'query_expenses':
                return $this->processExpenseQuery($user);
                
            case 'list_categories':
                return $this->listCategories();
                
            case 'greeting':
            default:
                return $this->aiService->generateResponse($aiResult);
        }
    }

    private function processExpenseRegistration(array $user, array $aiResult, array $state): string
    {
        if (isset($aiResult['error'])) {
            return $this->aiService->generateResponse($aiResult);
        }

        $data = $aiResult['data'];
        
        // Try to find category by hint
        $categoryId = null;
        if ($data['category_hint']) {
            $category = $this->expenseService->findCategoryByCode($data['category_hint']);
            $categoryId = $category['id'] ?? null;
        }

        // Create expense
        $expenseData = [
            'user_id' => $user['id'],
            'amount_cents' => $data['amount_cents'],
            'description' => $data['description'],
            'category_id' => $categoryId,
            'source_message_id' => uniqid(),
        ];

        $this->expenseService->createExpense($expenseData);

        return $this->aiService->generateResponse($aiResult);
    }

    private function processExpenseQuery(array $user): string
    {
        $summary = $this->expenseService->getExpensesSummary($user['id']);
        
        return "📊 Resumo dos seus gastos:\n" .
               "Total: R$ " . number_format($summary['total_brl'], 2, ',', '.') . "\n" .
               "Quantidade: {$summary['count']} gastos";
    }

    private function listCategories(): string
    {
        $categories = $this->expenseService->getAllCategories();
        $list = "📋 Categorias disponíveis:\n";
        
        foreach ($categories as $category) {
            $list .= "• {$category['name']} ({$category['code']})\n";
        }

        return $list;
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

        $this->producer->produce($message);
    }
}
