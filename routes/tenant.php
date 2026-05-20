<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BarTabController;
use App\Http\Controllers\Api\V1\HBMS\AvailabilityController as ApiAvailabilityController;
use App\Http\Controllers\Api\V1\OrderController as ApiOrderController;
use App\Http\Controllers\Api\V1\OutletController as ApiOutletController;
use App\Http\Controllers\Api\V1\MenuItemController as ApiMenuItemController;
use App\Http\Controllers\Api\V1\StockItemController as ApiStockItemController;
use App\Http\Controllers\Api\V1\TillController as ApiTillController;
use App\Http\Controllers\Api\V1\HBMS\FolioController;
use App\Http\Controllers\Api\V1\HBMS\GuestController as ApiGuestController;
use App\Http\Controllers\Api\V1\HBMS\ReservationController as ApiReservationController;
use App\Http\Controllers\Api\V1\HBMS\RoomController as ApiRoomController;
use App\Http\Controllers\Api\V1\HBMS\RoomTypeController as ApiRoomTypeController;
use App\Http\Controllers\Api\V1\UserController as ApiUserController;
use App\Http\Controllers\Web\AuthController as WebAuthController;
use App\Http\Controllers\Web\AvailabilityController;
use App\Http\Controllers\Web\BarController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\GuestController;
use App\Http\Controllers\Web\MenuCategoryController;
use App\Http\Controllers\Web\MenuItemController;
use App\Http\Controllers\Web\RatePlanController;
use App\Http\Controllers\Web\RoomTypeController;
use App\Http\Controllers\Web\StockItemController;
use App\Http\Controllers\Web\LoungeController;
use App\Http\Controllers\Web\OutletController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\ReservationController;
use App\Http\Controllers\Web\RestaurantController;
use App\Http\Controllers\Web\RoomController;
use App\Http\Controllers\Web\TillController;
use App\Http\Controllers\Web\AncillaryServiceController;
use App\Http\Controllers\Web\BookedListController;
use App\Http\Controllers\Web\DebtController;
use App\Http\Controllers\Web\ExpenditureController;
use App\Http\Controllers\Web\FbReportController;
use App\Http\Controllers\Web\PurchaseController;
use App\Http\Controllers\Web\RoomDamageController;
use App\Http\Controllers\Web\TimeLeftController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Api\V1\ReportController as ApiReportController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant API — modular monolith (HBMS + platform)
|--------------------------------------------------------------------------
|
| Base URL: http://{tenant}.localhost:8000/api/v1
| Demo tenant: http://demo.localhost:8000
|
*/

