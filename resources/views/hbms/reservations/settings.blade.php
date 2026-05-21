<x-layouts.app active-nav="reservations" title="Reservation settings" subtitle="Tenant-level payment and cancellation policy">
    <div class="mb-6">
        <a href="{{ route('tenant.reservations.index') }}" class="text-sm text-primary hover:underline">← Back to reservations</a>
    </div>

    <form method="POST" action="{{ route('tenant.reservations.settings.update') }}" class="card max-w-3xl space-y-6 p-6">
        @csrf
        @method('PUT')

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Payment mode</h2>
            <p class="mt-1 text-sm text-ink-muted">Reservation payments are fixed to prepaid.</p>
            <input type="hidden" name="payment_mode" value="prepaid">
            <div class="mt-3">
                <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-ink">Pre-paid</p>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Cancellation policy</h2>
            <p class="mt-1 text-sm text-ink-muted">Controls charge/refund calculations when a reservation is cancelled.</p>

            <div class="mt-4 space-y-4">
                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3">
                    <input type="radio" name="cancellation_policy" value="stayed_nights" class="mt-1" @checked(old('cancellation_policy', $settings['cancellation']['policy']) === 'stayed_nights')>
                    <span>
                        <span class="block text-sm font-medium text-ink">Charge by stayed nights</span>
                        <span class="block text-xs text-ink-muted">Charge based on nights already stayed, refund the remaining prepaid amount.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3">
                    <input type="radio" name="cancellation_policy" value="percentage_refund" class="mt-1" @checked(old('cancellation_policy', $settings['cancellation']['policy']) === 'percentage_refund')>
                    <span class="w-full">
                        <span class="block text-sm font-medium text-ink">Percentage refund</span>
                        <span class="block text-xs text-ink-muted">Refund a fixed percentage of prepaid amount after cancellation.</span>
                        <input
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            name="refund_percentage"
                            value="{{ old('refund_percentage', $settings['cancellation']['refund_percentage']) }}"
                            class="input-field mt-2 max-w-[220px]"
                            placeholder="Refund %"
                        >
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3">
                    <input type="radio" name="cancellation_policy" value="no_refund" class="mt-1" @checked(old('cancellation_policy', $settings['cancellation']['policy']) === 'no_refund')>
                    <span>
                        <span class="block text-sm font-medium text-ink">No refund</span>
                        <span class="block text-xs text-ink-muted">Keep all prepaid amount when reservation is cancelled.</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="btn-primary">Save settings</button>
        </div>
    </form>
</x-layouts.app>
