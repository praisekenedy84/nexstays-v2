<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Shared\Services\TimezoneService;
use App\Http\Middleware\InitializeTenancyBySession;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TenantTestCase;

class TimezoneSyncTest extends TenantTestCase
{
    public function test_authenticated_user_can_sync_browser_timezone(): void
    {
        $response = $this
            ->withoutMiddleware([
                InitializeTenancyBySubdomain::class,
                PreventAccessFromCentralDomains::class,
                InitializeTenancyBySession::class,
            ])
            ->actingAs($this->user, 'web')
            ->postJson(route('tenant.timezone.sync'), [
                'timezone' => 'Africa/Nairobi',
            ]);

        $response->assertOk();
        $response->assertJson(['timezone' => 'Africa/Nairobi']);

        $this->assertSame('Africa/Nairobi', $this->user->fresh()->timezone);
    }

    public function test_sync_seeds_tenant_timezone_when_missing(): void
    {
        $tenant = Tenant::withoutEvents(fn () => Tenant::create([
            'id' => 'timezone-test-'.Str::uuid(),
            'data' => [],
        ]));

        app(TimezoneService::class)->syncForUser($this->user, 'Africa/Dar_es_Salaam', $tenant);

        $tenant->refresh();
        $this->assertSame('Africa/Dar_es_Salaam', $tenant->timezone);
    }

    public function test_invalid_timezone_is_rejected(): void
    {
        $response = $this
            ->withoutMiddleware([
                InitializeTenancyBySubdomain::class,
                PreventAccessFromCentralDomains::class,
                InitializeTenancyBySession::class,
            ])
            ->actingAs($this->user, 'web')
            ->postJson(route('tenant.timezone.sync'), [
                'timezone' => 'Not/A/Timezone',
            ]);

        $response->assertUnprocessable();
    }
}
