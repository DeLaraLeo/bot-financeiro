<?php

declare(strict_types=1);

namespace App\Model;

class Category extends Model
{
    protected ?string $table = 'categories';

    protected array $fillable = [
        'code',
        'name',
    ];

    protected array $casts = [
        'id' => 'integer',
        'code' => 'string',
        'name' => 'string',
    ];
}

