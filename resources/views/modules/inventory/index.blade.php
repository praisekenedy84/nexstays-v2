<x-layouts.app active-nav="inventory" title="Inventory" subtitle="Stock items & bar beverage links">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            @if ($barOutlets->isNotEmpty())
                <div>
                    <label class="mb-1 block text-xs font-medium text-ink-muted">Bar outlet</label>
                    <select name="outlet_id" class="input-field min-w-[180px]" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach ($barOutlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected($outletId === $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <x-ui.search-bar :value="$search" placeholder="Item name…" />
        </form>
        <div class="flex flex-wrap gap-2">
            @can('manage-inventory')
                <form method="POST" action="{{ route('tenant.stock-items.sync') }}">
                    @csrf
                    <input type="hidden" name="outlet_id" value="{{ $outletId }}">
                    <button type="submit" class="btn-outline">Sync with bar menu</button>
                </form>
                <a href="{{ route('tenant.stock-items.create', ['outlet_id' => $outletId]) }}" class="btn-primary">Add stock item</a>
            @endcan
        </div>
    </div>

    @if ($lowStock > 0)
        <div class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ $lowStock }} item(s) at or below reorder level.
        </div>
    @endif

    @if ($awaitingStock->isNotEmpty() || $unlinkedMenuItems->isNotEmpty())
        <div class="mb-6 card overflow-hidden">
            <div class="border-b bg-slate-50 px-5 py-3">
                <h2 class="text-sm font-semibold text-ink">Awaiting stock / menu link</h2>
                <p class="text-xs text-ink-muted mt-0.5">Bar drinks on the menu that still need inventory setup or initial stock.</p>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-ink-muted">
                    <tr>
                        <th class="px-5 py-2">Item</th>
                        <th class="px-5 py-2">Status</th>
                        <th class="px-5 py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($awaitingStock as $row)
                        <tr class="bg-amber-50/40">
                            <td class="px-5 py-3 font-medium">{{ $row->name }}</td>
                            <td class="px-5 py-3 text-ink-muted">
                                Inventory registered — 0 on hand
                                @if ($row->menuItem)
                                    <span class="text-ink-subtle"> · Menu: {{ $row->menuItem->name }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                @can('manage-inventory')
                                    <a href="{{ route('tenant.stock-items.restock-form', $row) }}" class="text-primary hover:underline">Restock</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    @foreach ($unlinkedMenuItems as $menuItem)
                        <tr class="bg-amber-50/40">
                            <td class="px-5 py-3 font-medium">{{ $menuItem->name }}</td>
                            <td class="px-5 py-3 text-ink-muted">On menu — not in inventory yet</td>
                            <td class="px-5 py-3 text-right">
                                @can('manage-inventory')
                                    <a href="{{ route('tenant.stock-items.create', ['menu_item_id' => $menuItem->id]) }}" class="text-primary hover:underline">Create stock</a>
                                @endcan
                                @can('manage-menu')
                                    <a href="{{ route('tenant.menu-items.edit', $menuItem) }}" class="ml-3 text-primary hover:underline">Link on menu</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="card overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <x-ui.sort-th column="name" label="Item" :sort="$sort" :dir="$dir" />
                    <th class="px-5 py-3 text-left">Outlet</th>
                    <th class="px-5 py-3 text-left">Menu link</th>
                    <x-ui.sort-th column="current_stock" label="Stock" :sort="$sort" :dir="$dir" />
                    <x-ui.sort-th column="reorder_level" label="Reorder" :sort="$sort" :dir="$dir" />
                    <th class="px-5 py-3 text-left">Last restocked</th>
                    @can('manage-inventory')
                        <th class="px-5 py-3 text-right">Actions</th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($items as $item)
                    <tr @class(['bg-amber-50/50' => (float) $item->current_stock <= (float) $item->reorder_level])>
                        <td class="px-5 py-4 font-medium">{{ $item->name }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $item->outlet?->name ?? 'Property' }}</td>
                        <td class="px-5 py-4 text-xs text-ink-muted">
                            @if ($item->menuItem)
                                <a href="{{ route('tenant.menu-items.edit', $item->menuItem) }}" class="text-primary hover:underline">{{ $item->menuItem->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-4">{{ $item->current_stock }} {{ $item->unit }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $item->reorder_level }}</td>
                        <td class="px-5 py-4 text-xs text-ink-muted">
                            @if ($item->last_restocked_at)
                                <span class="block">{{ $item->last_restocked_at->format('d M Y H:i') }}</span>
                                @if ($item->lastRestockedBy)
                                    <span class="text-ink-subtle">{{ $item->lastRestockedBy->name }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        @can('manage-inventory')
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('tenant.stock-items.restock-form', $item) }}" class="btn-primary text-xs px-3 py-1.5">Restock</a>
                                <a href="{{ route('tenant.stock-items.edit', $item) }}" class="ml-2 text-primary hover:underline">Edit</a>
                                <form method="POST" action="{{ route('tenant.stock-items.destroy', $item) }}" class="ml-3 inline" onsubmit="return confirm('Delete this item?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-ink-muted">No stocked items yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $items->links() }}</div>
    </div>
</x-layouts.app>
