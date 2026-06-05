<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public const GUARD = 'web';

    /**
     * @var list<string>
     */
    public const PERMISSIONS = [
        'view-reservations',
        'manage-reservations',
        'check-in-guests',
        'check-out-guests',
        'view-guests',
        'manage-guests',
        'view-rooms',
        'manage-rooms',
        'manage-room-status',
        'manage-room-types',
        'manage-rate-plans',
        'manage-outlets',
        'view-folios',
        'post-folio-charges',
        'view-availability',
        'manage-users',
        'manage-roles',
        'view-reports',
        'view-outlets',
        'view-menu',
        'manage-menu',
        'view-orders',
        'manage-orders',
        'view-till',
        'manage-till',
        'view-inventory',
        'manage-inventory',
        'run-night-audit',
        'view-purchases',
        'manage-purchases',
        'view-expenditures',
        'manage-expenditures',
        'view-ancillary-services',
        'manage-ancillary-services',
        'view-debts',
        'view-damage',
        'manage-damage',
        'view-fb-reports',
    ];

    /**
     * @var list<string>
     */
    public const ROLES = [
        'super_admin',
        'general_manager',
        'front_desk',
        'housekeeper',
        'fb_manager',
        'waiter',
        'bartender',
        'lounge_staff',
        'head_chef',
        'finance',
        'it_admin',
        'guest',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        foreach (self::ROLES as $roleName) {
            Role::findOrCreate($roleName, self::GUARD);
        }

        $all = Permission::all();

        Role::findByName('super_admin', self::GUARD)->syncPermissions($all);
        Role::findByName('general_manager', self::GUARD)->syncPermissions($all);
        Role::findByName('it_admin', self::GUARD)->syncPermissions($all);

        Role::findByName('front_desk', self::GUARD)->syncPermissions([
            'view-reservations',
            'manage-reservations',
            'check-in-guests',
            'check-out-guests',
            'view-guests',
            'manage-guests',
            'view-rooms',
            'manage-rooms',
            'manage-room-status',
            'manage-room-types',
            'manage-rate-plans',
            'view-folios',
            'post-folio-charges',
            'view-availability',
            'view-debts',
            'view-ancillary-services',
            'view-damage',
            'manage-damage',
        ]);

        Role::findByName('housekeeper', self::GUARD)->syncPermissions([
            'view-rooms',
            'manage-room-status',
        ]);

        $fbStaff = [
            'view-outlets', 'manage-outlets', 'view-menu', 'manage-menu', 'view-orders', 'manage-orders',
            'view-folios', 'post-folio-charges', 'view-till', 'manage-till',
            'view-inventory', 'manage-inventory',
            'view-purchases', 'manage-purchases',
            'view-ancillary-services', 'manage-ancillary-services',
        ];

        Role::findByName('fb_manager', self::GUARD)->syncPermissions(array_merge($fbStaff, ['view-reports']));

        Role::findByName('waiter', self::GUARD)->syncPermissions([
            'view-outlets', 'view-menu', 'view-orders', 'manage-orders', 'view-folios', 'post-folio-charges',
        ]);

        Role::findByName('bartender', self::GUARD)->syncPermissions([
            'view-outlets', 'view-menu', 'view-orders', 'manage-orders', 'view-till', 'manage-till',
        ]);

        Role::findByName('lounge_staff', self::GUARD)->syncPermissions([
            'view-outlets', 'view-menu', 'view-orders', 'manage-orders',
        ]);

        Role::findByName('head_chef', self::GUARD)->syncPermissions([
            'view-outlets', 'view-menu', 'view-orders', 'view-inventory', 'manage-inventory',
        ]);

        Role::findByName('finance', self::GUARD)->syncPermissions([
            'view-reservations', 'view-guests', 'view-folios', 'view-reports', 'view-fb-reports',
            'view-till', 'view-inventory', 'run-night-audit',
            'view-purchases', 'view-expenditures', 'manage-expenditures', 'view-debts',
            'manage-rate-plans', 'manage-room-types', 'view-rooms',
        ]);


        Role::findByName('guest', self::GUARD)->syncPermissions([
            'view-availability',
        ]);
    }
}
