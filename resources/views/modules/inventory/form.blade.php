@php
    $isEdit = $stockItem->exists;
@endphp

<x-layouts.app active-nav="inventory" :title="$isEdit ? 'Edit stock item' : 'New stock item'">
    <div class="mb-6">
        <a href="{{ route('tenant.stock-items.index') }}" class="text-sm text-primary hover:underline">← Inventory</a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('tenant.stock-items.update', $stockItem) : route('tenant.stock-items.store') }}" class="card max-w-xl space-y-4 p-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <div>
            <label for="name" class="mb-1.5 block text-xs font-medium text-ink-muted">Name</label>
            <input id="name" name="name" value="{{ old('name', $stockItem->name) }}" required class="input-field">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="outlet_id" class="mb-1.5 block text-xs font-medium text-ink-muted">Outlet (optional)</label>
                <select id="outlet_id" name="outlet_id" class="input-field">
                    <option value="">Property-wide</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(old('outlet_id', $stockItem->outlet_id) === $outlet->id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="category" class="mb-1.5 block text-xs font-medium text-ink-muted">Category</label>
                <input id="category" name="category" value="{{ old('category', $stockItem->category) }}" class="input-field" placeholder="e.g. beverages">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label for="unit" class="mb-1.5 block text-xs font-medium text-ink-muted">Unit</label>
                <input id="unit" name="unit" value="{{ old('unit', $stockItem->unit ?? 'pcs') }}" required class="input-field">
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
</x-layouts.app>
