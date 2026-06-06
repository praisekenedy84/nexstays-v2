<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Actions\SyncRoleNavigation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreRoleRequest;
use App\Http\Requests\Web\UpdateRoleRequest;
use App\Models\Role;
use App\Support\NavigationMenuRegistry;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public const PERMISSION_GROUPS = [
        'Front office' => [
            'view-availability',
            'view-reservations', 'manage-reservations',
            'check-in-guests', 'check-out-guests',
        ],
        'Guests' => ['view-guests', 'manage-guests'],
        'Rooms' => [
            'view-rooms', 'manage-rooms', 'manage-room-status',
            'manage-room-types', 'manage-rate-plans',
        ],
        'Food & beverage' => [
            'view-outlets', 'manage-outlets',
            'view-menu', 'manage-menu',
            'view-orders', 'view-all-orders',
            'manage-orders', 'manage-all-orders', 'manage-own-orders',
            'process-kitchen-orders',
        ],
        'Till' => ['view-till', 'manage-till'],
        'Inventory' => [
            'view-inventory', 'manage-inventory',
            'view-purchases', 'manage-purchases',
        ],
        'Finance' => [
            'view-folios', 'post-folio-charges',
            'view-debts',
            'view-ancillary-services', 'manage-ancillary-services',
            'view-expenditures', 'manage-expenditures',
        ],
        'Damage' => ['view-damage', 'manage-damage'],
        'Reports' => ['view-reports', 'view-fb-reports', 'run-night-audit'],
        'Administration' => ['manage-users', 'manage-roles'],
    ];

    public function index(Request $request): View
    {
        $sort   = in_array($request->query('sort'), ['name', 'users_count', 'permissions_count']) ? $request->query('sort') : 'name';
        $dir    = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $search = trim((string) $request->query('search', ''));

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->withCount(['permissions', 'users'])
            ->when($search, fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('hbms.roles.index', ['roles' => $roles, 'systemRoles' => RoleAndPermissionSeeder::ROLES, 'sort' => $sort, 'dir' => $dir, 'search' => $search]);
    }

    public function create(): View
    {
        $role = new Role;

        return view('hbms.roles.form', [
            'role' => $role,
            'permissionGroups' => self::PERMISSION_GROUPS,
            'rolePermissions' => [],
            'navigationGroups' => NavigationMenuRegistry::groups(),
            'visibleNavigationIds' => NavigationMenuRegistry::allItemIds(),
            'systemRoles' => RoleAndPermissionSeeder::ROLES,
        ]);
    }

    public function store(StoreRoleRequest $request, SyncRoleNavigation $syncRoleNavigation): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($request->validated('permissions', []));
        $syncRoleNavigation->execute($role, $request->validated('navigation', []));

        return redirect()->route('tenant.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): View
    {
        return view('hbms.roles.form', [
            'role' => $role,
            'permissionGroups' => self::PERMISSION_GROUPS,
            'rolePermissions' => $role->permissions->pluck('name')->all(),
            'navigationGroups' => NavigationMenuRegistry::groups(),
            'visibleNavigationIds' => NavigationMenuRegistry::visibleItemIdsForRole($role),
            'systemRoles' => RoleAndPermissionSeeder::ROLES,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, SyncRoleNavigation $syncRoleNavigation): RedirectResponse
    {
        $role->syncPermissions($request->validated('permissions', []));
        $syncRoleNavigation->execute($role, $request->validated('navigation', []));

        return redirect()->route('tenant.roles.index')->with('success', 'Role updated.');
    }

    public function matrix(): View
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        $rolePermissions = $roles->mapWithKeys(
            fn ($role) => [$role->id => $role->permissions->pluck('name')->flip()]
        );

        return view('hbms.roles.matrix', [
            'roles' => $roles,
            'permissionGroups' => self::PERMISSION_GROUPS,
            'rolePermissions' => $rolePermissions,
            'systemRoles' => RoleAndPermissionSeeder::ROLES,
        ]);
    }

    public function clone(Role $role): RedirectResponse
    {
        $baseName = 'copy_of_' . $role->name;
        $name     = $baseName;
        $suffix   = 2;

        while (Role::where('name', $name)->where('guard_name', 'web')->exists()) {
            $name = $baseName . '_' . $suffix++;
        }

        $clone = Role::create([
            'name' => $name,
            'guard_name' => 'web',
            'hidden_navigation_ids' => $role->hidden_navigation_ids,
        ]);
        $clone->syncPermissions($role->permissions);

        return redirect()
            ->route('tenant.roles.edit', $clone)
            ->with('success', 'Role cloned from "' . $role->name . '". Update the name and permissions below.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if(
            in_array($role->name, RoleAndPermissionSeeder::ROLES, true),
            403,
            'System roles cannot be deleted.'
        );
        abort_if(
            $role->users()->exists(),
            422,
            'Reassign staff before deleting this role.'
        );

        $role->delete();

        return redirect()->route('tenant.roles.index')->with('success', 'Role deleted.');
    }
}
