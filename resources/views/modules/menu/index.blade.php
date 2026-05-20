<x-layouts.app active-nav="menu" title="Menu items" subtitle="F&B menu by outlet">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-ink-muted">Outlet</label>
                <select name="outlet_id" class="input-field min-w-[200px]" onchange="this.form.submit()">
                    <option value="">All outlets</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected($outletId === $outlet->id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        @can('manage-menu')
            <a href="{{ route('tenant.menu-items.create', ['outlet_id' => $outletId]) }}" class="btn-primary">Add item</a>
        @endcan
    </div>

    <div class="card overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3">Item</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Outlet</th>
                    <th class="px-5 py-3 text-right">Price</th>
                    <th class="px-5 py-3">Available</th>
                    @can('manage-menu')
                        <th class="px-5 py-3 text-right">Actions</th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($items as $item)
                    <tr>
                        <td class="px-5 py-4 font-medium">{{ $item->name }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $item->category?->name }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $item->category?->outlet?->name }}</td>
                        <td class="px-5 py-4 text-right">@money($item->price)</td>
                        <td class="px-5 py-4">
                            @if ($item->is_available)
                                <span class="text-emerald-600">Yes</span>
                            @else
                                <span class="text-ink-subtle">No</span>
                            @endif
                        </td>
                        @can('manage-menu')
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('tenant.menu-items.edit', $item) }}" class="text-primary hover:underline">Edit</a>
                                <form method="POST" action="{{ route('tenant.menu-items.destroy', $item) }}" class="ml-3 inline" onsubmit="return confirm('Delete this item?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-ink-muted">No menu items.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $items->links() }}</div>
    </div>
</x-layouts.app>
