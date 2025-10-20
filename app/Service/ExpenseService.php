<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ExpenseRepository;
use App\Repository\CategoryRepository;
use Carbon\Carbon;

class ExpenseService
{
    public function __construct(
        private ExpenseRepository $expenseRepository,
        private CategoryRepository $categoryRepository
    ) {}

    public function createExpense(array $data): array
    {
        $expenseData = [
            'user_id' => $data['user_id'],
            'amount_cents' => $data['amount_cents'],
            'currency' => $data['currency'] ?? 'BRL',
            'category_id' => $data['category_id'] ?? null,
            'description' => $data['description'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? Carbon::now(),
            'status' => $data['status'] ?? 'confirmed',
            'source_message_id' => $data['source_message_id'],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        $expenseId = $this->expenseRepository->create($expenseData);
        return $this->expenseRepository->findById($expenseId);
    }

    public function getExpensesByUser(int $userId, ?string $from = null, ?string $to = null, ?int $categoryId = null): array
    {
        return $this->expenseRepository->findByUser($userId, $from, $to, $categoryId);
    }

    public function getExpensesSummary(int $userId, ?string $from = null, ?string $to = null, ?int $categoryId = null): array
    {
        $expenses = $this->getExpensesByUser($userId, $from, $to, $categoryId);
        
        $total = array_sum(array_column($expenses, 'amount_cents'));
        $count = count($expenses);
        
        return [
            'total_cents' => $total,
            'total_brl' => $total / 100,
            'count' => $count,
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'category_id' => $categoryId,
        ];
    }

    public function findCategoryByCode(string $code): ?array
    {
        return $this->categoryRepository->findByCode($code);
    }

    public function getAllCategories(): array
    {
        return $this->categoryRepository->findAll();
    }
}
