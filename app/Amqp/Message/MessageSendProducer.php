<?php

declare(strict_types=1);

namespace App\Amqp\Message;

use Hyperf\Amqp\Message\ProducerMessage;

class MessageSendProducer extends ProducerMessage
{
    public function __construct($data)
    {
        $this->payload = $data;
    }

    public function setPayload($data): static
    {
        $this->payload = $data;
        return $this;
    }

    public function getExchange(): string
    {
        return 'whatsapp.send';
    }

    public function getRoutingKey(): string
    {
        return 'send';
    }
}
