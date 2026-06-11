<x-layouts.app active-nav="facility-settings" title="Facility Fees" subtitle="Default charges for swimming pool and gym attendance">

    <form method="POST" action="{{ route('tenant.finance.facility-settings.update') }}" class="max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <div class="card space-y-5 p-6">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Default fees</h2>
                <p class="mt-1 text-xs text-ink-muted">
                    Pre-fills the fee amount when staff record attendance. Staff can still override the amount per visit.
                </p>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Swimming pool default fee ({{ config('nexstay.currency.default', 'TZS') }})</label>
                <input type="number" step="0.01" min="0" name="pool_default_fee"
                       value="{{ old('pool_default_fee', $settings['pool_default_fee']) }}" class="input-field max-w-xs">
                @error('pool_default_fee')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Gym default fee ({{ config('nexstay.currency.default', 'TZS') }})</label>
                <input type="number" step="0.01" min="0" name="gym_default_fee"
                       value="{{ old('gym_default_fee', $settings['gym_default_fee']) }}" class="input-field max-w-xs">
                @error('gym_default_fee')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <button type="submit" class="btn-primary">Save settings</button>
        </div>
    </form>

</x-layouts.app>
