<x-layouts.app active-nav="inventory" title="Restock" :subtitle="$stockItem->name">
    <div class="mb-6">
        <a href="{{ route('tenant.stock-items.index', ['outlet_id' => $stockItem->outlet_id]) }}" class="text-sm text-primary hover:underline">← Inventory</a>
    </div>

    <div class="card mb-6 max-w-xl p-5">
        <h2 class="text-lg font-semibold text-ink">{{ $stockItem->name }}</h2>
        <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-xs text-ink-muted">Current stock</dt>
                <dd class="font-medium">{{ $stockItem->current_stock }} {{ $stockItem->unit }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink-muted">Reorder level</dt>
                <dd class="font-medium">{{ $stockItem->reorder_level }} {{ $stockItem->unit }}</dd>
            </div>
            @if ($stockItem->outlet)
                <div>
                    <dt class="text-xs text-ink-muted">Outlet</dt>
                    <dd>{{ $stockItem->outlet->name }}</dd>
                </div>
            @endif
            <div class="sm:col-span-2">
                <dt class="text-xs text-ink-muted">Last restocked</dt>
                <dd>
                    @if ($stockItem->last_restocked_at)
                        {{ $stockItem->last_restocked_at->format('d M Y H:i') }}
                        @if ($stockItem->lastRestockedBy)
                            <span class="text-ink-muted">by {{ $stockItem->lastRestockedBy->name }}</span>
                        @endif
                    @else
                        <span class="text-ink-subtle">Never restocked</span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    <form method="POST" action="{{ route('tenant.stock-items.restock', $stockItem) }}" class="card max-w-xl space-y-4 p-6">
        @csrf

        <div>
            <label for="quantity" class="mb-1.5 block text-xs font-medium text-ink-muted">
                Quantity to add ({{ $stockItem->unit }})
            </label>
            <input id="quantity" type="number" step="0.001" min="0.001" name="quantity"
                   value="{{ old('quantity') }}" required class="input-field" autofocus>
            @error('quantity')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="notes" class="mb-1.5 block text-xs font-medium text-ink-muted">Notes (optional)</label>
            <textarea id="notes" name="notes" rows="2" class="input-field"
                      placeholder="e.g. Supplier delivery, bottle count">{{ old('notes') }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">Confirm restock</button>
            <a href="{{ route('tenant.stock-items.index', ['outlet_id' => $stockItem->outlet_id]) }}" class="btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.app>
