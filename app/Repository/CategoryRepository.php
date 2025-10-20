<?php

declare(strict_types=1);

namespace App\Repository;

use Hyperf\DbConnection\Db;

class CategoryRepository
{
    public function findById(int $id): ?array
    {
        $result = Db::table('categories')->where('id', $id)->first();
        return $result ? (array) $result : null;
    }

    public function findByCode(string $code): ?array
    {
        $result = Db::table('categories')->where('code', $code)->first();
        return $result ? (array) $result : null;
    }

    public function findAll(): array
    {
        return Db::table('categories')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function create(array $data): int
    {
        return Db::table('categories')->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        return Db::table('categories')->where('id', $id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return Db::table('categories')->where('id', $id)->delete() > 0;
    }
}
