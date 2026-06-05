<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Till\Services\TillSessionService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LoungeController extends Controller
{
    public function __construct(private readonly TillSessionService $tillService) {}

    public function index(): View
    {
        $outlet = Outlet::query()->where('type', 'lounge')->where('is_active', true)->firstOrFail();

        $allOrders = Order::query()
            ->with(['table', 'items', 'waiter'])
            ->where('outlet_id', $outlet->id)
            ->whereNotIn('status', ['closed', 'voided'])
            ->latest('opened_at')
            ->get();

        $activeTill = $this->tillService->activeForOutlet($outlet->id);

        $outletOrders = Order::query()
            ->with(['table', 'items', 'waiter', 'payments'])
            ->where('outlet_id', $outlet->id)
            ->where(function ($q) {
                $q->whereDate('opened_at', today())
                    ->orWhereDate('closed_at', today());
            })
            ->latest('opened_at')
            ->get();

        return view('modules.lounge.index', compact('outlet', 'allOrders', 'activeTill', 'outletOrders'));
    }
}