Route::middleware([
    'web',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->name('tenant.')->group(function () {
    Route::middleware('guest:web')->group(function () {
        Route::get('/login', [WebAuthController::class, 'create'])->name('login');
        Route::post('/login', [WebAuthController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:web')->group(function () {
        Route::get('/', fn () => redirect()->route('tenant.dashboard'));
        Route::post('/logout', [WebAuthController::class, 'destroy'])->name('logout');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::middleware('permission:view-availability')->group(function () {
            Route::get('/availability', [AvailabilityController::class, 'index'])->name('availability');
        });

        Route::middleware('permission:view-reservations|manage-reservations')->group(function () {
            Route::resource('reservations', ReservationController::class);
        });

        Route::middleware('permission:view-guests|manage-guests')->group(function () {
            Route::resource('guests', GuestController::class)->except(['show']);
        });

        Route::middleware('permission:view-rooms|manage-rooms|manage-room-status')->group(function () {
            Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
            Route::middleware('permission:manage-rooms')->group(function () {
                Route::get('/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
                Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
            });
            Route::middleware('permission:manage-rooms|manage-room-status')->group(function () {
                Route::get('/rooms/{room}/edit', [RoomController::class, 'edit'])->name('rooms.edit');
                Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
            });
            Route::middleware('permission:manage-rooms')->group(function () {
                Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
            });
        });

        Route::middleware('permission:view-rooms|manage-room-types')->group(function () {
            Route::get('/room-types', [RoomTypeController::class, 'index'])->name('room-types.index');
            Route::middleware('permission:manage-room-types')->group(function () {
                Route::get('/room-types/create', [RoomTypeController::class, 'create'])->name('room-types.create');
                Route::post('/room-types', [RoomTypeController::class, 'store'])->name('room-types.store');
                Route::get('/room-types/{room_type}/edit', [RoomTypeController::class, 'edit'])->name('room-types.edit');
                Route::put('/room-types/{room_type}', [RoomTypeController::class, 'update'])->name('room-types.update');
                Route::delete('/room-types/{room_type}', [RoomTypeController::class, 'destroy'])->name('room-types.destroy');
            });
        });

        Route::middleware('permission:view-reservations|manage-rate-plans')->group(function () {
            Route::get('/rate-plans', [RatePlanController::class, 'index'])->name('rate-plans.index');
            Route::middleware('permission:manage-rate-plans')->group(function () {
                Route::get('/rate-plans/create', [RatePlanController::class, 'create'])->name('rate-plans.create');
                Route::post('/rate-plans', [RatePlanController::class, 'store'])->name('rate-plans.store');
                Route::get('/rate-plans/{rate_plan}/edit', [RatePlanController::class, 'edit'])->name('rate-plans.edit');
                Route::put('/rate-plans/{rate_plan}', [RatePlanController::class, 'update'])->name('rate-plans.update');
                Route::delete('/rate-plans/{rate_plan}', [RatePlanController::class, 'destroy'])->name('rate-plans.destroy');
            });
        });

        Route::middleware('permission:manage-users')->group(function () {
            Route::resource('users', UserController::class)->except(['show']);
        });

        Route::middleware('permission:view-reports|view-fb-reports')->group(function () {
            Route::get('/reports', ReportController::class)->name('reports');
            Route::get('/reports/fb-revenue', [FbReportController::class, 'index'])->name('reports.fb-revenue');
        });

        Route::middleware('permission:view-debts')->group(function () {
            Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
        });

        Route::middleware('permission:view-reservations')->group(function () {
            Route::get('/booked-list', [BookedListController::class, 'index'])->name('booked-list.index');
            Route::get('/time-left', [TimeLeftController::class, 'index'])->name('time-left.index');
        });

        Route::middleware('permission:view-purchases|manage-purchases')->group(function () {
            Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
            Route::middleware('permission:manage-purchases')->group(function () {
                Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
                Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
                Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
            });
            Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
        });

        Route::middleware('permission:view-expenditures|manage-expenditures')->group(function () {
            Route::get('/expenditures', [ExpenditureController::class, 'index'])->name('expenditures.index');
            Route::middleware('permission:manage-expenditures')->group(function () {
                Route::get('/expenditures/create', [ExpenditureController::class, 'create'])->name('expenditures.create');
                Route::post('/expenditures', [ExpenditureController::class, 'store'])->name('expenditures.store');
                Route::get('/expenditures/{expenditure}/edit', [ExpenditureController::class, 'edit'])->name('expenditures.edit');
                Route::put('/expenditures/{expenditure}', [ExpenditureController::class, 'update'])->name('expenditures.update');
                Route::delete('/expenditures/{expenditure}', [ExpenditureController::class, 'destroy'])->name('expenditures.destroy');
            });
        });

        Route::middleware('permission:view-ancillary-services|manage-ancillary-services')->group(function () {
            Route::get('/ancillary-services', [AncillaryServiceController::class, 'index'])->name('ancillary-services.index');
            Route::middleware('permission:manage-ancillary-services')->group(function () {
                Route::get('/ancillary-services/create', [AncillaryServiceController::class, 'create'])->name('ancillary-services.create');
                Route::post('/ancillary-services', [AncillaryServiceController::class, 'store'])->name('ancillary-services.store');
                Route::get('/ancillary-services/{ancillaryService}/edit', [AncillaryServiceController::class, 'edit'])->name('ancillary-services.edit');
                Route::put('/ancillary-services/{ancillaryService}', [AncillaryServiceController::class, 'update'])->name('ancillary-services.update');
                Route::delete('/ancillary-services/{ancillaryService}', [AncillaryServiceController::class, 'destroy'])->name('ancillary-services.destroy');
            });
            Route::middleware('permission:post-folio-charges')->group(function () {
                Route::get('/ancillary-services/charge', [AncillaryServiceController::class, 'chargeForm'])->name('ancillary-services.charge');
                Route::post('/ancillary-services/charge', [AncillaryServiceController::class, 'charge'])->name('ancillary-services.charge.store');
            });
        });

        Route::middleware('permission:view-damage|manage-damage')->group(function () {
            Route::get('/damages', [RoomDamageController::class, 'index'])->name('damages.index');
            Route::middleware('permission:manage-damage')->group(function () {
                Route::get('/damages/create', [RoomDamageController::class, 'create'])->name('damages.create');
                Route::post('/damages', [RoomDamageController::class, 'store'])->name('damages.store');
                Route::get('/damages/{damage}/edit', [RoomDamageController::class, 'edit'])->name('damages.edit');
                Route::put('/damages/{damage}', [RoomDamageController::class, 'update'])->name('damages.update');
                Route::delete('/damages/{damage}', [RoomDamageController::class, 'destroy'])->name('damages.destroy');
                Route::post('/damages/{damage}/resolve', [RoomDamageController::class, 'resolve'])->name('damages.resolve');
            });
        });

        Route::middleware('permission:view-outlets|manage-outlets')->group(function () {
            Route::get('/outlets', [OutletController::class, 'index'])->name('outlets.index');
            Route::middleware('permission:manage-outlets')->group(function () {
                Route::get('/outlets/create', [OutletController::class, 'create'])->name('outlets.create');
                Route::post('/outlets', [OutletController::class, 'store'])->name('outlets.store');
                Route::get('/outlets/{outlet}/edit', [OutletController::class, 'edit'])->name('outlets.edit');
                Route::put('/outlets/{outlet}', [OutletController::class, 'update'])->name('outlets.update');
                Route::delete('/outlets/{outlet}', [OutletController::class, 'destroy'])->name('outlets.destroy');
            });
        });

        Route::middleware('permission:view-orders')->group(function () {
            Route::get('/restaurant', [RestaurantController::class, 'index'])->name('restaurant.index');
            Route::get('/bar', [BarController::class, 'index'])->name('bar.index');
            Route::get('/lounge', [LoungeController::class, 'index'])->name('lounge.index');
        });

        Route::middleware('permission:view-till|manage-till')->group(function () {
            Route::get('/till', [TillController::class, 'index'])->name('till.index');
            Route::middleware('permission:manage-till')->group(function () {
                Route::get('/till/create', [TillController::class, 'create'])->name('till.create');
                Route::post('/till', [TillController::class, 'store'])->name('till.store');
                Route::get('/till/{till}/close', [TillController::class, 'closeForm'])->name('till.close.form');
                Route::post('/till/{till}/close', [TillController::class, 'close'])->name('till.close');
            });
        });

        Route::middleware('permission:view-menu|manage-menu')->group(function () {
            Route::get('/menu-categories', [MenuCategoryController::class, 'index'])->name('menu-categories.index');
            Route::middleware('permission:manage-menu')->group(function () {
                Route::get('/menu-categories/create', [MenuCategoryController::class, 'create'])->name('menu-categories.create');
                Route::post('/menu-categories', [MenuCategoryController::class, 'store'])->name('menu-categories.store');
                Route::get('/menu-categories/{menuCategory}/edit', [MenuCategoryController::class, 'edit'])->name('menu-categories.edit');
                Route::put('/menu-categories/{menuCategory}', [MenuCategoryController::class, 'update'])->name('menu-categories.update');
                Route::delete('/menu-categories/{menuCategory}', [MenuCategoryController::class, 'destroy'])->name('menu-categories.destroy');
            });
            Route::resource('menu-items', MenuItemController::class)->except(['show']);
        });

        Route::middleware('permission:view-inventory|manage-inventory')->group(function () {
            Route::resource('stock-items', StockItemController::class)->except(['show']);
        });
    });
});

Route::middleware([
    'api',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->prefix('api/v1')->name('api.v1.')->group(function () {

    Route::get('/health', function () {
        return response()->json([
            'data' => [
                'app' => config('app.name'),
                'tenant' => tenant('id'),
                'status' => 'ok',
                'time' => now()->toIso8601String(),
            ],
            'meta' => [
                'request_id' => (string) \Illuminate\Support\Str::uuid(),
            ],
        ]);
    })->name('health');

    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::get('/availability', [ApiAvailabilityController::class, 'index'])
        ->name('availability.index');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        Route::middleware('permission:manage-users')->group(function () {
            Route::get('/users', [ApiUserController::class, 'index'])->name('users.index');
        });

        Route::middleware('permission:view-guests|manage-guests')->group(function () {
            Route::get('/guests', [ApiGuestController::class, 'index'])->name('guests.index');
            Route::get('/guests/{guest}', [ApiGuestController::class, 'show'])->name('guests.show');
        });

        Route::middleware('permission:manage-guests')->group(function () {
            Route::post('/guests', [ApiGuestController::class, 'store'])->name('guests.store');
            Route::patch('/guests/{guest}', [ApiGuestController::class, 'update'])->name('guests.update');
        });

        Route::middleware('permission:view-rooms|manage-room-status')->group(function () {
            Route::get('/rooms', [ApiRoomController::class, 'index'])->name('rooms.index');
            Route::get('/rooms/{room}', [ApiRoomController::class, 'show'])->name('rooms.show');
        });

        Route::middleware('permission:manage-room-status')->group(function () {
            Route::patch('/rooms/{room}/status', [ApiRoomController::class, 'updateStatus'])
                ->name('rooms.status');
        });

        Route::middleware('permission:view-availability')->group(function () {
            Route::get('/room-types', [ApiRoomTypeController::class, 'index'])->name('room-types.index');
            Route::get('/room-types/{roomType}', [ApiRoomTypeController::class, 'show'])->name('room-types.show');
        });

        Route::middleware('permission:view-folios|post-folio-charges')->group(function () {
            Route::get('/folios/{folio}', [FolioController::class, 'show'])->name('folios.show');
        });

        Route::middleware('permission:post-folio-charges')->group(function () {
            Route::post('/folios/{folio}/transactions', [FolioController::class, 'postCharge'])
                ->name('folios.transactions.store');
        });

        Route::middleware('permission:view-outlets')->group(function () {
            Route::get('/outlets', [ApiOutletController::class, 'index'])->name('outlets.index');
            Route::get('/outlets/{outlet}', [ApiOutletController::class, 'show'])->name('outlets.show');
            Route::get('/outlets/{outlet}/tables', [ApiOutletController::class, 'tables'])->name('outlets.tables');
            Route::get('/outlets/{outlet}/menu', [ApiOutletController::class, 'menu'])->name('outlets.menu');
            Route::get('/outlets/{outlet}/orders', [ApiOrderController::class, 'index'])->name('outlets.orders.index');
            Route::post('/outlets/{outlet}/orders', [ApiOrderController::class, 'store'])->name('outlets.orders.store');
            Route::get('/outlets/{outlet}/tabs', [BarTabController::class, 'index'])->name('outlets.tabs.index');
            Route::post('/outlets/{outlet}/tabs', [BarTabController::class, 'store'])->name('outlets.tabs.store');
        });

        Route::middleware('permission:view-orders')->group(function () {
            Route::get('/orders/{order}', [ApiOrderController::class, 'show'])->name('orders.show');
        });

        Route::middleware('permission:manage-orders')->group(function () {
            Route::post('/orders/{order}/items', [ApiOrderController::class, 'addItems'])->name('orders.items.store');
            Route::post('/orders/{order}/fire', [ApiOrderController::class, 'fire'])->name('orders.fire');
            Route::post('/orders/{order}/post-to-folio', [ApiOrderController::class, 'postToFolio'])->name('orders.post-to-folio');
            Route::post('/orders/{order}/cash-payment', [ApiOrderController::class, 'cashPayment'])->name('orders.cash-payment');
        });

        Route::prefix('tills')->name('tills.')->group(function () {
            Route::middleware('permission:manage-till')->group(function () {
                Route::post('/open', [ApiTillController::class, 'open'])->name('open');
                Route::get('/active', [ApiTillController::class, 'active'])->name('active');
                Route::post('/{till}/close', [ApiTillController::class, 'close'])->name('close');
            });
            Route::middleware('permission:view-till')->group(function () {
                Route::get('/history', [ApiTillController::class, 'history'])->name('history');
            });
        });

        Route::middleware('permission:view-inventory|manage-inventory')->group(function () {
            Route::get('/inventory/stock-items', [ApiStockItemController::class, 'index'])->name('inventory.stock-items.index');
            Route::post('/inventory/stock-items', [ApiStockItemController::class, 'store'])->name('inventory.stock-items.store');
            Route::patch('/inventory/stock-items/{stockItem}', [ApiStockItemController::class, 'update'])->name('inventory.stock-items.update');
            Route::delete('/inventory/stock-items/{stockItem}', [ApiStockItemController::class, 'destroy'])->name('inventory.stock-items.destroy');
        });

        Route::middleware('permission:manage-menu')->group(function () {
            Route::post('/outlets/{outlet}/menu/items', [ApiMenuItemController::class, 'store'])->name('outlets.menu.items.store');
            Route::patch('/menu-items/{menuItem}', [ApiMenuItemController::class, 'update'])->name('menu-items.update');
            Route::delete('/menu-items/{menuItem}', [ApiMenuItemController::class, 'destroy'])->name('menu-items.destroy');
            Route::post('/menu-items/{menuItem}/toggle', [ApiMenuItemController::class, 'toggle'])->name('menu-items.toggle');
        });

        Route::middleware('permission:view-debts')->group(function () {
            Route::get('/reports/debts', [ApiReportController::class, 'debts'])->name('reports.debts');
        });

        Route::middleware('permission:view-fb-reports')->group(function () {
            Route::get('/reports/fb-revenue', [ApiReportController::class, 'fbRevenue'])->name('reports.fb-revenue');
        });

        Route::prefix('reservations')->name('reservations.')->group(function () {
            Route::middleware('permission:view-reservations|manage-reservations')->group(function () {
                Route::get('/', [ApiReservationController::class, 'index'])->name('index');
                Route::get('/{reservation}', [ApiReservationController::class, 'show'])->name('show');
            });

            Route::middleware('permission:manage-reservations')->group(function () {
                Route::post('/', [ApiReservationController::class, 'store'])->name('store');
                Route::patch('/{reservation}', [ApiReservationController::class, 'update'])->name('update');
                Route::delete('/{reservation}', [ApiReservationController::class, 'destroy'])->name('destroy');
            });

            Route::middleware('permission:check-in-guests')->group(function () {
                Route::post('/{reservation}/check-in', [ApiReservationController::class, 'checkIn'])
                    ->name('check-in');
            });

            Route::middleware('permission:check-out-guests')->group(function () {
                Route::post('/{reservation}/check-out', [ApiReservationController::class, 'checkOut'])
                    ->name('check-out');
            });
        });
    });
});
