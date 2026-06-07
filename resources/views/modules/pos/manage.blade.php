@php
    $outletNav = match($order->outlet->type) {
        'bar'    => 'bar',
        'lounge' => 'lounge',
        default  => 'restaurant',
    };
    $backRoute = match($order->outlet->type) {
        'bar'    => route('tenant.bar.index'),
        'lounge' => route('tenant.lounge.index'),
        default  => route('tenant.restaurant.index'),
    };
    $currency    = config('nexstay.currency.default', 'TZS');
    $isOpen      = $order->isOpen();
    $canManage   = auth()->user()?->can('manage-order', $order);

    // Can cancel if order is open (items in kitchen don't block cancel — just warn)
    $canCancel     = $isOpen && $canManage;
    // Can add items only if order is still open
    $canAddItems   = $isOpen && $canManage;

    $showCash        = $allowsDirect && in_array('cash', $enabledMethods, true);
    $showMobileMoney = $allowsDirect && in_array('mobile_money', $enabledMethods, true);
    $showCard        = $allowsDirect && in_array('card', $enabledMethods, true);
    $showFolio       = $allowsFolio;
@endphp

<x-layouts.app :active-nav="$outletNav" :title="'Order '.$order->order_number" subtitle="POS — manage order">

    {{-- Header --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ $backRoute }}" class="text-sm text-ink-muted hover:text-ink">← Back</a>
            <span class="text-slate-300">/</span>
            <span class="font-mono text-sm font-semibold text-primary">{{ $order->order_number }}</span>
            <span @class([
                'rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                'bg-slate-100 text-slate-700'     => $order->status === 'open',
                'bg-amber-100 text-amber-800'     => $order->status === 'sent_to_kitchen',
                'bg-orange-100 text-orange-800'   => $order->status === 'preparing',
                'bg-blue-100 text-blue-800'       => $order->status === 'partially_served',
                'bg-emerald-100 text-emerald-800' => $order->status === 'served',
                'bg-slate-200 text-slate-500'     => $order->status === 'closed',
            ])>{{ str_replace('_', ' ', $order->status) }}</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-xs text-ink-muted">
                <span>{{ $order->outlet->name }}</span>
                @if ($order->table)
                    <span>· Table {{ $order->table->table_number }}</span>
                @endif
                @if ($order->guest_label)
                    <span>· {{ $order->guest_label }}</span>
                @endif
                <span>· {{ $order->covers }} pax</span>
                <span>· {{ $order->waiter?->name ?? '—' }}</span>
                <span class="ml-2 text-ink-subtle">
                    · Opened {{ $order->opened_at?->format('d M, H:i') ?? '—' }}
                </span>
            </div>

            {{-- Receipt link --}}
            <a href="{{ route('tenant.pos.orders.receipt', $order) }}"
               target="_blank"
               class="btn-outline text-xs">
                Receipt ↗
            </a>

            {{-- Cancel order --}}
            @if ($canCancel)
                <form method="POST" action="{{ route('tenant.pos.orders.cancel', $order) }}"
                      onsubmit="return confirm('Cancel order {{ $order->order_number }}? This cannot be undone.')">
                    @csrf
                    <button type="submit"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 ring-1 ring-rose-200 hover:bg-rose-50">
                        Cancel order
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 ring-1 ring-rose-200">{{ session('error') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_380px]">

        {{-- ===== LEFT: MENU BROWSER ===== --}}
        <section>
            @if ($isOpen && $canManage)
                @forelse ($order->outlet->menuCategories as $cat)
                    <details class="card mb-3 overflow-hidden" open>
                        <summary class="flex cursor-pointer select-none items-center justify-between px-5 py-3 font-semibold text-ink hover:bg-slate-50">
                            {{ $cat->name }}
                            <span class="text-xs font-normal text-ink-muted">{{ $cat->items->count() }} items</span>
                        </summary>
                        <div class="divide-y divide-slate-100 border-t border-slate-100">
                            @forelse ($cat->items as $item)
                                <form method="POST" action="{{ route('tenant.pos.orders.add-item', $order) }}"
                                      class="pos-add-item-form flex items-center justify-between gap-3 px-5 py-3">
                                    @csrf
                                    <input type="hidden" name="menu_item_id" value="{{ $item->id }}">
                                    @php $itemPhoto = \App\Domain\Shared\Models\MenuItem::photoUrl($item->photo); @endphp
                                    @if ($itemPhoto)
                                        <img src="{{ $itemPhoto }}" alt="{{ $item->name }}"
                                             class="size-10 shrink-0 rounded-lg object-cover border border-slate-100">
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-ink">{{ $item->name }}</p>
                                        @if ($item->description)
                                            <p class="truncate text-xs text-ink-subtle">{{ $item->description }}</p>
                                        @endif
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-sm font-semibold text-ink">{{ $currency }} {{ number_format((float) $item->price) }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <input type="number" name="quantity" value="1" min="1" max="99"
                                               class="w-14 rounded-lg border border-slate-300 px-2 py-1 text-center text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                                        <button type="submit"
                                                class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary/90">
                                            Add
                                        </button>
                                    </div>
                                </form>
                            @empty
                                <p class="px-5 py-3 text-xs text-ink-subtle">No items in this category.</p>
                            @endforelse
                        </div>
                    </details>
                @empty
                    <div class="card p-8 text-center text-sm text-ink-muted">
                        No menu categories configured for this outlet.
                        <a href="{{ route('tenant.menu-categories.index') }}" class="mt-2 block text-primary hover:underline">Set up menu →</a>
                    </div>
                @endforelse

                @if ($beverageCategories->isNotEmpty())
                    <div class="mb-3 mt-6">
                        <h3 class="text-sm font-semibold text-ink">Beverages</h3>
                        <p class="text-xs text-ink-muted">Bar drinks served on this order — stock deducts from bar inventory.</p>
                    </div>
                    @foreach ($beverageCategories as $cat)
                        <details class="card mb-3 overflow-hidden">
                            <summary class="flex cursor-pointer select-none items-center justify-between px-5 py-3 font-semibold text-ink hover:bg-slate-50">
                                <span>{{ $cat->name }} <span class="ml-1 text-xs font-normal text-sky-600">(bar)</span></span>
                                <span class="text-xs font-normal text-ink-muted">{{ $cat->items->count() }} items</span>
                            </summary>
                            <div class="divide-y divide-slate-100 border-t border-slate-100">
                                @foreach ($cat->items as $item)
                                    <form method="POST" action="{{ route('tenant.pos.orders.add-item', $order) }}"
                                          class="pos-add-item-form flex items-center justify-between gap-3 px-5 py-3">
                                        @csrf
                                        <input type="hidden" name="menu_item_id" value="{{ $item->id }}">
                                        @php $itemPhoto = \App\Domain\Shared\Models\MenuItem::photoUrl($item->photo); @endphp
                                        @if ($itemPhoto)
                                            <img src="{{ $itemPhoto }}" alt="{{ $item->name }}"
                                                 class="size-10 shrink-0 rounded-lg object-cover border border-slate-100">
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-ink">{{ $item->name }}</p>
                                            @if ($item->description)
                                                <p class="truncate text-xs text-ink-subtle">{{ $item->description }}</p>
                                            @endif
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <p class="text-sm font-semibold text-ink">{{ $currency }} {{ number_format((float) $item->price) }}</p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1.5">
                                            <input type="number" name="quantity" value="1" min="1" max="99"
                                                   class="w-14 rounded-lg border border-slate-300 px-2 py-1 text-center text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                                            <button type="submit"
                                                    class="rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-sky-700">
                                                Add
                                            </button>
                                        </div>
                                    </form>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                @endif
            @elseif (! $isOpen)
                <div class="card p-8 text-center text-sm text-ink-muted">
                    This order is <strong>{{ $order->status }}</strong>. No further items can be added.
                </div>
            @else
                <div class="card p-8 text-center text-sm text-ink-muted">
                    You don't have permission to manage orders.
                </div>
            @endif
        </section>

        {{-- ===== RIGHT: ORDER PANEL ===== --}}
        <aside id="pos-order-panel" class="space-y-4">
            @include('modules.pos._order-panel')
        </aside>
    </div>

    @if ($canAddItems)
        @push('scripts')
        <script>
        (() => {
            const panel = document.getElementById('pos-order-panel');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!panel || !csrf) return;

            document.querySelectorAll('.pos-add-item-form').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn?.disabled) return;

                    const originalLabel = submitBtn?.textContent ?? 'Add';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Adding…';
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                            credentials: 'same-origin',
                        });

                        const payload = await response.json();

                        if (!response.ok) {
                            throw new Error(payload.message ?? 'Could not add item.');
                        }

                        if (payload.panel) {
                            panel.innerHTML = payload.panel;
                        }

                        const qtyInput = form.querySelector('input[name="quantity"]');
                        if (qtyInput) {
                            qtyInput.value = '1';
                        }
                    } catch (error) {
                        window.alert(error instanceof Error ? error.message : 'Could not add item.');
                    } finally {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalLabel;
                        }
                    }
                });
            });
        })();
        </script>
        @endpush
    @endif

</x-layouts.app>
