<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\HBMS\Models\Folio;
use App\Domain\Restaurant\Services\OrderService;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Shared\Services\OrderAuthorizationService;
use App\Domain\Till\Services\TillSessionService;
use App\Http\Controllers\Controller;
use App\Http\Concerns\RespondsWithJsonApi;
use App\Http\Requests\Api\V1\Orders\AddOrderItemsRequest;
use App\Http\Requests\Api\V1\Orders\CreateOrderRequest;
use App\Http\Requests\Api\V1\Orders\RecordOrderCashPaymentRequest;
use App\Http\Requests\Api\V1\Orders\PostOrderToFolioRequest;
use App\Http\Requests\Api\V1\Orders\UpdateOrderItemStatusRequest;
use App\Http\Resources\Api\V1\OrderResource;
use Brick\Money\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use RespondsWithJsonApi;

    public function __construct(
        private readonly OrderService $orderService,
        private readonly TillSessionService $tillSessionService,
        private readonly OrderAuthorizationService $orderAuth,
    ) {}

    public function index(Request $request, Outlet $outlet): JsonResponse
    {
        abort_unless($request->user()?->can('view-orders'), 403);

        $orders = Order::query()
            ->with(['items.menuItem', 'table'])
            ->where('outlet_id', $outlet->id)
            ->when(
                $request->boolean('active_only', true),
                fn ($q) => $q->whereNotIn('status', ['closed', 'voided'])
            )
            ->latest('opened_at')
            ->get();

        return $this->respond(OrderResource::collection($orders));
    }

    public function store(CreateOrderRequest $request, Outlet $outlet): JsonResponse
    {
        $order = $this->orderService->create(
            $outlet,
            $request->safe()->only(['table_id', 'folio_id', 'covers', 'guest_label', 'notes']),
            $request->validated('items', [])
        );

        return $this->respond(OrderResource::make($order), 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($this->orderAuth->canView($request->user(), $order), 403);
        $order->load(['items.menuItem', 'table', 'outlet', 'statusLogs']);

        return $this->respond(OrderResource::make($order));
    }

    public function addItems(AddOrderItemsRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderService->addItems($order, $request->validated('items'));

        return $this->respond(OrderResource::make($order));
    }

    public function fire(Request $request, Order $order): JsonResponse
    {
        abort_unless($this->orderAuth->canManage($request->user(), $order), 403);

        return $this->respond(OrderResource::make($this->orderService->fire($order)));
    }

    public function postToFolio(PostOrderToFolioRequest $request, Order $order): JsonResponse
    {
        $folio = Folio::query()->findOrFail($request->validated('folio_id'));

        return $this->respond(OrderResource::make($this->orderService->postToFolio($order, $folio)));
    }

    public function cashPayment(RecordOrderCashPaymentRequest $request, Order $order): JsonResponse
    {
        $currency = config('nexstay.currency.default', 'TZS');
        $order = $this->orderService->recalculate($order);
        $amount = Money::of((string) $order->total, $currency);
        $tendered = Money::of($request->validated('amount_tendered'), $currency);
        $tillSessionId = $request->validated('till_session_id');
        $session = $tillSessionId !== null
            ? $this->tillSessionService->activeForOutlet($order->outlet_id)
            : null;

        $payment = $this->orderService->recordCashPayment($order, $amount, $tendered, $session);

        return $this->respond([
            'payment_id' => $payment->id,
            'order' => OrderResource::make($order->fresh(['items.menuItem'])),
        ]);
    }

    public function updateItemStatus(UpdateOrderItemStatusRequest $request, Order $order): JsonResponse
    {
        $item = $order->items()->findOrFail($request->validated('order_item_id'));
        $order = $this->orderService->updateItemStatus(
            $order,
            $item,
            $request->validated('status'),
            $request->validated('reason')
        );

        return $this->respond(OrderResource::make($order->load('statusLogs')));
    }
}
