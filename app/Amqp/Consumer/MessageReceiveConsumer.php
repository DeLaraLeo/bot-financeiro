<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use App\Service\ConversationService;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

#[Consumer(exchange: 'message', routingKey: 'receive', queue: 'q.message.receive', nums: 1)]
class MessageReceiveConsumer extends ConsumerMessage
{
    public function __construct(
        private ConversationService $conversationService,
        private LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get('message-receive');
    }

    private LoggerInterface $logger;

    public function consumeMessage($data, \PhpAmqpLib\Message\AMQPMessage $message): string
    {
        try {
            $this->logger->info('Received message', ['data' => $data]);

            // Validate message structure
            if (!$this->validateMessage($data)) {
                $this->logger->error('Invalid message structure', ['data' => $data]);
                return Result::REJECT;
            }

            // Process message through conversation service
            $this->conversationService->processIncomingMessage($data);

            $this->logger->info('Message processed successfully');
            return Result::ACK;

        } catch (\Exception $e) {
            $this->logger->error('Error processing message', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return Result::REJECT;
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
