<x-layouts.app active-nav="purchases" title="Purchases" subtitle="Bar & kitchen stock orders">
    @can('manage-purchases')
        <div class="mb-4 flex justify-end gap-2">
            <a href="{{ route('tenant.purchases.create', ['department' => 'kitchen']) }}" class="btn-outline">Kitchen PO</a>
            <a href="{{ route('tenant.purchases.create', ['department' => 'bar']) }}" class="btn-primary">New purchase</a>
        </div>
    @endcan

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <select name="department" class="input-field w-auto" onchange="this.form.submit()">
            <option value="">All departments</option>
            <option value="kitchen" @selected(request('department') === 'kitchen')>Kitchen</option>
            <option value="bar" @selected(request('department') === 'bar')>Bar</option>
        </select>
        <select name="status" class="input-field w-auto" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach (['draft', 'ordered', 'received', 'cancelled'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </form>

    <div class="card overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3">PO #</th>
                    <th class="px-5 py-3">Supplier</th>
                    <th class="px-5 py-3">Dept</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Total</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-5 py-4 font-mono text-xs font-semibold">{{ $order->po_number }}</td>
                        <td class="px-5 py-4">{{ $order->supplier_name }}</td>
                        <td class="px-5 py-4 capitalize text-ink-muted">{{ $order->department }}</td>
                        <td class="px-5 py-4"><x-ui.status-badge :status="$order->status" /></td>
                        <td class="px-5 py-4 text-right font-medium">@money($order->total_amount)</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('tenant.purchases.show', $order) }}" class="text-primary hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-ink-muted">No purchase orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $orders->links() }}</div>
    </div>
</x-layouts.app>
