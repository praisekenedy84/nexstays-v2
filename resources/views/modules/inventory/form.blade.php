@php
    $isEdit = $stockItem->exists;
    $selectedMenuId = old('menu_item_id', $prefillMenuItemId ?? null);
@endphp

<x-layouts.app active-nav="inventory" :title="$isEdit ? 'Edit stock item' : 'New stock item'">
    <div class="mb-6">
        <a href="{{ route('tenant.stock-items.index') }}" class="text-sm text-primary hover:underline">← Inventory</a>
    </div>

    @if ($isEdit && $stockItem->last_restocked_at)
        <div class="card mb-4 max-w-xl px-5 py-3 text-sm text-ink-muted">
            <strong class="text-ink">Last restocked:</strong>
            {{ $stockItem->last_restocked_at->format('d M Y H:i') }}
            @if ($stockItem->relationLoaded('lastRestockedBy') && $stockItem->lastRestockedBy)
                by {{ $stockItem->lastRestockedBy->name }}
            @endif
            @can('manage-inventory')
                <a href="{{ route('tenant.stock-items.restock-form', $stockItem) }}" class="ml-3 text-primary hover:underline">Restock again</a>
            @endcan
        </div>
    @elseif ($isEdit)
        @can('manage-inventory')
            <div class="mb-4 max-w-xl">
                <a href="{{ route('tenant.stock-items.restock-form', $stockItem) }}" class="btn-primary">Restock</a>
            </div>
        @endcan
    @endif

    <form method="POST" action="{{ $isEdit ? route('tenant.stock-items.update', $stockItem) : route('tenant.stock-items.store') }}" class="card max-w-xl space-y-4 p-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        @if (($unlinkedMenuItems ?? collect())->isNotEmpty())
            <div class="rounded-xl border border-primary/20 bg-primary/5 p-4 space-y-3">
                <h3 class="text-sm font-semibold text-ink">Link to bar menu item</h3>
                <p class="text-xs text-ink-muted">Select a drink on the menu that is not yet tied to inventory.</p>
                <select id="menu_item_id" name="menu_item_id" class="input-field" onchange="applyMenuItemSelection()">
                    <option value="">— No menu link —</option>
                    @foreach ($unlinkedMenuItems as $menuItem)
                        <option value="{{ $menuItem->id }}"
                                data-name="{{ $menuItem->name }}"
                                @selected($selectedMenuId === $menuItem->id)>
                            {{ $menuItem->name }}
                            @if ($menuItem->relationLoaded('category') && $menuItem->category?->outlet)
                                ({{ $menuItem->category->outlet->name }})
                            @endif
                        </option>
                    @endforeach
                </select>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div>
                        <label for="serve_quantity" class="mb-1 block text-xs font-medium text-ink-muted">Qty per menu sale</label>
                        <input id="serve_quantity" type="number" step="0.0001" min="0.0001" name="serve_quantity"
                               value="{{ $serveQuantity ?? 1 }}" class="input-field">
                    </div>
                    <div>
                        <label for="serve_unit" class="mb-1 block text-xs font-medium text-ink-muted">Unit</label>
                        <input id="serve_unit" name="serve_unit" value="{{ $serveUnit ?? 'bottle' }}" class="input-field">
                    </div>
                </div>
            </div>
        @endif

        <div>
            <label for="name" class="mb-1.5 block text-xs font-medium text-ink-muted">Name</label>
            <input id="name" name="name" value="{{ old('name', $stockItem->name) }}" required class="input-field">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="outlet_id" class="mb-1.5 block text-xs font-medium text-ink-muted">Outlet</label>
                <select id="outlet_id" name="outlet_id" class="input-field">
                    <option value="">Property-wide</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(old('outlet_id', $stockItem->outlet_id) === $outlet->id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="category" class="mb-1.5 block text-xs font-medium text-ink-muted">Category</label>
                <input id="category" name="category" value="{{ old('category', $stockItem->category ?? 'beverage') }}" class="input-field" placeholder="beverage">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="unit" class="mb-1.5 block text-xs font-medium text-ink-muted">Unit</label>
                <input id="unit" name="unit" value="{{ old('unit', $stockItem->unit ?? 'bottle') }}" required class="input-field">
            </div>
            <div>
                <label for="current_stock" class="mb-1.5 block text-xs font-medium text-ink-muted">Current stock</label>
                <input id="current_stock" type="number" step="0.001" min="0" name="current_stock" value="{{ old('current_stock', $stockItem->current_stock ?? 0) }}" required class="input-field">
            </div>
            <div>
                <label for="reorder_level" class="mb-1.5 block text-xs font-medium text-ink-muted">Reorder level</label>
                <input id="reorder_level" type="number" step="0.001" min="0" name="reorder_level" value="{{ old('reorder_level', $stockItem->reorder_level ?? 0) }}" required class="input-field">
            </div>
        </div>

        <div>
            <label for="cost_per_unit" class="mb-1.5 block text-xs font-medium text-ink-muted">Cost per unit</label>
            <input id="cost_per_unit" type="number" step="0.01" min="0" name="cost_per_unit" value="{{ old('cost_per_unit', $stockItem->cost_per_unit) }}" class="input-field">
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Save' : 'Create' }}</button>
            <a href="{{ route('tenant.stock-items.index') }}" class="btn-outline">Cancel</a>
        </div>
    </form>

    <script>
        function applyMenuItemSelection() {
            const sel = document.getElementById('menu_item_id');
            const opt = sel?.selectedOptions[0];
            if (!opt || !opt.dataset.name) return;
            const nameInput = document.getElementById('name');
            if (nameInput && !nameInput.value.trim()) {
                nameInput.value = opt.dataset.name;
            }
        }
    </script>
</x-layouts.app>
