<x-layouts.app active-nav="payment-methods" title="Payment Methods" subtitle="Configure which payment methods are accepted">

    <form method="POST" action="{{ route('tenant.finance.payment-methods.update') }}" class="max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <div class="card space-y-5 p-6">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Accepted methods</h2>
                <p class="mt-1 text-xs text-ink-muted">
                    Disable a method to prevent staff from selecting it during checkout. At least one method must remain active.
                </p>
            </div>

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-3 text-xs text-red-700">
                    <p class="font-medium">Please fix the following:</p>
                    <ul class="mt-1 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-3">
                @foreach ($methods as $key => $meta)
                    @php
                        $isEnabled = in_array($key, old('enabled', $settings['enabled']), true);
                    @endphp
                    <label class="flex cursor-pointer items-start gap-4 rounded-lg border p-4 transition
                        {{ $isEnabled ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300' }}"
                        id="label-{{ $key }}">
                        <div class="mt-0.5 flex items-center">
                            <input type="checkbox" name="enabled[]" value="{{ $key }}"
                                   class="size-4 rounded text-primary"
                                   @checked($isEnabled)
                                   onchange="toggleCard(this)">
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-ink">{{ $meta['label'] }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">{{ $meta['description'] }}</p>
                        </div>
                        <div class="shrink-0">
                            @if ($isEnabled)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 toggle-badge-{{ $key }}">Active</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-400 toggle-badge-{{ $key }}">Disabled</span>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <button type="submit" class="btn-primary">Save settings</button>
        </div>
    </form>

    @push('scripts')
    <script>
    function toggleCard(checkbox) {
        const key   = checkbox.value;
        const label = document.getElementById('label-' + key);
        const badge = label.querySelector('.toggle-badge-' + key);

        if (checkbox.checked) {
            label.classList.add('border-primary', 'bg-primary/5');
            label.classList.remove('border-slate-200');
            badge.textContent = 'Active';
            badge.className = 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 toggle-badge-' + key;
        } else {
            label.classList.remove('border-primary', 'bg-primary/5');
            label.classList.add('border-slate-200');
            badge.textContent = 'Disabled';
            badge.className = 'rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-400 toggle-badge-' + key;
        }
    }
    </script>
    @endpush

</x-layouts.app>
