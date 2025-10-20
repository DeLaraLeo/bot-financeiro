<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;
use Hyperf\DbConnection\Db;

class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function upsertByPhone(string $phoneE164, string $name): array
    {
        $user = $this->userRepository->findByPhone($phoneE164);
        
        if ($user) {
            // Update existing user
            $this->userRepository->update($user['id'], ['name' => $name]);
            return $user;
        }

        // Create new user
        $userId = $this->userRepository->create([
            'phone_e164' => $phoneE164,
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->userRepository->findById($userId);
    }

    public function findByPhone(string $phoneE164): ?array
    {
        return $this->userRepository->findByPhone($phoneE164);
    }
}
