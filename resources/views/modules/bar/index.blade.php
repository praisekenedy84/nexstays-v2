<x-layouts.app active-nav="bar" :title="$outlet->name" subtitle="Bar tabs — open & settle via API">
    <div class="mb-4 flex justify-end">
        @can('manage-orders')
            <code class="text-xs text-ink-subtle">POST /api/v1/outlets/{{ $outlet->id }}/tabs</code>
        @endcan
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($tabs as $tab)
            <article class="card p-5">
                <h3 class="text-lg font-bold text-ink">{{ $tab->guest_label }}</h3>
                <p class="mt-1 font-mono text-sm text-primary">{{ $tab->order?->order_number }}</p>
                <p class="mt-3 text-xl font-bold">@money($tab->order?->total ?? 0)</p>
                <p class="mt-2 text-xs text-ink-muted">Opened {{ $tab->opened_at?->diffForHumans() }}</p>
            </article>
        @empty
            <p class="col-span-full text-center text-ink-muted">No open bar tabs.</p>
        @endforelse
    </div>
</x-layouts.app>
