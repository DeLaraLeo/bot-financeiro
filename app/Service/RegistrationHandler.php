<?php

declare(strict_types=1);

namespace App\Service;

class RegistrationHandler
{
    public function __construct(
        private UserService $userService,
        private AIService $aiService,
        private ConversationStateManager $stateManager
    ) {}

    public function handle(string $phoneE164, string $messageBody): array
    {
        if ($this->stateManager->isAwaitingName($phoneE164)) {
            return $this->handleNameResponse($phoneE164, $messageBody);
        }

        $this->startRegistration($phoneE164);
        return [
            'message' => "Olá! Para começar, me diga seu nome:",
            'shouldSend' => true
        ];
    }

    private function handleNameResponse(string $phoneE164, string $messageBody): array
    {
        $nameResult = $this->aiService->processNameMessage($messageBody);
        
        if (!$nameResult['isName']) {
            return [
                'message' => "Desculpe, não consegui identificar um nome. Por favor, me diga apenas seu nome.",
                'shouldSend' => true
            ];
        }
        
        $name = $nameResult['name'];
        $user = $this->userService->createByPhone($phoneE164, $name);
        
        $this->stateManager->setIdle($phoneE164);
        return [
            'message' => "Bem-vindo, {$name}! 🎉\n\nAgora você pode:\n• Registrar despesas: 'Gastei 25,50 no mercado'\n• Consultar despesas: 'quanto gastei este mês?'",
            'shouldSend' => true,
            'user' => $user
        ];
    }

    private function startRegistration(string $phoneE164): void
    {
        $this->stateManager->setAwaitingName($phoneE164, [
            'first_message_at' => time()
        ]);
    }
}
