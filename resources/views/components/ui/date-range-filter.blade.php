@props([
    'from',
    'to',
    'showReportsLink' => true,
    'presets' => null,
    'compact' => false,
    'today' => null,
])

@php
    $presetLabels = $presets ?? [
        'today'        => 'Today',
        'yesterday'    => 'Yesterday',
        'this_week'    => 'This week',
        'this_month'   => 'This month',
        'last_month'   => 'Last month',
    ];
    $todayDate = $today ?? now()->format('Y-m-d');
@endphp

<div
    class="date-range-filter {{ $compact ? '' : 'mb-6' }} space-y-2"
    data-today="{{ $todayDate }}"
>
    {{-- Preset chips --}}
    <div class="flex flex-wrap gap-1.5">
        @foreach ($presetLabels as $preset => $label)
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
            <input type="date" name="from" value="{{ $from }}" class="input-field w-auto" onchange="this.form.submit()">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-ink-muted">To</label>
            <input type="date" name="to" value="{{ $to }}" class="input-field w-auto" onchange="this.form.submit()">
        </div>
        {{ $slot }}
        @if ($showReportsLink)
            <a href="{{ route('tenant.reports') }}" class="btn-outline">All reports</a>
        @endif
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    function parseYmd(ymd) {
        const [year, month, day] = ymd.split('-').map(Number);
        return new Date(year, month - 1, day);
    }

    function formatYmd(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function addDays(date, days) {
        const next = new Date(date);
        next.setDate(next.getDate() + days);
        return next;
    }

    function resolvePreset(preset, todayYmd) {
        const today = parseYmd(todayYmd);

        if (preset === 'today') {
            return [todayYmd, todayYmd];
        }
        if (preset === 'yesterday') {
            const y = formatYmd(addDays(today, -1));
            return [y, y];
        }
        if (preset === 'last_14_days') {
            return [formatYmd(addDays(today, -13)), todayYmd];
        }
        if (preset === 'last_30_days') {
            return [formatYmd(addDays(today, -29)), todayYmd];
        }
        if (preset === 'this_week') {
            const start = new Date(today);
            const dow = start.getDay();
            start.setDate(start.getDate() - (dow === 0 ? 6 : dow - 1));
            return [formatYmd(start), todayYmd];
        }
        if (preset === 'this_month') {
            const start = new Date(today.getFullYear(), today.getMonth(), 1);
            return [formatYmd(start), todayYmd];
        }
        if (preset === 'last_month') {
            const first = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const last = new Date(today.getFullYear(), today.getMonth(), 0);
            return [formatYmd(first), formatYmd(last)];
        }
        return null;
    }

    document.querySelectorAll('.date-range-filter').forEach((root) => {
        const todayYmd = root.dataset.today;
        if (!todayYmd) return;

        root.querySelectorAll('.date-preset').forEach((btn) => {
            btn.addEventListener('click', function () {
                const range = resolvePreset(this.dataset.preset, todayYmd);
                if (!range) return;

                const fromEl = root.querySelector('[name="from"]');
                const toEl = root.querySelector('[name="to"]');
                if (fromEl) fromEl.value = range[0];
                if (toEl) toEl.value = range[1];

                const form = root.closest('form') ?? fromEl?.closest('form');
                if (form) form.submit();
            });
        });
    });
})();
</script>
@endpush
@endonce
