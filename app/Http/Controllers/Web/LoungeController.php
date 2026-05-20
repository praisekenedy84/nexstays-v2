<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\Outlet;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LoungeController extends Controller
{
    public function index(): View
    {
        $outlet = Outlet::query()->where('type', 'lounge')->where('is_active', true)->firstOrFail();

        $orders = Order::query()
            ->with(['items'])
            ->where('outlet_id', $outlet->id)
            ->whereNotIn('status', ['closed', 'voided'])
            ->latest('opened_at')
            ->get();

        return view('modules.lounge.index', compact('outlet', 'orders'));
    }
}
