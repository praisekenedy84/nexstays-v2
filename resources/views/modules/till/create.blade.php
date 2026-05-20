<x-layouts.app active-nav="till" title="Open till session" subtitle="Start cash drawer">
    <div class="mb-6">
        <a href="{{ route('tenant.till.index') }}" class="text-sm text-primary hover:underline">← Till sessions</a>
    </div>

    <form method="POST" action="{{ route('tenant.till.store') }}" class="card max-w-md space-y-4 p-6">
        @csrf

        <div>
            <label for="outlet_id" class="mb-1.5 block text-xs font-medium text-ink-muted">Outlet</label>
            <select id="outlet_id" name="outlet_id" class="input-field">
                <option value="">Front desk / property</option>
                @foreach ($outlets as $outlet)
                    <option value="{{ $outlet->id }}" @selected(old('outlet_id') === $outlet->id)>{{ $outlet->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="float_amount" class="mb-1.5 block text-xs font-medium text-ink-muted">Opening float (TZS)</label>
            <input id="float_amount" type="number" step="0.01" min="0" name="float_amount" value="{{ old('float_amount', '0') }}" required class="input-field">
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Open session</button>
            <a href="{{ route('tenant.till.index') }}" class="btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.app>
