<?php

declare(strict_types=1);

namespace App\Repository;

use Hyperf\DbConnection\Db;

class UserRepository
{
    public function findById(int $id): ?array
    {
        $result = Db::table('users')->where('id', $id)->first();
        return $result ? (array) $result : null;
    }

    public function findByPhone(string $phoneE164): ?array
    {
        $result = Db::table('users')->where('phone_e164', $phoneE164)->first();
        return $result ? (array) $result : null;
    }

    public function create(array $data): int
    {
        return Db::table('users')->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        return Db::table('users')->where('id', $id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return Db::table('users')->where('id', $id)->delete() > 0;
    }
}
