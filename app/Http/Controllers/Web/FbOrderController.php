<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Restaurant\Actions\DeleteOrder;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Till\Models\Payment;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FbOrderController extends Controller
{
    public function __construct(
        private readonly DeleteOrder $deleteOrder
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('view-orders'), 403);

        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->startOfDay();
        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        $outletId = $request->input('outlet_id');
        $waiterId = $request->input('waiter_id');
        $status = $request->input('status');
        $search = trim((string) $request->query('search', ''));

        $baseQuery = Order::query()
            ->with(['outlet', 'table', 'waiter', 'items', 'payments'])
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('opened_at', [$from, $to])
                    ->orWhereBetween('closed_at', [$from, $to]);
            })
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->when($waiterId, fn ($q) => $q->where('waiter_id', $waiterId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where('order_number', 'ilike', "%{$search}%"))
            ->latest('opened_at');

        $allForSummary = (clone $baseQuery)->get();
        $summary = $this->buildSalesSummary($allForSummary);

        $orders = (clone $baseQuery)->paginate(30)->withQueryString();

        $outlets = Outlet::query()->where('is_active', true)->orderBy('name')->get();

        $waiterIds = Order::query()
            ->whereNotNull('waiter_id')
            ->whereBetween('opened_at', [now()->subMonths(3), now()])
            ->distinct()
            ->pluck('waiter_id');

        $waiters = User::query()
            ->whereIn('id', $waiterIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('modules.fb.orders.index', [
            'orders' => $orders,
            'summary' => $summary,
            'outlets' => $outlets,
            'waiters' => $waiters,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'outletId' => $outletId,
            'waiterId' => $waiterId,
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($request->user()?->can('view-orders'), 403);

        $order->load([
            'outlet',
            'table',
            'waiter',
            'folio.reservation.guest',
            'folio.reservation.room',
            'items.menuItem',
            'payments.receiver',
            'statusLogs' => fn ($q) => $q->limit(20),
        ]);

        $canManage = $request->user()?->can('manage-orders') ?? false;

        return view('modules.fb.orders.show', compact('order', 'canManage'));
    }

    public function destroy(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user()?->can('manage-orders'), 403);

        try {
            $this->deleteOrder->execute($order);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.fb.orders.index', $request->only(['from', 'to', 'outlet_id', 'waiter_id', 'status', 'search']))
            ->with('success', "Order {$order->order_number} permanently deleted.");
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     * @return array{
     *     cash: float,
     *     card: float,
     *     mobile_money: float,
     *     folio: float,
     *     total_closed: float,
     *     open_value: float,
     *     count_open: int,
     *     count_closed: int,
     *     count_voided: int
     * }
     */
    private function buildSalesSummary(\Illuminate\Support\Collection $orders): array
    {
        $closedOrderIds = $orders->where('status', 'closed')->pluck('id');

        $payments = Payment::query()
            ->whereIn('order_id', $closedOrderIds)
            ->get();

        $cash = (float) $payments->where('method', 'cash')->sum('amount');
        $card = (float) $payments->where('method', 'card')->sum('amount');
        $mobile = (float) $payments->where('method', 'mobile_money')->sum('amount');

        $folio = (float) $orders
            ->where('status', 'closed')
            ->filter(fn (Order $o) => $o->folio_id !== null)
            ->sum('total');

        $openOrders = $orders->filter(fn (Order $o) => $o->isOpen());

        return [
            'cash' => $cash,
            'card' => $card,
            'mobile_money' => $mobile,
            'folio' => $folio,
            'total_closed' => $cash + $card + $mobile + $folio,
            'open_value' => (float) $openOrders->sum('total'),
            'count_open' => $openOrders->count(),
            'count_closed' => $orders->where('status', 'closed')->count(),
            'count_voided' => $orders->where('status', 'voided')->count(),
        ];
    }
}
