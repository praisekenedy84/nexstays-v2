<x-layouts.app active-nav="inventory" title="Inventory" subtitle="Stock items & recipe consumption">
    @can('manage-inventory')
        <div class="mb-4 flex justify-end">
            <a href="{{ route('tenant.stock-items.create') }}" class="btn-primary">Add stock item</a>
        </div>
    @endcan

    @if ($lowStock > 0)
        <div class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ $lowStock }} item(s) at or below reorder level.
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3">Item</th>
                    <th class="px-5 py-3">Outlet</th>
                    <th class="px-5 py-3">Stock</th>
                    <th class="px-5 py-3">Reorder</th>
                    @can('manage-inventory')
                        <th class="px-5 py-3 text-right">Actions</th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($items as $item)
                    <tr @class(['bg-amber-50/50' => (float) $item->current_stock <= (float) $item->reorder_level])>
                        <td class="px-5 py-4 font-medium">{{ $item->name }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $item->outlet?->name ?? 'Property' }}</td>
                        <td class="px-5 py-4">{{ $item->current_stock }} {{ $item->unit }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $item->reorder_level }}</td>
                        @can('manage-inventory')
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('tenant.stock-items.edit', $item) }}" class="text-primary hover:underline">Edit</a>
                                <form method="POST" action="{{ route('tenant.stock-items.destroy', $item) }}" class="ml-3 inline" onsubmit="return confirm('Delete this item?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        @endcan
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $items->links() }}</div>
    </div>
</x-layouts.app>
