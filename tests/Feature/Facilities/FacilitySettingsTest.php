<?php

declare(strict_types=1);

namespace Tests\Feature\Facilities;

use App\Domain\Shared\Services\FacilitySettingsService;
use App\Models\Tenant;
use Tests\TenantTestCase;

class FacilitySettingsTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.bootstrappers' => []]);

        $tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'facility-settings-test']));
        tenancy()->initialize($tenant);
    }

    public function test_admin_can_view_and_update_facility_fee_settings(): void
    {
        $this->user->givePermissionTo('manage-roles');

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.finance.facility-settings.edit'));

        $response->assertOk();
        $response->assertSee('Facility Fees');

        $update = $this->web()
            ->actingAs($this->user, 'web')
            ->put(route('tenant.finance.facility-settings.update'), [
                'pool_default_fee' => 25000,
                'gym_default_fee' => 18000,
            ]);

        $update->assertRedirect(route('tenant.finance.facility-settings.edit'));
        $update->assertSessionHas('success');

        $settings = app(FacilitySettingsService::class)->all();
        $this->assertSame(25000.0, $settings['pool_default_fee']);
        $this->assertSame(18000.0, $settings['gym_default_fee']);

        $this->assertSame(25000.0, app(FacilitySettingsService::class)->defaultFeeFor('pool'));
        $this->assertSame(18000.0, app(FacilitySettingsService::class)->defaultFeeFor('gym'));
    }

    public function test_pool_desk_shows_configured_default_fee(): void
    {
        app(FacilitySettingsService::class)->update([
            'pool_default_fee' => 30000,
            'gym_default_fee' => 20000,
        ]);

        $response = $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.facilities.pool'));

        $response->assertOk();
        $response->assertSee('30,000');
    }
}
