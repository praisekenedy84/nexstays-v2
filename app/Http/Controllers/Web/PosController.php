<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\HBMS\Models\Folio;
use App\Domain\Restaurant\Services\OrderService;
use App\Domain\Shared\Models\MenuCategory;
use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Models\OrderItem;
use App\Domain\Shared\Models\Outlet;
use App\Domain\Shared\Services\FbSettingsService;
use App\Domain\Shared\Services\OrderAuthorizationService;
use App\Domain\Shared\Services\PaymentMethodSettingsService;
use App\Domain\Till\Services\TillSessionService;
use App\Http\Controllers\Controller;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly TillSessionService $tillService,
        private readonly FbSettingsService $fbSettings,
        private readonly PaymentMethodSettingsService $paymentMethodSettings,
        private readonly OrderAuthorizationService $orderAuth,
    ) {}

    public function createOrder(Request $request, Outlet $outlet): RedirectResponse
    {
        abort_unless($this->orderAuth->canCreate(auth()->user()), 403);

        $validated = $request->validate([
            'table_id'    => ['nullable', 'uuid', 'exists:outlet_tables,id'],
            'covers'      => ['required', 'integer', 'min:1', 'max:50'],
            'guest_label' => ['nullable', 'string', 'max:100'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $order = $this->orderService->create($outlet, [
                'table_id'    => $validated['table_id'] ?? null,
                'covers'      => $validated['covers'],
                'guest_label' => $validated['guest_label'] ?? null,
                'notes'       => $validated['notes'] ?? null,
                'waiter_id'   => Auth::id(),
            ]);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('tenant.pos.manage', $order)->with('success', 'Order created.');
    }

    public function manage(Order $order): View
    {
        abort_unless($this->orderAuth->canView(auth()->user(), $order), 403);

        $order->load([
            'outlet.menuCategories' => fn ($q) => $q->where('is_active', true)->orderBy('display_order'),
            'outlet.menuCategories.items' => fn ($q) => $q->where('is_available', true)->orderBy('sort_order'),
            'items.menuItem.category.outlet',
            'table',
            'waiter',
        ]);

        $beverageCategories = collect();
        if ($order->outlet->isRestaurant() || $order->outlet->isLounge()) {
            $barOutlet = Outlet::query()
                ->where('type', 'bar')
                ->where('is_active', true)
                ->first();

            if ($barOutlet !== null) {
                $beverageCategories = MenuCategory::query()
                    ->where('outlet_id', $barOutlet->id)
                    ->where('is_active', true)
                    ->with(['items' => fn ($q) => $q->where('is_available', true)->orderBy('sort_order')])
                    ->orderBy('display_order')
                    ->get();
            }
        }

        $activeTill     = $this->tillService->activeForOutlet($order->outlet_id);
        $fbSettings     = $this->fbSettings->all();
        $allowsDirect   = $this->fbSettings->allowsDirect();
        $allowsFolio    = $this->fbSettings->allowsFolio();
        $enabledMethods = $this->paymentMethodSettings->enabledMethods();

        $openFolios = $allowsFolio
            ? Folio::query()
                ->with(['reservation.guest', 'reservation.room'])
                ->where('status', 'open')
                ->get()
                ->sortBy(fn ($f) => ($f->reservation?->guest?->last_name ?? ''))
            : collect();

        return view('modules.pos.manage', compact(
            'order', 'activeTill', 'openFolios', 'beverageCategories',
            'allowsDirect', 'allowsFolio', 'enabledMethods'
        ));
    }

    public function addItem(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        abort_unless($this->orderAuth->canManage(auth()->user(), $order), 403);

        $validated = $request->validate([
            'menu_item_id' => ['required', 'uuid', 'exists:menu_items,id'],
            'quantity'     => ['required', 'integer', 'min:1', 'max:99'],
            'notes'        => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $order = $this->orderService->addItems($order, [[
                'menu_item_id' => $validated['menu_item_id'],
                'quantity'     => (int) $validated['quantity'],
                'notes'        => $validated['notes'] ?? null,
            ]]);
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Item added.',
                'panel' => view('modules.pos._order-panel', $this->orderPanelViewData($order))->render(),
            ]);
        }

        return back()->with('success', 'Item added.');
    }

    public function voidItem(Order $order, OrderItem $item): RedirectResponse
    {
        abort_unless($this->orderAuth->canManage(auth()->user(), $order), 403);

        try {
            $this->orderService->updateItemStatus($order, $item, 'voided', 'voided_by_waiter');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Item voided.');
    }

    public function fire(Order $order): RedirectResponse
    {
        abort_unless($this->orderAuth->canManage(auth()->user(), $order), 403);

        try {
            $this->orderService->fire($order);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order sent to kitchen.');
    }

    public function serve(Order $order): RedirectResponse
    {
        abort_unless($this->orderAuth->canManage(auth()->user(), $order), 403);

        try {
            $this->orderService->serve($order);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order served.');
    }

    public function settleCash(Request $request, Order $order): RedirectResponse
    {
        abort_unless($this->orderAuth->canManage(auth()->user(), $order), 403);

        $validated = $request->validate([
            'tendered' => ['required', 'numeric', 'min:0.01'],
        ]);

        $activeTill = $this->tillService->activeForOutlet($order->outlet_id);
        if ($activeTill === null) {
            return back()->with('error', 'No active till session for this outlet. Open a till first.');
        }

        $currency = config('nexstay.currency.default', 'TZS');
        $total    = Money::of((string) $order->total, $currency);
        $tendered = Money::of($validated['tendered'], $currency, null, RoundingMode::HALF_UP);

        try {
            $this->orderService->recordCashPayment($order, $total, $tendered, $activeTill);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.restaurant.index')
            ->with('success', "Order {$order->order_number} settled — cash TZS ".number_format($total->getAmount()->toFloat()).".");
    }

    public function settleFollio(Request $request, Order $order): RedirectResponse
    {
        abort_unless($this->orderAuth->canManage(auth()->user(), $order), 403);

        $validated = $request->validate([
            'folio_id' => ['required', 'uuid', 'exists:folios,id'],
        ]);

        $folio = Folio::findOrFail($validated['folio_id']);

        try {
            $this->orderService->postToFolio($order, $folio);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $guestName = trim(
            ($folio->reservation?->guest?->first_name ?? '').' '.
            ($folio->reservation?->guest?->last_name ?? '')
        ) ?: 'guest';

        return redirect()
            ->route('tenant.restaurant.index')
            ->with('success', "Order {$order->order_number} posted to {$guestName}'s folio.");
    }

    public function settleDirectPayment(Request $request, Order $order): RedirectResponse
    {
        abort_unless($this->orderAuth->canManage(auth()->user(), $order), 403);

        $enabledMethods = implode(',', $this->paymentMethodSettings->enabledMethods());

        $validated = $request->validate([
            'method'          => ['required', 'string', "in:{$enabledMethods}"],
            'transaction_ref' => ['nullable', 'string', 'max:100'],
            'tendered'        => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $currency   = config('nexstay.currency.default', 'TZS');
        $order->loadMissing('outlet');
        $total      = Money::of((string) $order->total, $currency);
        $method     = $validated['method'];
        $txRef      = $validated['transaction_ref'] ?? null;
        $tendered   = isset($validated['tendered'])
            ? Money::of($validated['tendered'], $currency, null, RoundingMode::HALF_UP)
            : null;

        $activeTill = ($method === 'cash')
            ? $this->tillService->activeForOutlet($order->outlet_id)
            : null;

        try {
            $this->orderService->recordDirectPayment($order, $method, $total, $txRef, $tendered, $activeTill);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $label = match ($method) {
            'cash'         => 'cash — '.config('nexstay.currency.default', 'TZS').' '.number_format($total->getAmount()->toFloat()),
            'mobile_money' => 'mobile money'.($txRef ? " (ref: {$txRef})" : ''),
            'card'         => 'card'.($txRef ? " (ref: {$txRef})" : ''),
            default        => $method,
        };

        $backRoute = match ($order->outlet->type) {
            'bar'    => route('tenant.bar.index'),
            'lounge' => route('tenant.lounge.index'),
            default  => route('tenant.restaurant.index'),
        };

        return redirect()->to($backRoute)
            ->with('success', "Order {$order->order_number} settled via {$label}.");
    }

    public function cancelOrder(Order $order): RedirectResponse
    {
        abort_unless($this->orderAuth->canManage(auth()->user(), $order), 403);

        $backRoute = match ($order->outlet?->type) {
            'bar'    => route('tenant.bar.index'),
            'lounge' => route('tenant.lounge.index'),
            default  => route('tenant.restaurant.index'),
        };

        $wasClosed = $order->status === 'closed';

        try {
            $this->orderService->cancel($order);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = $wasClosed
            ? "Order {$order->order_number} has been voided and removed from sales reports."
            : "Order {$order->order_number} has been cancelled.";

        return back(fallback: $backRoute)
            ->with('success', $message);
    }

    public function receipt(Order $order): \Illuminate\View\View
    {
        abort_unless($this->orderAuth->canView(auth()->user(), $order), 403);

        $order->load([
            'outlet',
            'table',
            'waiter',
            'items' => fn ($q) => $q->where('status', '!=', 'voided')->with('menuItem'),
            'payments',
        ]);

        $payment = $order->payments->first();

        return view('modules.pos.receipt', compact('order', 'payment'));
    }

    /** @return array<string, mixed> */
    private function orderPanelViewData(Order $order): array
    {
        $order->load([
            'items.menuItem.category.outlet',
            'outlet',
            'table',
            'waiter',
        ]);

        $backRoute = match ($order->outlet->type) {
            'bar'    => route('tenant.bar.index'),
            'lounge' => route('tenant.lounge.index'),
            default  => route('tenant.restaurant.index'),
        };

        $currency = config('nexstay.currency.default', 'TZS');
        $isOpen = $order->isOpen();
        $canManage = $this->orderAuth->canManage(auth()->user(), $order);
        $activeTill = $this->tillService->activeForOutlet($order->outlet_id);
        $allowsDirect = $this->fbSettings->allowsDirect();
        $allowsFolio = $this->fbSettings->allowsFolio();
        $enabledMethods = $this->paymentMethodSettings->enabledMethods();

        $openFolios = $allowsFolio
            ? Folio::query()
                ->with(['reservation.guest', 'reservation.room'])
                ->where('status', 'open')
                ->get()
                ->sortBy(fn ($f) => ($f->reservation?->guest?->last_name ?? ''))
            : collect();

        $showCash = $allowsDirect && in_array('cash', $enabledMethods, true);
        $showMobileMoney = $allowsDirect && in_array('mobile_money', $enabledMethods, true);
        $showCard = $allowsDirect && in_array('card', $enabledMethods, true);
        $showFolio = $allowsFolio;

        return compact(
            'order',
            'backRoute',
            'currency',
            'isOpen',
            'canManage',
            'activeTill',
            'openFolios',
            'showCash',
            'showMobileMoney',
            'showCard',
            'showFolio',
        );
    }
}
