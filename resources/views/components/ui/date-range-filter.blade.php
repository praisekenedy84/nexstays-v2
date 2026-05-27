@props(['from', 'to'])

<div class="mb-6 space-y-2">
    {{-- Preset chips --}}
    <div class="flex flex-wrap gap-1.5">
        @foreach ([
            'today'      => 'Today',
            'yesterday'  => 'Yesterday',
            'this_week'  => 'This week',
            'this_month' => 'This month',
            'last_month' => 'Last month',
        ] as $preset => $label)
            <button type="button"
                    class="date-preset rounded-full border border-slate-200 px-3 py-1 text-xs text-ink-muted transition hover:border-primary/40 hover:text-primary"
                    data-preset="{{ $preset }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Inputs row --}}
    <div class="flex flex-wrap items-end gap-3">
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-ink-muted">From</label>
            <input type="date" name="from" id="filter-from" value="{{ $from }}" class="input-field w-auto">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-ink-muted">To</label>
            <input type="date" name="to" id="filter-to" value="{{ $to }}" class="input-field w-auto">
        </div>
        <button type="submit" class="btn-primary">Update</button>
        {{ $slot }}
        <a href="{{ route('tenant.reports') }}" class="btn-outline">All reports</a>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    function fmt(d) { return d.toISOString().split('T')[0]; }

    function resolvePreset(preset) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (preset === 'today') {
            return [fmt(today), fmt(today)];
        }
        if (preset === 'yesterday') {
            const y = new Date(today); y.setDate(y.getDate() - 1);
            return [fmt(y), fmt(y)];
        }
        if (preset === 'this_week') {
            const d = new Date(today);
            const dow = d.getDay(); // 0=Sun
            d.setDate(d.getDate() - (dow === 0 ? 6 : dow - 1)); // back to Mon
            return [fmt(d), fmt(today)];
        }
        if (preset === 'this_month') {
            return [fmt(new Date(today.getFullYear(), today.getMonth(), 1)), fmt(today)];
        }
        if (preset === 'last_month') {
            const first = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const last  = new Date(today.getFullYear(), today.getMonth(), 0);
            return [fmt(first), fmt(last)];
        }
        return null;
    }

    document.querySelectorAll('.date-preset').forEach(btn => {
        btn.addEventListener('click', function () {
            const range = resolvePreset(this.dataset.preset);
            if (!range) return;
            const fromEl = document.getElementById('filter-from');
            const toEl   = document.getElementById('filter-to');
            if (fromEl) fromEl.value = range[0];
            if (toEl)   toEl.value   = range[1];
            // Submit the nearest ancestor form
            const form = this.closest('form') ?? fromEl?.closest('form');
            if (form) form.submit();
        });
    });
})();
</script>
@endpush
@endonce
