<x-layouts.app active-nav="purchases" title="New purchase order">
    <div class="mb-6"><a href="{{ route('tenant.purchases.index') }}" class="text-sm text-primary hover:underline">← Purchases</a></div>

    <form method="POST" action="{{ route('tenant.purchases.store') }}" id="po-form" class="space-y-6">
        @csrf

        {{-- Header details --}}
        <div class="card space-y-4 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Order details</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-ink-muted">Department <span class="text-red-500">*</span></label>
                    <select name="department" class="input-field" required>
                        <option value="kitchen" @selected(old('department', $department) === 'kitchen')>Kitchen</option>
                        <option value="bar"     @selected(old('department', $department) === 'bar')>Bar</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-ink-muted">Outlet (optional)</label>
                    <select name="outlet_id" class="input-field">
                        <option value="">—</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected(old('outlet_id') === $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-ink-muted">Expected delivery</label>
                    <input type="date" name="delivery_date_expected" value="{{ old('delivery_date_expected') }}" class="input-field">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-ink-muted">Supplier <span class="text-red-500">*</span></label>
                    <input name="supplier_name" value="{{ old('supplier_name') }}" required placeholder="Supplier name" class="input-field">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-ink-muted">Supplier reference / quote no.</label>
                    <input name="supplier_reference" value="{{ old('supplier_reference') }}" placeholder="e.g. INV-2026-001" class="input-field">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-ink-muted">Payment terms</label>
                    <input name="payment_terms" value="{{ old('payment_terms') }}" placeholder="e.g. Net 30, Cash on delivery" class="input-field">
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Notes / special instructions</label>
                <textarea name="notes" rows="2" class="input-field" placeholder="Any notes for the supplier or your team…">{{ old('notes') }}</textarea>
            </div>
        </div>

        {{-- Line items --}}
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Line items</h2>
                <button type="button" id="add-row-btn" class="btn-outline text-xs">+ Add item</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="lines-table">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-ink-muted">
                        <tr>
                            <th class="px-4 py-3 text-left" style="min-width:240px">Stock item</th>
                            <th class="px-4 py-3 text-right" style="width:110px">Qty</th>
                            <th class="px-4 py-3 text-right" style="width:130px">Unit cost</th>
                            <th class="px-4 py-3 text-right" style="width:130px">Line total</th>
                            <th class="px-4 py-3 text-center" style="width:50px"></th>
                        </tr>
                    </thead>
                    <tbody id="lines-body" class="divide-y divide-slate-100">
                        {{-- Rows injected by JS --}}
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-ink-muted">Grand total</td>
                            <td class="px-4 py-3 text-right text-lg font-bold text-ink" id="grand-total">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Submit options --}}
        <div class="flex flex-wrap items-center gap-4">
            <button type="submit" class="btn-primary">Create purchase order</button>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-ink">
                <input type="checkbox" name="receive_now" value="1" @checked(old('receive_now')) class="rounded border-slate-300 text-primary">
                Receive immediately (update stock now)
            </label>
        </div>
    </form>

    @push('scripts')
    <style>
        .item-autocomplete { position: relative; }
        .item-autocomplete .ac-dropdown {
            position: absolute; top: 100%; left: 0; right: 0; z-index: 50;
            background: #fff; border: 1px solid #e2e8f0; border-top: none;
            border-radius: 0 0 6px 6px; max-height: 220px; overflow-y: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
        }
        .item-autocomplete .ac-option {
            padding: 8px 12px; cursor: pointer; font-size: 13px;
        }
        .item-autocomplete .ac-option:hover,
        .item-autocomplete .ac-option.active { background: #f1f5f9; }
        .item-autocomplete .ac-option .ac-unit { font-size: 11px; color: #94a3b8; margin-left: 6px; }
        .item-autocomplete .ac-empty { padding: 8px 12px; font-size: 12px; color: #94a3b8; }
    </style>
    <script>
    (function () {
        const SEARCH_URL = '{{ route('tenant.stock-items.json') }}';
        const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        let rowIndex = 0;

        const body       = document.getElementById('lines-body');
        const grandTotal = document.getElementById('grand-total');
        const addBtn     = document.getElementById('add-row-btn');

        /* ── Utility ── */
        function fmt(n) {
            return Number(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        function recalcAll() {
            let total = 0;
            body.querySelectorAll('.line-row').forEach(row => {
                const qty  = parseFloat(row.querySelector('.line-qty')?.value)  || 0;
                const cost = parseFloat(row.querySelector('.line-cost')?.value) || 0;
                const sub  = qty * cost;
                total += sub;
                row.querySelector('.line-total-display').textContent = fmt(sub);
            });
            grandTotal.textContent = fmt(total);
        }

        /* ── AJAX search ── */
        let searchTimer = null;
        async function fetchItems(q) {
            const res  = await fetch(`${SEARCH_URL}?q=${encodeURIComponent(q)}`, {
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
            });
            return res.ok ? res.json() : [];
        }

        /* ── Autocomplete widget ── */
        function buildAutocomplete(idx) {
            const wrap = document.createElement('div');
            wrap.className = 'item-autocomplete';

            const display = document.createElement('input');
            display.type        = 'text';
            display.placeholder = 'Search item…';
            display.className   = 'input-field';
            display.autocomplete = 'off';

            const hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = `lines[${idx}][stock_item_id]`;

            const drop = document.createElement('div');
            drop.className = 'ac-dropdown';
            drop.hidden    = true;

            wrap.appendChild(display);
            wrap.appendChild(hidden);
            wrap.appendChild(drop);

            let activeIdx = -1;
            let currentItems = [];

            function showDrop(items) {
                currentItems = items;
                activeIdx    = -1;
                drop.innerHTML = '';
                if (!items.length) {
                    const empty = document.createElement('div');
                    empty.className = 'ac-empty';
                    empty.textContent = 'No items found';
                    drop.appendChild(empty);
                } else {
                    items.forEach((item, i) => {
                        const opt = document.createElement('div');
                        opt.className = 'ac-option';
                        opt.innerHTML = `${item.name}${item.unit ? `<span class="ac-unit">${item.unit}</span>` : ''}`;
                        opt.addEventListener('mousedown', e => {
                            e.preventDefault();
                            selectItem(item);
                        });
                        drop.appendChild(opt);
                    });
                }
                drop.hidden = false;
            }

            function hideDrop() { drop.hidden = true; activeIdx = -1; }

            function selectItem(item) {
                display.value = item.name;
                hidden.value  = item.id;
                hideDrop();
                recalcAll();
            }

            function highlightOption(i) {
                const opts = drop.querySelectorAll('.ac-option');
                opts.forEach(o => o.classList.remove('active'));
                if (i >= 0 && i < opts.length) {
                    opts[i].classList.add('active');
                    opts[i].scrollIntoView({ block: 'nearest' });
                }
            }

            display.addEventListener('input', () => {
                hidden.value = '';
                const q = display.value.trim();
                clearTimeout(searchTimer);
                if (!q) { hideDrop(); return; }
                searchTimer = setTimeout(async () => {
                    const items = await fetchItems(q);
                    showDrop(items);
                }, 220);
            });

            display.addEventListener('focus', () => {
                if (display.value.trim() && !hidden.value) {
                    fetchItems(display.value.trim()).then(showDrop);
                }
            });

            display.addEventListener('keydown', e => {
                if (drop.hidden) return;
                const opts = currentItems;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeIdx = Math.min(activeIdx + 1, opts.length - 1);
                    highlightOption(activeIdx);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeIdx = Math.max(activeIdx - 1, 0);
                    highlightOption(activeIdx);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (activeIdx >= 0 && opts[activeIdx]) selectItem(opts[activeIdx]);
                } else if (e.key === 'Escape') {
                    hideDrop();
                }
            });

            display.addEventListener('blur', () => setTimeout(hideDrop, 150));

            return { wrap, hidden };
        }

        /* ── Row factory ── */
        function makeRow(idx) {
            const tr = document.createElement('tr');
            tr.className    = 'line-row';
            tr.dataset.index = idx;

            /* Item cell */
            const tdItem = document.createElement('td');
            tdItem.className = 'px-4 py-3';
            const { wrap } = buildAutocomplete(idx);
            tdItem.appendChild(wrap);

            /* Qty cell */
            const tdQty  = document.createElement('td');
            tdQty.className = 'px-4 py-3';
            tdQty.innerHTML = `<input type="number" step="0.001" min="0" name="lines[${idx}][quantity]"
                placeholder="0" class="input-field text-right line-qty" required>`;

            /* Cost cell */
            const tdCost = document.createElement('td');
            tdCost.className = 'px-4 py-3';
            tdCost.innerHTML = `<input type="number" step="0.01" min="0" name="lines[${idx}][unit_cost]"
                placeholder="0.00" class="input-field text-right line-cost" required>`;

            /* Total display */
            const tdTotal = document.createElement('td');
            tdTotal.className = 'px-4 py-3 text-right font-medium text-ink line-total-display';
            tdTotal.textContent = '0';

            /* Remove button */
            const tdRm = document.createElement('td');
            tdRm.className = 'px-4 py-3 text-center';
            const rmBtn = document.createElement('button');
            rmBtn.type      = 'button';
            rmBtn.className = 'remove-row-btn text-slate-400 hover:text-red-500';
            rmBtn.title     = 'Remove';
            rmBtn.textContent = '✕';
            rmBtn.addEventListener('click', () => {
                if (body.querySelectorAll('.line-row').length <= 1) return;
                tr.remove();
                recalcAll();
            });
            tdRm.appendChild(rmBtn);

            tr.appendChild(tdItem);
            tr.appendChild(tdQty);
            tr.appendChild(tdCost);
            tr.appendChild(tdTotal);
            tr.appendChild(tdRm);
            return tr;
        }

        /* ── Init ── */
        body.appendChild(makeRow(rowIndex++));

        addBtn.addEventListener('click', () => {
            body.appendChild(makeRow(rowIndex++));
        });

        body.addEventListener('input', e => {
            if (e.target.closest('.line-row')) recalcAll();
        });

        recalcAll();
    })();
    </script>
    @endpush
</x-layouts.app>
