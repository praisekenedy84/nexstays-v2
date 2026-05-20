<x-layouts.app active-nav="lounge" :title="$outlet->name" subtitle="Lounge orders">
    <div class="space-y-4">
        @forelse ($orders as $order)
            <article class="card flex items-center justify-between p-4">
                <div>
                    <p class="font-mono text-sm font-semibold text-primary">{{ $order->order_number }}</p>
                    <p class="text-sm text-ink-muted">{{ $order->guest_label ?? 'Walk-in' }}</p>
                </div>
                <div class="text-right">
                    <x-ui.status-badge :status="$order->status" />
                    <p class="mt-2 font-bold">@money($order->total)</p>
                </div>
            </article>
        @empty
            <p class="text-ink-muted">No active lounge orders.</p>
        @endforelse
    </div>
</x-layouts.app>
