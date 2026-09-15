<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailOtp extends Model
{
    public const PURPOSE_LOGIN = 'login';

    public const PURPOSE_REGISTER = 'register';

    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    protected $fillable = [
        'email',
        'purpose',
        'code_hash',
        'payload',
        'attempts',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
