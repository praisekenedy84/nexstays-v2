<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Tests\TenantTestCase;

class TenantSuspensionCacheTest extends TenantTestCase
{
    public function test_updating_suspended_at_busts_the_cached_tenant_lookup(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->create(['id' => 'demo']));

        Cache::put('central_tenant:demo', $tenant, 60);

        $tenant->update(['suspended_at' => now()]);

        $this->assertFalse(Cache::has('central_tenant:demo'));
    }

    public function test_deleting_a_tenant_busts_the_cached_tenant_lookup(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->create(['id' => 'demo']));

        Cache::put('central_tenant:demo', $tenant, 60);

        $tenant->delete();

        $this->assertFalse(Cache::has('central_tenant:demo'));
    }
}
