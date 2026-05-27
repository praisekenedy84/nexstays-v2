<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Till\Services\TillSessionService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function __construct(private readonly TillSessionService $tillService) {}

    public function index(): View
    {
        $outlet = Outlet::query()->where('type', 'restaurant')->where('is_active', true)->firstOrFail();

        $tables = $outlet->tables()->orderBy('table_number')->get();

        $myOrders = Order::query()
            ->with(['table', 'items', 'waiter'])
            ->where('outlet_id', $outlet->id)
            ->where('waiter_id', Auth::id())
            ->whereNotIn('status', ['closed', 'voided'])
            ->latest('opened_at')
            ->get();

        $allOrders = Order::query()
            ->with(['table', 'items', 'waiter'])
            ->where('outlet_id', $outlet->id)
            ->whereNotIn('status', ['closed', 'voided'])
            ->latest('opened_at')
            ->get();

        $activeTill = $this->tillService->activeForOutlet($outlet->id);

        return view('modules.restaurant.index', compact('outlet', 'tables', 'myOrders', 'allOrders', 'activeTill'));
    }
}
