<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;
use Carbon\Carbon;

class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function createByPhone(string $phoneE164, string $name): array
    {
        $userId = $this->userRepository->create([
            'phone_e164' => $phoneE164,
            'name' => $name,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return $this->userRepository->findById($userId);
    }

    public function findByPhone(string $phoneE164): ?array
    {
        return $this->userRepository->findByPhone($phoneE164);
    }
}
