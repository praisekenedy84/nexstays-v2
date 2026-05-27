<?php

use App\Http\Controllers\Platform;
use App\Http\Controllers\Web\TenantAssetController;
use App\Support\HbmsNavigation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central app — landlord domain only (no tenant context)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect(HbmsNavigation::tenantHomeUrl());
});

// Tenant-scoped file serving — tenant ID in URL, no auth required (hotel/food photos are not sensitive)
Route::get('/tenant-assets/{tenantId}/{path}', [TenantAssetController::class, 'serve'])
    ->where('path', '.*')
    ->name('tenant.assets');

/*
|--------------------------------------------------------------------------
| Platform admin — central, no tenant context
|--------------------------------------------------------------------------
*/
Route::prefix('platform')->name('platform.')->group(function () {
    Route::get('login', [Platform\AuthController::class, 'create'])->name('login');
    Route::post('login', [Platform\AuthController::class, 'store'])->name('login.store');

    Route::middleware('auth:platform_admin')->group(function () {
        Route::post('logout', [Platform\AuthController::class, 'destroy'])->name('logout');

        Route::get('/', fn () => redirect()->route('platform.tenants.index'));

        // Hotel management
        Route::get('tenants', [Platform\TenantController::class, 'index'])->name('tenants.index');
        Route::get('tenants/create', [Platform\TenantController::class, 'create'])->name('tenants.create');
        Route::post('tenants', [Platform\TenantController::class, 'store'])->name('tenants.store');
        Route::get('tenants/{tenant}', [Platform\TenantController::class, 'show'])->name('tenants.show');
        Route::patch('tenants/{tenant}/settings', [Platform\TenantController::class, 'updateSettings'])->name('tenants.settings');
        Route::patch('tenants/{tenant}/suspend', [Platform\TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::patch('tenants/{tenant}/restore', [Platform\TenantController::class, 'restore'])->name('tenants.restore');
        Route::patch('tenants/{tenant}/run-migrations', [Platform\TenantController::class, 'runMigrations'])->name('tenants.run-migrations');
        Route::patch('tenants/{tenant}/reseed-roles', [Platform\TenantController::class, 'reseedRoles'])->name('tenants.reseed-roles');
        Route::delete('tenants/{tenant}', [Platform\TenantController::class, 'destroy'])->name('tenants.destroy');

        // Tenant user actions
        Route::post('tenants/{tenant}/users/{user}/impersonate', [Platform\ImpersonateController::class, 'start'])->name('impersonate.start');
        Route::patch('tenants/{tenant}/users/{user}/role', [Platform\TenantController::class, 'changeUserRole'])->name('tenants.users.change-role');
        Route::post('tenants/{tenant}/users/{user}/reset-password', [Platform\TenantController::class, 'resetUserPassword'])->name('tenants.users.reset-password');
    });

    // Stop impersonation — reachable while web guard is active (no platform_admin guard check needed)
    Route::post('impersonate/stop', [Platform\ImpersonateController::class, 'stop'])->name('impersonate.stop');
});
