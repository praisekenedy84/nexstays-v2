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
use App\Http\Controllers\Web\FolioController as WebFolioController;
use App\Http\Controllers\Api\V1\HBMS\GuestController as ApiGuestController;
use App\Http\Controllers\Api\V1\HBMS\ReservationController as ApiReservationController;
use App\Http\Controllers\Api\V1\HBMS\RoomController as ApiRoomController;
use App\Http\Controllers\Api\V1\HBMS\RoomTypeController as ApiRoomTypeController;
use App\Http\Controllers\Api\V1\UserController as ApiUserController;
use App\Http\Controllers\Web\AuthController as WebAuthController;
use App\Http\Controllers\Web\AvailabilityController;
use App\Http\Controllers\Web\BarController;
use App\Http\Controllers\Web\FbOrderController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\GuestController;
use App\Http\Controllers\Web\MenuCategoryController;
use App\Http\Controllers\Web\MenuItemController;
use App\Http\Controllers\Web\RatePlanController;
use App\Http\Controllers\Web\RoomTypeController;
use App\Http\Controllers\Web\StockItemController;
use App\Http\Controllers\Web\LoungeController;
use App\Http\Controllers\Web\OutletController;
use App\Http\Controllers\Web\OutletTableController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\OccupancyReportController;
use App\Http\Controllers\Web\PaymentSummaryReportController;
use App\Http\Controllers\Web\RoomReservationReportController;
use App\Http\Controllers\Web\RoomPaymentsAccountingReportController;
use App\Http\Controllers\Web\ReservationController;
use App\Http\Controllers\Web\ReservationSettingsController;
use App\Http\Controllers\Web\RestaurantController;
use App\Http\Controllers\Web\RoomController;
use App\Http\Controllers\Web\TillController;
use App\Http\Controllers\Web\AncillaryServiceController;
use App\Http\Controllers\Web\BookedListController;
use App\Http\Controllers\Web\DebtController;
use App\Http\Controllers\Web\ExpenditureController;
use App\Http\Controllers\Web\FbReportController;
use App\Http\Controllers\Web\PurchaseController;
use App\Http\Controllers\Web\FinanceSettingsController;
use App\Http\Controllers\Web\ReportDeliverySettingsController;
use App\Http\Controllers\Web\RoomDamageController;
use App\Http\Controllers\Web\TimeLeftController;
use App\Http\Controllers\Web\PosController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\ShiftSummaryController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Api\V1\ReportController as ApiReportController;
use App\Http\Middleware\InitializeTenancyBySession;
use App\Http\Middleware\InitializeTenancyByToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web — staff dashboard (single domain, tenant resolved from session)
|--------------------------------------------------------------------------
|
| Login initializes tenancy from property_code and stores tenant_id in the
| session. Every subsequent web request resolves the tenant via that session
| value — no subdomain required.
|
*/

