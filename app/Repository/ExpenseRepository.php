<?php

declare(strict_types=1);

namespace App\Repository;

use Hyperf\DbConnection\Db;

class ExpenseRepository
{
    public function findById(int $id): ?array
    {
        $result = Db::table('expenses')
            ->join('users', 'expenses.user_id', '=', 'users.id')
            ->leftJoin('categories', 'expenses.category_id', '=', 'categories.id')
            ->select([
                'expenses.*',
                'users.name as user_name',
                'users.phone_e164',
                'categories.name as category_name',
                'categories.code as category_code'
            ])
            ->where('expenses.id', $id)
            ->first();
            
        return $result ? (array) $result : null;
    }

    public function create(array $data): int
    {
        return Db::table('expenses')->insertGetId($data);
    }

    public function findByUser(int $userId, ?string $from = null, ?string $to = null, ?int $categoryId = null): array
    {
        $query = Db::table('expenses')
            ->leftJoin('categories', 'expenses.category_id', '=', 'categories.id')
            ->select([
                'expenses.*',
                'categories.name as category_name',
                'categories.code as category_code'
            ])
            ->where('expenses.user_id', $userId);

        if ($from) {
            $query->where('expenses.occurred_at', '>=', $from);
        }

        if ($to) {
            $query->where('expenses.occurred_at', '<=', $to);
        }

        if ($categoryId) {
            $query->where('expenses.category_id', $categoryId);
        }

        return $query->orderBy('expenses.occurred_at', 'desc')->get()->toArray();
    }

    public function update(int $id, array $data): bool
    {
        return Db::table('expenses')->where('id', $id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return Db::table('expenses')->where('id', $id)->delete() > 0;
    }
}
