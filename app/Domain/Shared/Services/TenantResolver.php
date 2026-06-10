<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves a tenant by ID for session-based tenancy, applying the same
 * cached lookup and suspension check used by both the tenancy middleware
 * and the login form so the two cannot drift apart.
 */
class TenantResolver
{
    public function find(string $tenantId): ?Tenant
    {
        return Cache::remember(
            "central_tenant:{$tenantId}",
            60,
            fn () => Tenant::find($tenantId)
        );
    }

    public function isSuspended(Tenant $tenant): bool
    {
        return ! empty($tenant->suspended_at);
    }
}
