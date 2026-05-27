<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

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
        $sort   = in_array($request->query('sort'), ['po_number', 'supplier_name', 'total_amount', 'created_at']) ? $request->query('sort') : 'created_at';
        $dir    = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));

        $orders = PurchaseOrder::query()
            ->with(['outlet', 'creator'])
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->input('department')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($search, fn ($q) => $q->where(fn ($inner) => $inner
                ->where('po_number', 'ilike', "%{$search}%")
                ->orWhere('supplier_name', 'ilike', "%{$search}%")))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('modules.purchases.index', compact('orders', 'sort', 'dir', 'search'));
    }

    public function create(Request $request): View
    {
        return view('modules.purchases.form', [
            'outlets'    => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
            'department' => $request->input('department', 'kitchen'),
            'order'      => null,
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['lines'])) {
            return back()->withInput()->with('error', 'Add at least one line item.');
        }

        $order = app(CreatePurchaseOrder::class)->execute(
            $data,
            $request->boolean('receive_now')
        );

        return redirect()
            ->route('tenant.purchases.show', $order)
            ->with('success', 'Purchase order '.$order->po_number.' created.');
    }

    public function show(PurchaseOrder $purchase): View
    {
        $purchase->load(['lines.stockItem', 'outlet', 'creator']);

        return view('modules.purchases.show', ['order' => $purchase]);
    }

    public function markOrdered(PurchaseOrder $purchase): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-purchases'), 403);

        if ($purchase->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be marked as ordered.');
        }

        $purchase->update(['status' => 'ordered', 'ordered_at' => now()]);

        return back()->with('success', 'Purchase order marked as ordered — awaiting delivery.');
    }

    public function receiveForm(PurchaseOrder $purchase): RedirectResponse|View
    {
        abort_unless(auth()->user()?->can('manage-purchases'), 403);

        if (! $purchase->isReceivable()) {
            return redirect()->route('tenant.purchases.show', $purchase)
                ->with('error', 'This purchase order cannot be received.');
        }

        $purchase->load(['lines.stockItem', 'outlet', 'creator']);

        return view('modules.purchases.receive', ['order' => $purchase]);
    }

    public function receive(Request $request, PurchaseOrder $purchase): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage-purchases'), 403);

        $validated = $request->validate([
            'lines'                       => ['required', 'array'],
            'lines.*.line_id'             => ['required', 'uuid'],
            'lines.*.qty_received'        => ['required', 'numeric', 'min:0'],
            'lines.*.actual_unit_cost'    => ['required', 'numeric', 'min:0'],
            'lines.*.line_notes'          => ['nullable', 'string', 'max:500'],
        ]);

        try {
            app(ReceivePurchaseOrder::class)->execute($purchase, $validated['lines']);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('tenant.purchases.show', $purchase)
            ->with('success', 'Stock received and inventory updated.');
    }

    public function print(PurchaseOrder $purchase): View
    {
        $purchase->load(['lines.stockItem', 'outlet', 'creator']);

        return view('modules.purchases.print', ['order' => $purchase]);
    }
}
