<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actions;

use App\Models\Role;
use App\Models\Tenant;
use App\Support\TenantFeatures;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

final class SyncFeatureCeiling
{
    public function execute(?Tenant $tenant = null): void
    {
        $tenant ??= tenant();
        $allowedPermissions = TenantFeatures::allowedPermissions($tenant);
        $allowedNavIds = TenantFeatures::allowedNavigationIds($tenant);

        DB::transaction(function () use ($allowedPermissions, $allowedNavIds) {
            $roles = Role::query()->where('guard_name', 'web')->with('permissions')->get();

            foreach ($roles as $role) {
                $current = $role->permissions->pluck('name')->all();
                $kept = array_values(array_intersect($current, $allowedPermissions));
                $role->syncPermissions($kept);

                $hidden = $role->hidden_navigation_ids ?? [];
                if ($hidden !== []) {
                    $hidden = array_values(array_intersect($hidden, $allowedNavIds));
                    $role->update(['hidden_navigation_ids' => $hidden === [] ? null : $hidden]);
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
