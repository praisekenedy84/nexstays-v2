<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Shared\Actions\SyncRoleNavigation;
use App\Models\Role;
use App\Models\User;
use App\Support\HbmsNavigation;
use App\Support\NavigationMenuRegistry;
use Tests\TenantTestCase;

class RoleNavigationTest extends TenantTestCase
{
    public function test_waiter_navigation_hides_items_without_permission(): void
    {
        $waiter = User::factory()->create();
        $waiter->assignRole('waiter');

        $nav = HbmsNavigation::forUser($waiter);
        $labels = $this->flattenNavLabels($nav);

        $this->assertContains('Restaurant', $labels);
        $this->assertNotContains('Staff shift', $labels);
        $this->assertNotContains('Staff', $labels);
    }

    public function test_role_can_hide_menu_item_while_keeping_permission(): void
    {
        $role = Role::findByName('waiter', 'web');

        app(SyncRoleNavigation::class)->execute($role, array_values(array_diff(
            NavigationMenuRegistry::allItemIds(),
            ['bar']
        )));

        $waiter = User::factory()->create();
        $waiter->assignRole('waiter');

        $labels = $this->flattenNavLabels(HbmsNavigation::forUser($waiter));

        $this->assertContains('Restaurant', $labels);
        $this->assertNotContains('Bar', $labels);
    }

    public function test_manager_can_update_role_navigation_from_form(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('general_manager');

        $role = Role::findByName('waiter', 'web');
        $visibleIds = array_values(array_diff(NavigationMenuRegistry::allItemIds(), ['bar', 'lounge']));

        $response = $this->web()
            ->actingAs($manager, 'web')
            ->put(route('tenant.roles.update', $role), [
                'permissions' => $role->permissions->pluck('name')->all(),
                'navigation' => $visibleIds,
            ]);

        $response->assertRedirect(route('tenant.roles.index'));

        $role->refresh();
        $this->assertEquals(['bar', 'lounge'], $role->hidden_navigation_ids);
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
            } else {
                $labels[] = $item['label'];
            }
        }

        return $labels;
    }
}
