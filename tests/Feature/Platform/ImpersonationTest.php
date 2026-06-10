<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Http\Controllers\Platform\ImpersonateController;
use App\Models\PlatformAdmin;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TenantTestCase;

class ImpersonationTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.bootstrappers' => []]);

        // Authenticate the platform_admin guard without touching the central
        // platform_admins table (not needed by the controller beyond ->name).
        $admin = new PlatformAdmin(['name' => 'Ops Admin', 'email' => 'ops@nexstay.test']);
        $admin->id = (string) Str::uuid();
        Auth::guard('platform_admin')->setUser($admin);
    }

    private function requestWithSession(): Request
    {
        $session = $this->app['session']->driver();
        $session->start();

        $request = Request::create('/platform/impersonate', 'POST');
        $request->setLaravelSession($session);

        return $request;
    }

    public function test_impersonation_regenerates_the_session_and_records_impersonation_state(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));

        $request = $this->requestWithSession();
        $sessionId = $request->session()->getId();

        $response = app(ImpersonateController::class)->start($request, $tenant->id, $this->user->id);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('tenant.dashboard'), $response->headers->get('Location'));
        $this->assertNotSame($sessionId, $request->session()->getId());
        $this->assertSame($tenant->id, $request->session()->get('tenant_id'));
        $this->assertSame($this->user->id, $request->session()->get('impersonating.user_id'));
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_impersonating_a_nonexistent_user_returns_not_found(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));

        $this->expectException(ModelNotFoundException::class);

        app(ImpersonateController::class)->start($this->requestWithSession(), $tenant->id, (string) Str::uuid());
    }
}
