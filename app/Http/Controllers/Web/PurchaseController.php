<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Inventory\Models\StockItem;
use App\Domain\Purchases\Actions\CreatePurchaseOrder;
use App\Domain\Purchases\Actions\ReceivePurchaseOrder;
use App\Domain\Purchases\Models\PurchaseOrder;
use App\Domain\Shared\Models\Outlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StorePurchaseOrderRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $orders = PurchaseOrder::query()
            ->with(['outlet', 'creator'])
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->input('department')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('modules.purchases.index', compact('orders'));
    }

    public function create(Request $request): View
    {
        return view('modules.purchases.form', [
            'stockItems' => StockItem::query()->orderBy('name')->get(),
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
            'department' => $request->input('department', 'kitchen'),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['lines'] = collect($data['lines'])
            ->filter(fn (array $line) => ! empty($line['stock_item_id']))
            ->values()
            ->all();

        if ($data['lines'] === []) {
            return back()->withInput()->with('error', 'Add at least one line item.');
        }

        $order = app(CreatePurchaseOrder::class)->execute(
            $data,
            $request->boolean('receive_now')
        );

        return redirect()
            ->route('tenant.purchases.show', $order)
            ->with('success', 'Purchase order created.');
    }

    public function show(PurchaseOrder $purchase): View
    {
        $purchase->load(['lines.stockItem', 'outlet', 'creator']);

        return view('modules.purchases.show', ['order' => $purchase]);
    }

    public function receive(PurchaseOrder $purchase): RedirectResponse
    {
        try {
            app(ReceivePurchaseOrder::class)->execute($purchase);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.purchases.show', $purchase)
            ->with('success', 'Stock received and inventory updated.');
    }
}
