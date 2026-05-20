<x-layouts.app active-nav="purchases" title="New purchase order">
    <div class="mb-6"><a href="{{ route('tenant.purchases.index') }}" class="text-sm text-primary hover:underline">← Purchases</a></div>

    <form method="POST" action="{{ route('tenant.purchases.store') }}" class="card max-w-3xl space-y-4 p-6">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Department</label>
                <select name="department" class="input-field" required>
                    <option value="kitchen" @selected(old('department', $department) === 'kitchen')>Kitchen</option>
                    <option value="bar" @selected(old('department', $department) === 'bar')>Bar</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Outlet (optional)</label>
                <select name="outlet_id" class="input-field">
                    <option value="">—</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected(old('outlet_id') === $outlet->id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Supplier</label>
            <input name="supplier_name" value="{{ old('supplier_name') }}" required class="input-field">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Notes</label>
            <textarea name="notes" rows="2" class="input-field">{{ old('notes') }}</textarea>
        </div>

        <p class="text-sm font-semibold text-ink">Line items</p>
        @for ($i = 0; $i < 3; $i++)
            <div class="grid gap-3 rounded-lg bg-slate-50 p-4 sm:grid-cols-3">
                <select name="lines[{{ $i }}][stock_item_id]" class="input-field sm:col-span-1">
                    <option value="">Stock item…</option>
                    @foreach ($stockItems as $item)
                        <option value="{{ $item->id }}" @selected(old("lines.{$i}.stock_item_id") === $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.001" min="0" name="lines[{{ $i }}][quantity]" placeholder="Qty" value="{{ old("lines.{$i}.quantity") }}" class="input-field">
                <input type="number" step="0.01" min="0" name="lines[{{ $i }}][unit_cost]" placeholder="Unit cost" value="{{ old("lines.{$i}.unit_cost") }}" class="input-field">
            </div>
        @endfor

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="receive_now" value="1" @checked(old('receive_now')) class="rounded border-slate-300 text-primary">
            Receive immediately (update stock)
        </label>

        <button type="submit" class="btn-primary">Create purchase order</button>
    </form>
</x-layouts.app>
