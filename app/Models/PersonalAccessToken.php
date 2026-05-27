<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuids;

    protected $connection = 'central';

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'tokenable_id',
        'tokenable_type',
        'tenant_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $token): void {
            if (tenancy()->initialized) {
                $token->tenant_id = tenant('id');
            }
        });
    }
}
