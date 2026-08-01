<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Tests\Support\FailingDatabaseBootstrapper;
use Tests\TenantTestCase;

class WebLoginTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.bootstrappers' => []]);
    }

    public function test_get_login_shows_suspended_page_for_a_suspended_tenant_session(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        $tenant->suspended_at = now();
        $tenant->save();

        Auth::guard('web')->login($this->user);

        $this->withSession(['tenant_id' => $tenant->id])
            ->get('/login')
            ->assertStatus(503)
            ->assertViewIs('errors.tenant-suspended')
            ->assertSee('System unavailable')
            ->assertSee($tenant->id);
    }

    public function test_login_post_shows_suspended_page_with_reason(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        $tenant->suspended_at = now();
        $tenant->suspension_reason = 'Payment overdue for March invoice';
        $tenant->save();

        Auth::shouldUse('web');

        $this->post('/login', [
            'property_code' => $tenant->id,
            'username' => $this->user->username,
            'password' => 'password',
        ])
            ->assertStatus(503)
            ->assertViewIs('errors.tenant-suspended')
            ->assertViewHas('reason', 'Payment overdue for March invoice')
            ->assertSee('Payment overdue for March invoice');
    }

    public function test_login_post_shows_suspended_page_for_a_suspended_tenant(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        $tenant->suspended_at = now();
        $tenant->save();

        Auth::shouldUse('web');

        $this->post('/login', [
            'property_code' => $tenant->id,
            'username' => $this->user->username,
            'password' => 'password',
        ])
            ->assertStatus(503)
            ->assertViewIs('errors.tenant-suspended')
            ->assertSee('System unavailable');
    }

    public function test_get_login_redirects_to_dashboard_for_an_active_tenant_session(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));

        Auth::guard('web')->login($this->user);

        $this->withSession(['tenant_id' => $tenant->id])
            ->get('/login')
            ->assertRedirect(route('tenant.dashboard'));
    }

    public function test_successful_login_sets_the_active_session_cookie(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));

        // Reset the default guard from 'sanctum' (set by Sanctum::actingAs in
        // TenantTestCase::setUp) back to 'web' so Auth::login() in the
        // controller resolves to a SessionGuard rather than a RequestGuard.
        Auth::shouldUse('web');

        $this->post('/login', [
            'property_code' => $tenant->id,
            'username' => $this->user->username,
            'password' => 'password',
        ])->assertCookie('nexstay_active', '1');
    }

    public function test_logout_clears_the_active_session_cookie(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));

        Auth::guard('web')->login($this->user);

        $this->web()
            ->withSession(['tenant_id' => $tenant->id])
            ->post('/logout')
            ->assertCookieExpired('nexstay_active');
    }

    public function test_get_login_recovers_when_session_tenant_database_is_unreachable(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));

        Auth::guard('web')->login($this->user);

        config(['tenancy.bootstrappers' => [FailingDatabaseBootstrapper::class]]);

        $response = $this->withSession(['tenant_id' => $tenant->id])->get('/login');

        $response->assertOk()->assertViewIs('auth.login');
        $this->assertFalse(tenancy()->initialized);
        $this->assertNull(session('tenant_id'));
    }

    public function test_login_post_recovers_when_tenant_database_is_unreachable(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));

        config(['tenancy.bootstrappers' => [FailingDatabaseBootstrapper::class]]);

        $response = $this->post('/login', [
            'property_code' => $tenant->id,
            'username' => $this->user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertFalse(tenancy()->initialized);

        $errors = $response->getSession()?->get('errors');
        $this->assertNotNull($errors);
        $this->assertSame(
            'We could not connect to your property. Please try again or contact NexStay support.',
            $errors->first('property_code')
        );
    }
}
