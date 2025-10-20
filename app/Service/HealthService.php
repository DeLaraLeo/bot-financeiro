<?php

declare(strict_types=1);

namespace App\Service;

class HealthService
{
    public function __construct() {}

    public function checkHealth(): array
    {
        return [
            'status' => 'ok',
            'timestamp' => date('c'),
            'message' => 'Bot Financeiro API is running'
        ];
    }
}
