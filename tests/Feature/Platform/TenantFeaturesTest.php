<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Domain\Shared\Actions\SyncFeatureCeiling;
use App\Domain\Shared\Actions\UpdateTenantFeatures;
use App\Models\PlatformAdmin;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\HbmsNavigation;
use App\Support\TenantFeatures;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TenantTestCase;

class TenantFeaturesTest extends TenantTestCase
{
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.bootstrappers' => []]);

        $admin = new PlatformAdmin(['name' => 'Ops Admin', 'email' => 'ops@nexstay.test']);
        $admin->id = (string) Str::uuid();
        Auth::guard('platform_admin')->setUser($admin);

        $this->tenant = Tenant::withoutEvents(fn () => Tenant::query()->firstOrCreate(['id' => 'demo']));
        $this->tenant->enabled_features = null;
        $this->tenant->save();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_platform_can_update_features_on_tenant(): void
    {
        $this->from(route('platform.tenants.show', $this->tenant->id))
            ->patch(route('platform.tenants.features', $this->tenant->id), [
                'features' => ['restaurant', 'inventory'],
            ])
            ->assertRedirect(route('platform.tenants.show', $this->tenant->id))
            ->assertSessionHas('success');

        $this->tenant->refresh();
        $this->assertEqualsCanonicalizing(['restaurant', 'inventory'], $this->tenant->enabled_features);
    }

    public function test_disabling_hotel_hides_front_office_nav_and_blocks_routes(): void
    {
        $this->tenant->enabled_features = ['restaurant', 'bar', 'lounge', 'inventory', 'facilities'];
        $this->tenant->save();
        tenancy()->initialize($this->tenant);

        app(SyncFeatureCeiling::class)->execute($this->tenant);

        $this->user->refresh();
        $this->user->unsetRelation('roles');
        $this->user->unsetRelation('permissions');

        $labels = $this->flattenNavLabels(HbmsNavigation::forUser($this->user));
        $this->assertNotContains('Reservations', $labels);
        $this->assertContains('Restaurant', $labels);

        $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.reservations.index'))
            ->assertForbidden();

        $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.menu-items.index'))
            ->assertOk();
    }

    public function test_restaurant_only_tenant_allows_menu_and_till(): void
    {
        $this->tenant->enabled_features = ['restaurant'];
        $this->tenant->save();
        tenancy()->initialize($this->tenant);
        app(SyncFeatureCeiling::class)->execute($this->tenant);

        $this->assertTrue(TenantFeatures::allows('restaurant', $this->tenant));
        $this->assertFalse(TenantFeatures::allows('hotel', $this->tenant));
        $this->assertTrue(TenantFeatures::allowsPermission('view-menu', $this->tenant));
        $this->assertTrue(TenantFeatures::allowsPermission('view-till', $this->tenant));
        $this->assertFalse(TenantFeatures::allowsPermission('view-reservations', $this->tenant));

        $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.menu-items.index'))
            ->assertOk();

        $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.till.index'))
            ->assertOk();

        $this->web()
            ->actingAs($this->user, 'web')
            ->get(route('tenant.rooms.index'))
            ->assertForbidden();
    }

    public function test_role_update_rejects_permission_outside_ceiling(): void
    {
        $this->tenant->enabled_features = ['restaurant'];
        $this->tenant->save();
        tenancy()->initialize($this->tenant);
        app(SyncFeatureCeiling::class)->execute($this->tenant);

        $manager = User::factory()->create();
        // After ceiling sync, general_manager may lack manage-roles if somehow stripped —
        // manage-roles is always-on, so it remains.
        $manager->assignRole('general_manager');

        $role = Role::findByName('waiter', 'web');

        $this->web()
            ->actingAs($manager, 'web')
            ->put(route('tenant.roles.update', $role), [
                'permissions' => ['view-orders', 'view-reservations'],
                'navigation' => [],
            ])
            ->assertSessionHasErrors('permissions.1');
    }

    public function test_reseed_respects_feature_ceiling(): void
    {
        $this->tenant->enabled_features = ['restaurant'];
        $this->tenant->save();
        tenancy()->initialize($this->tenant);

        app(RoleAndPermissionSeeder::class)->run();

        $adminRole = Role::findByName('super_admin', 'web');
        $perms = $adminRole->permissions->pluck('name')->all();

        $this->assertContains('view-orders', $perms);
        $this->assertContains('manage-roles', $perms);
        $this->assertNotContains('view-reservations', $perms);
        $this->assertNotContains('view-inventory', $perms);
    }

    public function test_update_tenant_features_action_syncs_ceiling(): void
    {
        tenancy()->initialize($this->tenant);
        app(RoleAndPermissionSeeder::class)->run();
        tenancy()->end();

        app(UpdateTenantFeatures::class)->execute($this->tenant, ['hotel']);

        tenancy()->initialize($this->tenant);
        $adminRole = Role::findByName('super_admin', 'web');
        $perms = $adminRole->permissions->pluck('name')->all();

        $this->assertContains('view-reservations', $perms);
        $this->assertNotContains('view-orders', $perms);
        $this->assertNotContains('view-facility-attendance', $perms);
    }

    public function test_null_enabled_features_means_all_modules_on(): void
    {
        $this->tenant->enabled_features = null;
        $this->tenant->save();

        $this->assertEqualsCanonicalizing(TenantFeatures::keys(), TenantFeatures::enabled($this->tenant));
    }

    /**
     * @param  list<array<string, mixed>>  $nav
     * @return list<string>
     */
    private function flattenNavLabels(array $nav): array
    {
        $labels = [];

        foreach ($nav as $item) {
            if (isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    $labels[] = $child['label'];
                }

                continue;
            }

            $labels[] = $item['label'];
        }

        return $labels;
    }
}
