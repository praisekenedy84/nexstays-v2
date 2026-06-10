<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    protected static function booted(): void
    {
        // Bust the central-tenant lookup cache so a suspension takes effect
        // immediately instead of waiting for the cached entry to expire.
        //
        // Use the default store explicitly: while tenancy is initialized, the
        // `cache` binding is Stancl's tenant-scoped CacheManager, which forces
        // every call through tags() — unsupported by the database/file cache
        // stores and would throw BadMethodCallException on every tenant save.
        static::saved(fn (self $tenant) => Cache::store(config('cache.default'))->forget("central_tenant:{$tenant->id}"));
        static::deleted(fn (self $tenant) => Cache::store(config('cache.default'))->forget("central_tenant:{$tenant->id}"));
    }
}
