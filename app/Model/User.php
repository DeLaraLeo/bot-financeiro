<?php

declare(strict_types=1);

namespace App\Model;

class User extends Model
{
    protected ?string $table = 'users';

    protected array $fillable = [
        'phone_e164',
        'name',
    ];

    protected array $casts = [
        'id' => 'integer',
        'phone_e164' => 'string',
        'name' => 'string',
    ];
}

