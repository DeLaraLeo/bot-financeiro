<?php

declare(strict_types=1);

namespace App\Service;

use Hyperf\Redis\Redis;

class ConversationStateManager
{
    private const TTL_SECONDS = 1800;
    private const STATE_IDLE = 'idle';
    private const STATE_AWAITING_NAME = 'awaiting_name';
    private const STATE_GREETING_SENT = 'greeting_sent';

    public function __construct(
        private Redis $redis
    ) {}

    public function getState(string $phoneE164): array
    {
        $key = $this->getStateKey($phoneE164);
        $state = $this->redis->get($key);
        
        if (!$state) {
            return [
                'state' => self::STATE_IDLE,
                'context' => []
            ];
        }

        $decoded = json_decode($state, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'state' => self::STATE_IDLE,
                'context' => []
            ];
        }

        return $decoded;
    }

    public function setState(string $phoneE164, string $state, array $context = []): void
    {
        $key = $this->getStateKey($phoneE164);
        $stateData = [
            'state' => $state,
            'context' => $context
        ];
        
        $this->redis->setex($key, self::TTL_SECONDS, json_encode($stateData));
    }

    public function clearState(string $phoneE164): void
    {
        $key = $this->getStateKey($phoneE164);
        $this->redis->del($key);
    }

    public function isInState(string $phoneE164, string $state): bool
    {
        $currentState = $this->getState($phoneE164);
        return $currentState['state'] === $state;
    }

    public function isAwaitingName(string $phoneE164): bool
    {
        return $this->isInState($phoneE164, self::STATE_AWAITING_NAME);
    }

    public function setIdle(string $phoneE164): void
    {
        $this->setState($phoneE164, self::STATE_IDLE);
    }

    public function setAwaitingName(string $phoneE164, array $context = []): void
    {
        $this->setState($phoneE164, self::STATE_AWAITING_NAME, $context);
    }

    public function setGreetingSent(string $phoneE164): void
    {
        $this->setState($phoneE164, self::STATE_GREETING_SENT, [
            'greeting_sent_at' => time()
        ]);
    }

    public function isGreetingSent(string $phoneE164): bool
    {
        return $this->isInState($phoneE164, self::STATE_GREETING_SENT);
    }

    private function getStateKey(string $phoneE164): string
    {
        return "convo:{$phoneE164}:state";
    }
}
