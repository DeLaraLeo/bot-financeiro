<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use App\Service\HealthService;

class HealthController
{
    public function __construct(
        private HealthService $healthService
    ) {}

    public function index(RequestInterface $request, ResponseInterface $response)
    {
        $status = $this->healthService->checkHealth();
        return $response->json($status);
    }
}
