{{-- Order row actions — used in outlet POS order lists. Expects: $order --}}
@php
    $iconBtn = 'inline-flex items-center justify-center rounded-md p-1.5 transition-colors';
@endphp

<div class="flex flex-wrap items-center justify-end gap-0.5">
    <a
        href="{{ route('tenant.fb.orders.show', $order) }}"
        class="{{ $iconBtn }} text-primary hover:bg-slate-100"
        title="View order"
        aria-label="View order"
    >
        <x-icon name="eye" class="size-4" />
        <span class="sr-only">View</span>
    </a>

    @if ($order->status === 'closed')
        <a
            href="{{ route('tenant.pos.orders.receipt', $order) }}"
            target="_blank"
            class="{{ $iconBtn }} text-primary hover:bg-slate-100"
            title="Print receipt"
            aria-label="Print receipt"
        >
            <x-icon name="till" class="size-4" />
            <span class="sr-only">Receipt</span>
        </a>
    @endif

    @can('manage-orders')
        @if ($order->isOpen())
            <a
                href="{{ route('tenant.pos.manage', $order) }}"
                class="{{ $iconBtn }} text-primary hover:bg-slate-100"
                title="Manage order"
                aria-label="Manage order"
            >
                <x-icon name="clipboard" class="size-4" />
                <span class="sr-only">Manage</span>
            </a>
            <form method="POST" action="{{ route('tenant.pos.orders.cancel', $order) }}" class="inline"
                  onsubmit="return confirm('Cancel order {{ $order->order_number }}? All items will be voided.')">
                @csrf
                <button
                    type="submit"
                    class="{{ $iconBtn }} text-red-600 hover:bg-red-50"
                    title="Cancel order"
                    aria-label="Cancel order"
                >
                    <x-icon name="x-circle" class="size-4" />
                    <span class="sr-only">Cancel</span>
                </button>
            </form>
        @elseif ($order->status === 'closed')
            <form method="POST" action="{{ route('tenant.pos.orders.cancel', $order) }}" class="inline"
                  onsubmit="return confirm('Void settled order {{ $order->order_number }}? Beverage stock will be restored and this sale will be removed from reports.')">
                @csrf
                <button
                    type="submit"
                    class="{{ $iconBtn }} text-amber-600 hover:bg-amber-50"
                    title="Void settled order"
                    aria-label="Void settled order"
                >
                    <x-icon name="x-circle" class="size-4" />
                    <span class="sr-only">Void</span>
                </button>
            </form>
            <form method="POST" action="{{ route('tenant.fb.orders.destroy', $order) }}" class="inline"
                  onsubmit="return confirm('Permanently delete order {{ $order->order_number }}? Stock will be restored and the record will be removed entirely.')">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="{{ $iconBtn }} text-red-600 hover:bg-red-50"
                    title="Delete order permanently"
                    aria-label="Delete order permanently"
                >
                    <x-icon name="trash" class="size-4" />
                    <span class="sr-only">Delete</span>
                </button>
            </form>
        @elseif ($order->status === 'voided')
            <form method="POST" action="{{ route('tenant.fb.orders.destroy', $order) }}" class="inline"
                  onsubmit="return confirm('Permanently delete order {{ $order->order_number }}? This cannot be undone.')">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="{{ $iconBtn }} text-red-600 hover:bg-red-50"
                    title="Delete order permanently"
                    aria-label="Delete order permanently"
                >
                    <x-icon name="trash" class="size-4" />
                    <span class="sr-only">Delete</span>
                </button>
            </form>
        @endif
    @endcan
</div>
