<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use App\Service\ConversationService;

#[Consumer(exchange: 'message', routingKey: 'receive', queue: 'q.message.receive', nums: 1)]
class MessageReceiveConsumer extends ConsumerMessage
{
    public function __construct(
        private ConversationService $conversationService
    ) {}

    public function consumeMessage($data, \PhpAmqpLib\Message\AMQPMessage $message): Result
    {
        try {
            if (!$this->validateMessage($data)) {
                error_log("Message validation failed: " . json_encode($data));
                return Result::ACK;
            }
            
            error_log("Processing message: " . json_encode($data));
            $this->conversationService->processIncomingMessage($data);
            return Result::ACK;

        } catch (\Exception $e) {
            error_log("Error processing message: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return Result::ACK;
        }
    }

    private function validateMessage(array $data): bool
    {
        $required = ['message_type', 'message_id', 'sender_number', 'message_body', 'transaction_id'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        return $data['message_type'] === 'text';
    }
}
