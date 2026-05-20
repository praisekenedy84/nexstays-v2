<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\Outlet;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(): View
    {
        $outlet = Outlet::query()->where('type', 'restaurant')->where('is_active', true)->firstOrFail();

        $tables = $outlet->tables()->orderBy('table_number')->get();
        $orders = Order::query()
            ->with(['table', 'items'])
            ->where('outlet_id', $outlet->id)
            ->whereNotIn('status', ['closed', 'voided'])
            ->latest('opened_at')
            ->get();

        return view('modules.restaurant.index', compact('outlet', 'tables', 'orders'));
    }
}
