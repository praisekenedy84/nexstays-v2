<?php

declare(strict_types=1);

namespace App\Domain\Shared\Actions;

use App\Models\Role;
use App\Models\Tenant;
use App\Support\TenantFeatures;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

final class SyncFeatureCeiling
{
    public function execute(?Tenant $tenant = null): void
    {
        $tenant ??= tenant();
        $allowedPermissions = TenantFeatures::allowedPermissions($tenant);
        $allowedNavIds = TenantFeatures::allowedNavigationIds($tenant);
        $hasHiddenNavColumn = Schema::hasColumn('roles', 'hidden_navigation_ids');

        DB::transaction(function () use ($allowedPermissions, $allowedNavIds, $hasHiddenNavColumn) {
            $columns = ['id', 'name', 'guard_name'];
            if ($hasHiddenNavColumn) {
                $columns[] = 'hidden_navigation_ids';
            }

            $roles = Role::query()
                ->select($columns)
                ->where('guard_name', 'web')
                ->with('permissions')
                ->get();

            foreach ($roles as $role) {
                $current = $role->permissions->pluck('name')->all();
                $kept = array_values(array_intersect($current, $allowedPermissions));
                $role->syncPermissions($kept);

                if (! $hasHiddenNavColumn) {
                    continue;
                }

                $hidden = $role->hidden_navigation_ids ?? [];
                if ($hidden !== []) {
                    $hidden = array_values(array_intersect($hidden, $allowedNavIds));
                    $role->update(['hidden_navigation_ids' => $hidden === [] ? null : $hidden]);
                }
            }
        });

        $this->forgetPermissionCache();
    }

    /**
     * Flush Spatie permission cache without Stancl tenant cache tags
     * (database/file stores do not support tagging and would 500).
     */
    private function forgetPermissionCache(): void
    {
        $registrar = app(PermissionRegistrar::class);

        try {
            $registrar->forgetCachedPermissions();
        } catch (\BadMethodCallException) {
            Cache::store(config('cache.default'))
                ->forget(config('permission.cache.key', 'spatie.permission.cache'));
            $registrar->initializeCache();
        }
    }
}