Route::middleware(['web'])->name('tenant.')->group(function () {

    // Login: no tenancy needed yet, controller initializes it after property_code lookup.
    Route::get('/login', [WebAuthController::class, 'create'])->name('login');
    Route::post('/login', [WebAuthController::class, 'store'])->name('login.store');

    // Authenticated & tenant-scoped: tenancy is initialized first (from session),
    // then the auth guard can resolve the user from the tenant schema.
    Route::middleware([InitializeTenancyBySession::class, 'auth:web'])->group(function () {
        Route::get('/', fn () => redirect()->route('tenant.dashboard'));
        Route::post('/logout', [WebAuthController::class, 'destroy'])->name('logout');
        Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]))->name('csrf.token');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::middleware('permission:view-availability')->group(function () {
            Route::get('/availability', [AvailabilityController::class, 'index'])->name('availability');
        });

        Route::middleware('permission:view-reservations|manage-reservations')->group(function () {
            Route::post('/reservations/bulk-action', [ReservationController::class, 'bulkAction'])
                ->middleware('permission:manage-reservations')
                ->name('reservations.bulk-action');
            Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
                ->middleware('permission:manage-reservations')
                ->name('reservations.cancel');
            Route::post('/reservations/{reservation}/check-in', [ReservationController::class, 'checkIn'])
                ->middleware('permission:check-in-guests')
                ->name('reservations.check-in');
            Route::post('/reservations/{reservation}/check-out', [ReservationController::class, 'checkOut'])
                ->middleware('permission:check-out-guests')
                ->name('reservations.check-out');
            Route::post('/folios/{folio}/payments', [WebFolioController::class, 'postPayment'])
                ->middleware('permission:post-folio-charges')
                ->name('folios.payments.store');
            Route::post('/reservations/{reservation}/overstay/charge', [ReservationController::class, 'postOverstayCharge'])
                ->middleware('permission:manage-reservations')
                ->name('reservations.overstay.charge');
            Route::post('/reservations/{reservation}/overstay/settle', [ReservationController::class, 'settleOverstay'])
                ->middleware('permission:post-folio-charges')
                ->name('reservations.overstay.settle');
            Route::post('/reservations/{reservation}/no-show', [ReservationController::class, 'noShow'])
                ->middleware('permission:manage-reservations')
                ->name('reservations.no-show');
            Route::get('/reservations/{reservation}/ticket', [ReservationController::class, 'ticket'])
                ->name('reservations.ticket');
            Route::get('/reservations/available-room-types', [ReservationController::class, 'availableRoomTypes'])
                ->middleware('permission:manage-reservations')
                ->name('reservations.available-room-types');
            Route::get('/reservations/available-rooms', [ReservationController::class, 'availableRooms'])
                ->middleware('permission:manage-reservations')
                ->name('reservations.available-rooms');
            Route::get('/reservations/settings', [ReservationSettingsController::class, 'edit'])
                ->middleware('permission:manage-reservations')
                ->name('reservations.settings.edit');
            Route::put('/reservations/settings', [ReservationSettingsController::class, 'update'])
                ->middleware('permission:manage-reservations')
                ->name('reservations.settings.update');
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
            Route::resource('users', UserController::class);
        });

        Route::middleware('permission:manage-roles')->group(function () {
            Route::get('/roles/matrix', [RoleController::class, 'matrix'])->name('roles.matrix');
            Route::post('/roles/{role}/clone', [RoleController::class, 'clone'])->name('roles.clone');
            Route::resource('roles', RoleController::class)->except(['show']);
            Route::get('/finance/settings', [FinanceSettingsController::class, 'edit'])->name('finance.settings.edit');
            Route::put('/finance/settings', [FinanceSettingsController::class, 'update'])->name('finance.settings.update');
            Route::get('/finance/payment-methods', [FinanceSettingsController::class, 'paymentMethodsEdit'])->name('finance.payment-methods.edit');
            Route::put('/finance/payment-methods', [FinanceSettingsController::class, 'paymentMethodsUpdate'])->name('finance.payment-methods.update');
            Route::get('/finance/fb-settings', [FinanceSettingsController::class, 'fbSettingsEdit'])->name('finance.fb-settings.edit');
            Route::put('/finance/fb-settings', [FinanceSettingsController::class, 'fbSettingsUpdate'])->name('finance.fb-settings.update');
        });

        Route::middleware('permission:view-reports|view-fb-reports|view-reservations')->group(function () {
            Route::get('/reports', ReportController::class)->name('reports');
            Route::get('/reports/fb-revenue', [FbReportController::class, 'index'])->name('reports.fb-revenue');
            Route::get('/reports/fb-revenue/export', [FbReportController::class, 'exportRevenueCsv'])->name('reports.fb-revenue.export');
            Route::get('/reports/fb-revenue/export-excel', [FbReportController::class, 'exportRevenueExcel'])->name('reports.fb-revenue.export-excel');
            Route::get('/reports/fb-revenue/export-pdf', [FbReportController::class, 'exportRevenuePdf'])->name('reports.fb-revenue.export-pdf');
            Route::get('/reports/fb-profitability', [FbReportController::class, 'profitability'])->name('reports.fb-profitability');
            Route::get('/reports/fb-profitability/export', [FbReportController::class, 'exportProfitabilityCsv'])->name('reports.fb-profitability.export');
            Route::get('/reports/fb-profitability/export-excel', [FbReportController::class, 'exportProfitabilityExcel'])->name('reports.fb-profitability.export-excel');
            Route::get('/reports/fb-profitability/export-pdf', [FbReportController::class, 'exportProfitabilityPdf'])->name('reports.fb-profitability.export-pdf');
        });
        Route::middleware('permission:manage-reservations')->group(function () {
            Route::put('/reports/delivery-settings', [ReportDeliverySettingsController::class, 'update'])
                ->name('reports.delivery-settings.update');
            Route::post('/reports/delivery-settings/send-now', [ReportDeliverySettingsController::class, 'sendNow'])
                ->name('reports.delivery-settings.send-now');
        });

        Route::middleware('permission:view-reports|view-reservations')->group(function () {
            Route::get('/reports/room-reservations', [RoomReservationReportController::class, 'index'])
                ->name('reports.room-reservations');
            Route::get('/reports/room-reservations/export', [RoomReservationReportController::class, 'exportCsv'])
                ->name('reports.room-reservations.export');
            Route::get('/reports/room-reservations/export-excel', [RoomReservationReportController::class, 'exportExcel'])
                ->name('reports.room-reservations.export-excel');
            Route::get('/reports/room-reservations/export-pdf', [RoomReservationReportController::class, 'exportPdf'])
                ->name('reports.room-reservations.export-pdf');
            Route::get('/reports/room-payments-accounting', [RoomPaymentsAccountingReportController::class, 'index'])
                ->name('reports.room-payments-accounting');
            Route::get('/reports/room-payments-accounting/export', [RoomPaymentsAccountingReportController::class, 'exportCsv'])
                ->name('reports.room-payments-accounting.export');
            Route::get('/reports/room-payments-accounting/export-excel', [RoomPaymentsAccountingReportController::class, 'exportExcel'])
                ->name('reports.room-payments-accounting.export-excel');
            Route::get('/reports/room-payments-accounting/export-pdf', [RoomPaymentsAccountingReportController::class, 'exportPdf'])
                ->name('reports.room-payments-accounting.export-pdf');
            Route::get('/reports/occupancy', [OccupancyReportController::class, 'index'])
                ->name('reports.occupancy');
            Route::get('/reports/occupancy/export', [OccupancyReportController::class, 'exportCsv'])
                ->name('reports.occupancy.export');
            Route::get('/reports/occupancy/export-excel', [OccupancyReportController::class, 'exportExcel'])
                ->name('reports.occupancy.export-excel');
            Route::get('/reports/occupancy/export-pdf', [OccupancyReportController::class, 'exportPdf'])
                ->name('reports.occupancy.export-pdf');
            Route::get('/reports/payment-summary', [PaymentSummaryReportController::class, 'index'])
                ->name('reports.payment-summary');
            Route::get('/reports/payment-summary/export', [PaymentSummaryReportController::class, 'exportCsv'])
                ->name('reports.payment-summary.export');
            Route::get('/reports/payment-summary/export-excel', [PaymentSummaryReportController::class, 'exportExcel'])
                ->name('reports.payment-summary.export-excel');
            Route::get('/reports/payment-summary/export-pdf', [PaymentSummaryReportController::class, 'exportPdf'])
                ->name('reports.payment-summary.export-pdf');
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
                Route::post('/purchases/{purchase}/order', [PurchaseController::class, 'markOrdered'])->name('purchases.mark-ordered');
                Route::get('/purchases/{purchase}/receive', [PurchaseController::class, 'receiveForm'])->name('purchases.receive-form');
                Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
            });
            Route::get('/purchases/{purchase}/print', [PurchaseController::class, 'print'])->name('purchases.print');
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
                Route::post('/folios/{folio}/write-off', [WebFolioController::class, 'writeOff'])->name('folios.write-off');
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

                // Table management (nested under outlet)
                Route::get('/outlets/{outlet}/tables', [OutletTableController::class, 'index'])->name('outlets.tables.index');
                Route::post('/outlets/{outlet}/tables', [OutletTableController::class, 'store'])->name('outlets.tables.store');
                Route::put('/outlets/{outlet}/tables/{table}', [OutletTableController::class, 'update'])->name('outlets.tables.update');
                Route::patch('/outlets/{outlet}/tables/{table}/status', [OutletTableController::class, 'updateStatus'])->name('outlets.tables.status');
                Route::delete('/outlets/{outlet}/tables/{table}', [OutletTableController::class, 'destroy'])->name('outlets.tables.destroy');
            });
        });

        Route::middleware('permission:view-orders')->group(function () {
            Route::get('/restaurant', [RestaurantController::class, 'index'])->name('restaurant.index');
            Route::get('/bar', [BarController::class, 'index'])->name('bar.index');
            Route::get('/lounge', [LoungeController::class, 'index'])->name('lounge.index');
            Route::get('/fb/orders', [FbOrderController::class, 'index'])->name('fb.orders.index');
            Route::get('/fb/orders/{order}', [FbOrderController::class, 'show'])->name('fb.orders.show');

            // POS order management
            Route::get('/pos/orders/{order}', [PosController::class, 'manage'])->name('pos.manage');
            Route::get('/shift/mine', [ShiftSummaryController::class, 'mine'])->name('shift.mine');
            Route::get('/shift/all', [ShiftSummaryController::class, 'all'])->name('shift.all');
        });

        Route::middleware('permission:manage-orders')->group(function () {
            Route::delete('/fb/orders/{order}', [FbOrderController::class, 'destroy'])->name('fb.orders.destroy');
            Route::post('/pos/{outlet}/orders', [PosController::class, 'createOrder'])->name('pos.orders.create');
            Route::post('/pos/orders/{order}/items', [PosController::class, 'addItem'])->name('pos.orders.add-item');
            Route::post('/pos/orders/{order}/items/{item}/void', [PosController::class, 'voidItem'])->name('pos.orders.void-item');
            Route::post('/pos/orders/{order}/fire', [PosController::class, 'fire'])->name('pos.orders.fire');
            Route::post('/pos/orders/{order}/settle-cash', [PosController::class, 'settleCash'])->name('pos.orders.settle-cash');
            Route::post('/pos/orders/{order}/settle-folio', [PosController::class, 'settleFollio'])->name('pos.orders.settle-folio');
            Route::post('/pos/orders/{order}/settle-direct', [PosController::class, 'settleDirectPayment'])->name('pos.orders.settle-direct');
            Route::post('/pos/orders/{order}/cancel', [PosController::class, 'cancelOrder'])->name('pos.orders.cancel');
        });

        Route::middleware('permission:view-orders')->group(function () {
            Route::get('/pos/orders/{order}/receipt', [PosController::class, 'receipt'])->name('pos.orders.receipt');
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
            Route::get('/menu-items/json', [MenuItemController::class, 'json'])->name('menu-items.json');
            Route::post('/menu-items/sync-bar-inventory', [MenuItemController::class, 'syncBarInventory'])
                ->middleware('permission:manage-menu|manage-inventory')
                ->name('menu-items.sync-bar-inventory');
            Route::resource('menu-items', MenuItemController::class)->except(['show']);
        });

        Route::middleware('permission:view-inventory|manage-inventory')->group(function () {
            Route::get('/stock-items/json', [StockItemController::class, 'json'])->name('stock-items.json');
            Route::get('/stock-items/{stockItem}/restock', [StockItemController::class, 'restockForm'])
                ->middleware('permission:manage-inventory')
                ->name('stock-items.restock-form');
            Route::post('/stock-items/{stockItem}/restock', [StockItemController::class, 'restock'])
                ->middleware('permission:manage-inventory')
                ->name('stock-items.restock');
            Route::post('/stock-items/sync', [StockItemController::class, 'sync'])
                ->middleware('permission:manage-inventory')
                ->name('stock-items.sync');
            Route::resource('stock-items', StockItemController::class)->except(['show']);
        });

        // Notifications (all authenticated staff)
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    });
});

