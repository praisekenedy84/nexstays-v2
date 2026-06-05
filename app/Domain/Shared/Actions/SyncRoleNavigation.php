<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actions;

use App\Models\Role;
use App\Support\NavigationMenuRegistry;

final class SyncRoleNavigation
{
    /**
     * @param  list<string>  $visibleNavIds
     */
    public function execute(Role $role, array $visibleNavIds): Role
    {
        $validIds = NavigationMenuRegistry::allItemIds();
        $visibleNavIds = array_values(array_intersect($visibleNavIds, $validIds));
        $hiddenIds = array_values(array_diff($validIds, $visibleNavIds));

        $role->update(['hidden_navigation_ids' => $hiddenIds === [] ? null : $hiddenIds]);

        return $role->refresh();
    }
}
