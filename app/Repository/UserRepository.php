<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\User;

class UserRepository
{
    public function findById(int $id): ?array
    {
        $user = User::find($id);
        return $user ? $user->toArray() : null;
    }

    public function findByPhone(string $phoneE164): ?array
    {
        $user = User::where('phone_e164', $phoneE164)->first();
        return $user ? $user->toArray() : null;
    }

    public function create(array $data): int
    {
        $user = User::create($data);
        return $user->id;
    }

    public function update(int $id, array $data): bool
    {
        $user = User::find($id);
        if (!$user) {
            return false;
        }
        
        return $user->update($data);
    }

    public function delete(int $id): bool
    {
        $user = User::find($id);
        if (!$user) {
            return false;
        }
        
        return $user->delete();
    }
}
