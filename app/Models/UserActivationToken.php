<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon as CarbonDate;

class UserActivationToken extends Model
{
    protected $table = 'user_activation_tokens';

    protected $fillable = [
        'uuid',
        'email',
        'token',
        'activation_code',
        'expired_at',
        'validated_at',
        'used_at',
    ];   

    protected $casts = [
        'expired_at' => 'datetime',
        'validated_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uuid', 'uuid');
    }

    public function isExpired(): bool
    {
        $now = CarbonDate::now();
        return $now->gt($this->expired_at);
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isValidated(): bool
    {
        return $this->validated_at !== null;
    }

    public function isValidCode(int $activationCode): bool
    {
        return $this->activation_code === $activationCode;
    }
}
