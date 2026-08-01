<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\PlatformAdmin;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TenantTestCase;

class TenantSuspensionTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.bootstrappers' => []]);

        $admin = new PlatformAdmin(['name' => 'Ops Admin', 'email' => 'ops@nexstay.test']);
        $admin->id = (string) Str::uuid();
        Auth::guard('platform_admin')->setUser($admin);
    }

    public function test_suspend_requires_a_reason(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        $tenant->suspended_at = null;
        $tenant->suspension_reason = null;
        $tenant->save();

        $this->from(route('platform.tenants.show', $tenant->id))
            ->patch(route('platform.tenants.suspend', $tenant->id))
            ->assertRedirect(route('platform.tenants.show', $tenant->id))
            ->assertSessionHasErrors('reason');

        $tenant->refresh();
        $this->assertEmpty($tenant->suspended_at);
    }

    public function test_suspend_stores_reason_and_restore_clears_it(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        $tenant->suspended_at = null;
        $tenant->suspension_reason = null;
        $tenant->save();

        $this->from(route('platform.tenants.show', $tenant->id))
            ->patch(route('platform.tenants.suspend', $tenant->id), [
                'reason' => 'Payment overdue for March invoice',
            ])
            ->assertRedirect(route('platform.tenants.show', $tenant->id))
            ->assertSessionHas('success');

        $tenant->refresh();
        $this->assertNotEmpty($tenant->suspended_at);
        $this->assertSame('Payment overdue for March invoice', $tenant->suspension_reason);

        $this->from(route('platform.tenants.show', $tenant->id))
            ->patch(route('platform.tenants.restore', $tenant->id))
            ->assertRedirect(route('platform.tenants.show', $tenant->id));

        $tenant->refresh();
        $this->assertEmpty($tenant->suspended_at);
        $this->assertEmpty($tenant->suspension_reason);
    }
}
