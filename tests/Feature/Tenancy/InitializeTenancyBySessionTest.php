<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Http\Middleware\InitializeTenancyBySession;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Session\NullSessionHandler;
use Illuminate\Support\Facades\Cookie;
use Tests\Support\FailingDatabaseBootstrapper;
use Tests\TenantTestCase;

class InitializeTenancyBySessionTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.bootstrappers' => []]);
    }

    private function requestWithSession(array $sessionData): Request
    {
        $session = new Store('test-session', new NullSessionHandler());

        foreach ($sessionData as $key => $value) {
            $session->put($key, $value);
        }

        $request = Request::create('/dashboard', 'GET');
        $request->setLaravelSession($session);

        return $request;
    }

    public function test_unknown_tenant_ends_a_leaked_tenancy_before_redirecting(): void
    {
        $request = $this->requestWithSession(['tenant_id' => 'missing-tenant']);

        // Simulate a worker that left tenancy initialized from a previous request.
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'leftover']));
        tenancy()->initialize($tenant);

        $response = app(InitializeTenancyBySession::class)->handle($request, fn ($req) => response('ok'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_missing_tenant_id_with_active_session_cookie_reports_session_expired(): void
    {
        $request = $this->requestWithSession([]);
        $request->cookies->set('nexstay_active', '1');

        $response = app(InitializeTenancyBySession::class)->handle($request, fn ($req) => response('ok'));

        $this->assertSame(302, $response->getStatusCode());

        $errors = $response->getSession()?->get('errors');
        $this->assertNotNull($errors);
        $this->assertSame(
            'Your session has expired due to inactivity. Please log in again.',
            $errors->first('property_code')
        );
        $this->assertTrue(Cookie::hasQueued('nexstay_active'));
    }

    public function test_missing_tenant_id_without_active_session_cookie_redirects_silently(): void
    {
        $request = $this->requestWithSession([]);

        $response = app(InitializeTenancyBySession::class)->handle($request, fn ($req) => response('ok'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertFalse(Cookie::hasQueued('nexstay_active'));
    }

    public function test_unreachable_tenant_database_clears_session_and_redirects_with_message(): void
    {
        config(['tenancy.bootstrappers' => [FailingDatabaseBootstrapper::class]]);

        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));

        $request = $this->requestWithSession(['tenant_id' => $tenant->id]);

        $response = app(InitializeTenancyBySession::class)->handle($request, fn ($req) => response('ok'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertFalse(tenancy()->initialized);
        $this->assertNull($request->session()->get('tenant_id'));

        $errors = $response->getSession()?->get('errors');
        $this->assertNotNull($errors);
        $this->assertSame(
            'We could not connect to your property. Please log in again or contact NexStay support.',
            $errors->first('property_code')
        );
    }

    public function test_suspended_tenant_ends_a_leaked_tenancy_and_shows_unavailable_page(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        $tenant->suspended_at = now();
        $tenant->save();

        $request = $this->requestWithSession(['tenant_id' => $tenant->id]);

        // Simulate a worker that left tenancy initialized from a previous request.
        tenancy()->initialize($tenant);

        $response = app(InitializeTenancyBySession::class)->handle($request, fn ($req) => response('ok'));

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('errors.tenant-suspended', $response->original->name());
        $this->assertFalse(tenancy()->initialized);
        $this->assertTrue(Cookie::hasQueued('nexstay_active'));
    }
}
