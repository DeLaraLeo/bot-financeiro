<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\User;
use App\Model\Category;

class Expense extends Model
{
    protected ?string $table = 'expenses';

    protected array $fillable = [
        'user_id',
        'amount_cents',
        'currency',
        'category_id',
        'description',
        'occurred_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'amount_cents' => 'integer',
        'currency' => 'string',
        'category_id' => 'integer',
        'description' => 'string',
        'occurred_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

