<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Category;

class CategoryRepository
{
    public function findById(int $id): ?array
    {
        $category = Category::find($id);
        return $category ? $category->toArray() : null;
    }

    public function findByCode(string $code): ?array
    {
        $category = Category::where('code', $code)->first();
        return $category ? $category->toArray() : null;
    }

    public function findAll(): array
    {
        return Category::orderBy('name')
            ->get()
            ->toArray();
    }

    public function create(array $data): int
    {
        $category = Category::create($data);
        return $category->id;
    }

    public function update(int $id, array $data): bool
    {
        $category = Category::find($id);
        if (!$category) {
            return false;
        }
        
        return $category->update($data);
    }

    public function delete(int $id): bool
    {
        $category = Category::find($id);
        if (!$category) {
            return false;
        }
        
        return $category->delete();
    }
}
