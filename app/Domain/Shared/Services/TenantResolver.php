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
        // Use the default store explicitly: if tenancy is already initialized
        // (e.g. a long-running worker, or a re-entrant lookup), the `cache`
        // binding is Stancl's tenant-scoped CacheManager, which forces every
        // call through tags() — unsupported by the database/file cache stores.
        return Cache::store(config('cache.default'))->remember(
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
