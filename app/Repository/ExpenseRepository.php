<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Expense;
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
        $expense = Expense::create($data);
        return $expense->id;
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
        $expense = Expense::find($id);
        if (!$expense) {
            return false;
        }
        
        return $expense->update($data);
    }

    public function delete(int $id): bool
    {
        $expense = Expense::find($id);
        if (!$expense) {
            return false;
        }
        
        return $expense->delete();
    }

    public function getSummaryByCategory(int $userId, ?string $from = null, ?string $to = null, ?string $categoryFilter = null): array
    {
        $query = Db::table('expenses')
            ->leftJoin('categories', 'expenses.category_id', '=', 'categories.id')
            ->select([
                'categories.name as category_name',
                'categories.code as category_code',
                Db::raw('SUM(expenses.amount_cents) as total_cents'),
                Db::raw('COUNT(*) as count')
            ])
            ->where('expenses.user_id', $userId)
            ->whereNotNull('expenses.category_id')
            ->groupBy('categories.id', 'categories.name', 'categories.code');

        if ($from) {
            $query->where('expenses.occurred_at', '>=', $from);
        }

        if ($to) {
            $query->where('expenses.occurred_at', '<=', $to);
        }

        if ($categoryFilter) {
            if (is_numeric($categoryFilter)) {
                $query->where('categories.id', $categoryFilter);
            } else {
                $query->where('categories.code', 'like', '%' . strtolower($categoryFilter) . '%');
            }
        }

        $results = $query->get()->toArray();
        
        $summary = [];
        foreach ($results as $result) {
            $resultArray = (array) $result;
            
            if (empty($resultArray['category_name'])) {
                continue;
            }
            
            $totalCents = (int) ($resultArray['total_cents'] ?? 0);
            if ($totalCents <= 0) {
                continue;
            }
            
            $summary[] = [
                'category_name' => $resultArray['category_name'],
                'category_code' => $resultArray['category_code'] ?? null,
                'total_cents' => $totalCents,
                'total_brl' => $totalCents / 100,
                'count' => (int) ($resultArray['count'] ?? 0)
            ];
        }

        return $summary;
    }
}