/*
|--------------------------------------------------------------------------
| API v1 — tenant-scoped REST (single domain, tenant from token or header)
|--------------------------------------------------------------------------
|
| Login: POST /api/v1/auth/login — accepts property_code, username, password.
|        Controller initializes tenancy, returns token + tenant_id.
|
| All other authenticated routes: InitializeTenancyByToken reads the tenant_id
|        stored on the Sanctum token (central PAT table), initializes tenancy,
|        then auth:sanctum authenticates the user from the tenant schema.
|
| Public tenant endpoints (e.g. availability): pass X-Tenant-Id header.
|
*/

Route::middleware(['api'])->prefix('api/v1')->name('api.v1.')->group(function () {

    Route::get('/health', function () {
        return response()->json([
            'data' => [
                'app' => config('app.name'),
                'tenant' => tenancy()->initialized ? tenant('id') : null,
                'status' => 'ok',
                'time' => now()->toIso8601String(),
            ],
            'meta' => [
                'request_id' => (string) \Illuminate\Support\Str::uuid(),
            ],
        ]);
    })->name('health');

    // Login resolves the tenant from property_code inside the controller.
    Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    // Tenant context required (token → tenant_id, or X-Tenant-Id header for public endpoints).
    Route::middleware([InitializeTenancyByToken::class])->group(function () {

        // Public: guest booking engine uses X-Tenant-Id header.
        Route::get('/availability', [ApiAvailabilityController::class, 'index'])
            ->name('availability.index');

        // Authenticated tenant routes.
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

            Route::middleware('permission:manage-rooms|manage-room-status')->group(function () {
                Route::patch('/rooms/{room}', [ApiRoomController::class, 'update'])->name('rooms.update');
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
                Route::post('/folios/{folio}/payments', [FolioController::class, 'postPayment'])
                    ->name('folios.payments.store');
                Route::post('/folios/{folio}/write-off', [FolioController::class, 'writeOff'])
                    ->name('folios.write-off');
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
                Route::patch('/orders/{order}/items/status', [ApiOrderController::class, 'updateItemStatus'])->name('orders.items.status');
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

            Route::middleware('permission:view-reports|view-reservations')->group(function () {
                Route::get('/reports/room-reservations', [ApiReportController::class, 'roomReservations'])
                    ->name('reports.room-reservations');
                Route::get('/reports/room-payments-accounting', [ApiReportController::class, 'roomPaymentsAccounting'])
                    ->name('reports.room-payments-accounting');
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

                Route::middleware('permission:manage-reservations')->group(function () {
                    Route::post('/{reservation}/overstay/charge', [ApiReservationController::class, 'postOverstayCharge'])
                        ->name('overstay.charge');
                });

                Route::middleware('permission:post-folio-charges')->group(function () {
                    Route::post('/{reservation}/overstay/settle', [ApiReservationController::class, 'settleOverstay'])
                        ->name('overstay.settle');
                });
            });
        });
    });
});
