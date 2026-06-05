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
            <x-ui.search-bar :value="$search" placeholder="Item name…" />
        </form>
        <div class="flex flex-wrap gap-2">
            @if ($outletId && $outlets->firstWhere('id', $outletId)?->isBar())
                @can('manage-menu')
                    <form method="POST" action="{{ route('tenant.menu-items.sync-bar-inventory') }}">
                        @csrf
                        <input type="hidden" name="outlet_id" value="{{ $outletId }}">
                        <button type="submit" class="btn-outline">Sync with inventory</button>
                    </form>
                @endcan
            @endif
            @can('manage-menu')
                <a href="{{ route('tenant.menu-items.create', ['outlet_id' => $outletId]) }}" class="btn-primary">Add item</a>
            @endcan
        </div>
    </div>

    <div class="card overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3 w-12"></th>
                    <x-ui.sort-th column="name" label="Item" :sort="$sort" :dir="$dir" />
                    <th class="px-5 py-3 text-left">Category</th>
                    <th class="px-5 py-3 text-left">Outlet</th>
                    <x-ui.sort-th column="price" label="Price" :sort="$sort" :dir="$dir" class="text-right" />
                    @if ($outletId && $outlets->firstWhere('id', $outletId)?->isBar())
                        <th class="px-5 py-3 text-left">Inventory</th>
                    @endif
                    <th class="px-5 py-3 text-left">Available</th>
                    @can('manage-menu')
                        <th class="px-5 py-3 text-right">Actions</th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($items as $item)
                    <tr>
                        <td class="pl-5 py-3">
                            @php $photoUrl = \App\Domain\Shared\Models\MenuItem::photoUrl($item->photo); @endphp
                            @if ($photoUrl)
                                <img src="{{ $photoUrl }}" alt="{{ $item->name }}"
                                     class="size-10 rounded-lg object-cover border border-slate-100">
                            @else
                                <div class="size-10 rounded-lg border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center">
                                    <span class="text-[10px] text-slate-400">No img</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-4 font-medium">{{ $item->name }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $item->category?->name }}</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $item->category?->outlet?->name }}</td>
                        <td class="px-5 py-4 text-right">@money($item->price)</td>
                        @if ($outletId && $outlets->firstWhere('id', $outletId)?->isBar())
                            <td class="px-5 py-4 text-xs">
                                @if ($item->recipeIngredients->isEmpty())
                                    <span class="text-amber-700">Not linked</span>
                                @elseif ($item->linkedStockItem?->awaiting_stock)
                                    <span class="text-amber-700">Awaiting stock</span>
                                    <span class="block text-ink-subtle">0 {{ $item->linkedStockItem->unit }}</span>
                                @else
                                    @php $primary = $item->linkedStockItem ?? $item->recipeIngredients->first()?->stockItem; @endphp
                                    <span class="text-ink-muted">{{ $primary?->current_stock }} {{ $primary?->unit }} on hand</span>
                                    @if ($item->recipeIngredients->count() > 1)
                                        <span class="block text-ink-subtle">{{ $item->recipeIngredients->count() }} ingredients</span>
                                    @endif
                                @endif
                            </td>
                        @endif
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
                        <td colspan="8" class="px-5 py-12 text-center text-ink-muted">No menu items.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $items->links() }}</div>
    </div>
</x-layouts.app>
