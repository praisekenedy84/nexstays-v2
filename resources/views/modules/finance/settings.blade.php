<x-layouts.app active-nav="finance-settings" title="Finance & Tax Settings" subtitle="Configure how tax is applied to all charges">

    <form method="POST" action="{{ route('tenant.finance.settings.update') }}" class="space-y-6 max-w-2xl">
        @csrf
        @method('PUT')

        {{-- Tax mode --}}
        <div class="card space-y-5 p-6">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Tax mode</h2>
                <p class="mt-1 text-xs text-ink-muted">
                    Determines whether the prices you enter for menu items, ancillary services, and room charges
                    <strong>already include tax</strong> or whether tax is <strong>added on top</strong>.
                </p>
            </div>

            <div class="space-y-3">
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition
                    {{ old('tax_inclusive', $settings['tax_inclusive'] ? '1' : '0') === '1' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300' }}">
                    <input type="radio" name="tax_inclusive" value="1" class="mt-0.5 text-primary"
                        @checked(old('tax_inclusive', $settings['tax_inclusive'] ? '1' : '0') === '1')>
                    <div>
                        <p class="font-medium text-ink">Tax-inclusive <span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Recommended</span></p>
                        <p class="mt-0.5 text-xs text-ink-muted">
                            The price you enter already contains tax. A 1,180 item is charged at 1,180 — the system
                            extracts 180 (at 18%) as the VAT portion for accounting records.
                        </p>
                    </div>
                </label>

                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition
                    {{ old('tax_inclusive', $settings['tax_inclusive'] ? '1' : '0') === '0' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300' }}">
                    <input type="radio" name="tax_inclusive" value="0" class="mt-0.5 text-primary"
                        @checked(old('tax_inclusive', $settings['tax_inclusive'] ? '1' : '0') === '0')>
                    <div>
                        <p class="font-medium text-ink">Tax-exclusive</p>
                        <p class="mt-0.5 text-xs text-ink-muted">
                            The price you enter is the net (pre-tax) amount. Tax is calculated and added on top.
                            A 1,000 item at 18% becomes 1,180 on the guest's bill.
                        </p>
                    </div>
                </label>
            </div>

            @error('tax_inclusive')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Rates --}}
        <div class="card space-y-5 p-6">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">VAT rates</h2>
                <p class="mt-1 text-xs text-ink-muted">
                    Enter rates as percentages, e.g. <strong>18</strong> for 18%. Per-charge-type rates override
                    the default when set differently.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-ink-muted">
                        Default VAT rate (%) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="vat_rate" step="0.001" min="0" max="100"
                               value="{{ old('vat_rate', $settings['vat_rate_pct']) }}"
                               class="input-field pr-8" required>
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-ink-muted">%</span>
                    </div>
                    @error('vat_rate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-medium text-ink-muted">Tax code</label>
                    <input type="text" name="tax_code" maxlength="10"
                           value="{{ old('tax_code', $settings['tax_code']) }}"
                           placeholder="e.g. A, VAT, GST"
                           class="input-field">
                    @error('tax_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <p class="mb-3 text-xs font-medium uppercase tracking-wide text-ink-muted">Per-charge-type overrides</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-ink-muted">Room charge (%)</label>
                        <div class="relative">
                            <input type="number" name="rate_room_charge" step="0.001" min="0" max="100"
                                   value="{{ old('rate_room_charge', $settings['rate_room_charge_pct']) }}"
                                   class="input-field pr-8" required>
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-ink-muted">%</span>
                        </div>
                        @error('rate_room_charge')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-ink-muted">Restaurant (%)</label>
                        <div class="relative">
                            <input type="number" name="rate_restaurant" step="0.001" min="0" max="100"
                                   value="{{ old('rate_restaurant', $settings['rate_restaurant_pct']) }}"
                                   class="input-field pr-8" required>
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-ink-muted">%</span>
                        </div>
                        @error('rate_restaurant')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-ink-muted">Bar (%)</label>
                        <div class="relative">
                            <input type="number" name="rate_bar" step="0.001" min="0" max="100"
                                   value="{{ old('rate_bar', $settings['rate_bar_pct']) }}"
                                   class="input-field pr-8" required>
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-ink-muted">%</span>
                        </div>
                        @error('rate_bar')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Example preview --}}
        <div class="rounded-lg border border-sky-200 bg-sky-50 p-4 text-xs text-sky-800" id="tax-preview">
            <p class="font-semibold mb-1">Live example</p>
            <p id="preview-text">Loading…</p>
        </div>

        <div>
            <button type="submit" class="btn-primary">Save settings</button>
        </div>
    </form>

    @push('scripts')
    <script>
    (function () {
        const radios    = document.querySelectorAll('input[name="tax_inclusive"]');
        const rateInput = document.querySelector('input[name="vat_rate"]');
        const preview   = document.getElementById('preview-text');
        const EXAMPLE   = 1180;

        function update() {
            const inclusive = document.querySelector('input[name="tax_inclusive"]:checked')?.value === '1';
            const rate      = parseFloat(rateInput?.value || '18') / 100;
            const fmt       = n => Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            if (inclusive) {
                const tax = EXAMPLE * rate / (1 + rate);
                const net = EXAMPLE - tax;
                preview.innerHTML = `Price entered: <strong>${fmt(EXAMPLE)}</strong> → guest is charged <strong>${fmt(EXAMPLE)}</strong> · tax extracted: ${fmt(tax)} · net revenue: ${fmt(net)}`;
            } else {
                const tax   = EXAMPLE * rate;
                const total = EXAMPLE + tax;
                preview.innerHTML = `Price entered: <strong>${fmt(EXAMPLE)}</strong> (net) → tax added: ${fmt(tax)} → guest is charged <strong>${fmt(total)}</strong>`;
            }

            // Highlight selected radio card
            document.querySelectorAll('input[name="tax_inclusive"]').forEach(r => {
                r.closest('label').classList.toggle('border-primary', r.checked);
                r.closest('label').classList.toggle('bg-primary/5', r.checked);
                r.closest('label').classList.toggle('border-slate-200', !r.checked);
            });
        }

        radios.forEach(r => r.addEventListener('change', update));
        rateInput?.addEventListener('input', update);
        update();
    })();
    </script>
    @endpush

</x-layouts.app>
