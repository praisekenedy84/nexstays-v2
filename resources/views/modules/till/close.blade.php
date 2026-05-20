<x-layouts.app active-nav="till" title="Close till session" :subtitle="$till->outlet?->name ?? 'Property till'">
    <div class="mb-6">
        <a href="{{ route('tenant.till.index') }}" class="text-sm text-primary hover:underline">← Till sessions</a>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 max-w-2xl">
        <div class="card p-4">
            <p class="text-xs uppercase text-ink-muted">System cash</p>
            <p class="text-xl font-bold text-ink">@money($systemCash->getAmount()->__toString())</p>
        </div>
        <div class="card p-4">
            <p class="text-xs uppercase text-ink-muted">Opening float</p>
            <p class="text-xl font-bold text-ink">@money($till->float_amount)</p>
        </div>
    </div>

    <form method="POST" action="{{ route('tenant.till.close', $till) }}" class="card max-w-md space-y-4 p-6">
        @csrf

        <div>
            <label for="declared_cash" class="mb-1.5 block text-xs font-medium text-ink-muted">Declared cash in drawer (TZS)</label>
            <input id="declared_cash" type="number" step="0.01" min="0" name="declared_cash" value="{{ old('declared_cash') }}" required class="input-field">
        </div>

        <div>
            <label for="manager_notes" class="mb-1.5 block text-xs font-medium text-ink-muted">Notes (optional)</label>
            <textarea id="manager_notes" name="manager_notes" rows="2" class="input-field">{{ old('manager_notes') }}</textarea>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Close session</button>
            <a href="{{ route('tenant.till.index') }}" class="btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.app>
