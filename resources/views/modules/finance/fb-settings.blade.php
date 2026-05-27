<x-layouts.app active-nav="fb-settings" title="F&B Settlement Settings" subtitle="How waiters collect payment for food & beverage orders">

    <form method="POST" action="{{ route('tenant.finance.fb-settings.update') }}" class="max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <div class="card space-y-5 p-6">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Settlement mode</h2>
                <p class="mt-1 text-xs text-ink-muted">
                    Controls what options appear on the POS screen when a waiter is ready to close an order.
                </p>
            </div>

            <div class="space-y-3">
                @foreach ($modes as $key => $label)
                    @php $checked = old('settlement_mode', $settings['settlement_mode']) === $key; @endphp
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition
                        {{ $checked ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300' }}"
                        id="mode-label-{{ $key }}">
                        <input type="radio" name="settlement_mode" value="{{ $key }}"
                               class="mt-0.5 text-primary"
                               @checked($checked)
                               onchange="highlightMode()">
                        <div>
                            @php
                                [$modeTitle, $modeDesc] = match($key) {
                                    'both'   => ['Both options', 'Waiter can post to a guest folio OR collect cash / mobile money / card directly. Recommended when you have both walk-in and hotel guests.'],
                                    'direct' => ['Direct collection only', 'Waiter collects payment on the spot — cash, mobile money, or card. No folio required. Ideal for restaurants and bars that mostly serve walk-in customers.'],
                                    'folio'  => ['Folio only', 'All orders are posted to a guest room account. No direct payment is recorded at the POS. Suited to all-inclusive or pre-paid setups.'],
                                    default  => [$key, $label],
                                };
                            @endphp
                            <p class="font-medium text-ink">
                                {{ $modeTitle }}
                                @if ($key === 'direct')
                                    <span class="ml-1 rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700">Recommended for most hotels</span>
                                @endif
                            </p>
                            <p class="mt-0.5 text-xs text-ink-muted">{{ $modeDesc }}</p>
                        </div>
                    </label>
                @endforeach
            </div>

            @error('settlement_mode')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- What this affects --}}
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs text-ink-muted space-y-1">
            <p class="font-semibold text-ink">What this setting affects</p>
            <ul class="list-inside list-disc space-y-0.5">
                <li>Which settlement panels appear on the POS order screen.</li>
                <li><strong>Direct</strong> mode: Cash (with optional till tracking), Mobile money (with optional transaction reference), Card.</li>
                <li><strong>Folio</strong> mode: A dropdown of open guest folios to post the charge to.</li>
                <li>Payment methods available in direct mode are controlled under <a href="{{ route('tenant.finance.payment-methods.edit') }}" class="underline">Payment methods</a>.</li>
            </ul>
        </div>

        <div>
            <button type="submit" class="btn-primary">Save settings</button>
        </div>
    </form>

    @push('scripts')
    <script>
    function highlightMode() {
        document.querySelectorAll('input[name="settlement_mode"]').forEach(r => {
            const lbl = document.getElementById('mode-label-' + r.value);
            lbl.classList.toggle('border-primary', r.checked);
            lbl.classList.toggle('bg-primary/5',   r.checked);
            lbl.classList.toggle('border-slate-200', !r.checked);
        });
    }
    </script>
    @endpush

</x-layouts.app>
