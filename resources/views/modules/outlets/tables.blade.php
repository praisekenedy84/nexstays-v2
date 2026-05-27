<x-layouts.app active-nav="outlets"
    :title="$outlet->name . ' — Tables'"
    :subtitle="'Manage seating for ' . $outlet->name">

@php
    $statusColors = [
        'available' => 'bg-emerald-100 text-emerald-700',
        'occupied'  => 'bg-primary/10 text-primary',
        'reserved'  => 'bg-amber-100 text-amber-700',
        'blocked'   => 'bg-slate-100 text-slate-500',
    ];
    $sections = $tables->pluck('section')->filter()->unique()->sort()->values();
@endphp

<div class="space-y-6">

    {{-- Back + outlet info --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('tenant.outlets.index') }}" class="text-sm text-ink-muted hover:text-ink">← Outlets</a>
        <span class="text-slate-300">/</span>
        <span class="text-sm font-semibold text-ink capitalize">{{ $outlet->type }}</span>
        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $outlet->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
            {{ $outlet->is_active ? 'Active' : 'Inactive' }}
        </span>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">

        {{-- ===== LEFT: TABLE LIST ===== --}}
        <div class="space-y-4">

            {{-- Summary chips --}}
            @if ($tables->isNotEmpty())
                @php
                    $byStatus = $tables->groupBy('status');
                @endphp
                <div class="flex flex-wrap gap-2">
                    @foreach (['available', 'occupied', 'reserved', 'blocked'] as $s)
                        @php $count = $byStatus->get($s, collect())->count(); @endphp
                        @if ($count > 0)
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusColors[$s] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $count }} {{ $s }}
                            </span>
                        @endif
                    @endforeach
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        {{ $tables->sum('capacity') }} total seats
                    </span>
                </div>
            @endif

            {{-- Table grid by section --}}
            @if ($tables->isEmpty())
                <div class="card p-10 text-center text-sm text-ink-muted">
                    No tables yet. Add one using the form →
                </div>
            @else
                @php
                    $grouped = $tables->groupBy(fn ($t) => $t->section ?: '—')->sortKeys();
                @endphp

                @foreach ($grouped as $section => $sectionTables)
                    <section class="card overflow-hidden">
                        <div class="border-b border-slate-100 px-5 py-3 flex items-center justify-between">
                            <h3 class="font-semibold text-ink">
                                {{ $section === '—' ? 'No section' : $section }}
                            </h3>
                            <span class="text-xs text-ink-muted">{{ $sectionTables->count() }} table{{ $sectionTables->count() !== 1 ? 's' : '' }}</span>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @foreach ($sectionTables as $table)
                                <div class="flex items-center gap-4 px-5 py-3">

                                    {{-- Table number + capacity --}}
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 font-bold text-ink">
                                        {{ $table->table_number }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-ink">Table {{ $table->table_number }}</p>
                                        <p class="text-xs text-ink-muted">
                                            {{ $table->capacity }} seat{{ $table->capacity !== 1 ? 's' : '' }}
                                            @if ($table->active_orders_count > 0)
                                                · <span class="font-medium text-primary">{{ $table->active_orders_count }} active order{{ $table->active_orders_count !== 1 ? 's' : '' }}</span>
                                            @endif
                                        </p>
                                    </div>

                                    {{-- Status badge + quick-change --}}
                                    <form method="POST"
                                          action="{{ route('tenant.outlets.tables.status', [$outlet, $table]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status"
                                                onchange="this.form.submit()"
                                                class="rounded-full border-0 px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ring-slate-200 focus:outline-none {{ $statusColors[$table->status] ?? 'bg-slate-100 text-slate-600' }}">
                                            @foreach (['available', 'occupied', 'reserved', 'blocked'] as $s)
                                                <option value="{{ $s }}" @selected($table->status === $s)>
                                                    {{ ucfirst($s) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>

                                    {{-- Edit toggle --}}
                                    <button type="button"
                                            onclick="toggleEdit('edit-{{ $table->id }}')"
                                            class="text-xs text-primary hover:underline shrink-0">
                                        Edit
                                    </button>

                                    {{-- Delete --}}
                                    <form method="POST"
                                          action="{{ route('tenant.outlets.tables.destroy', [$outlet, $table]) }}"
                                          onsubmit="return confirm('Delete table {{ $table->table_number }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-xs text-rose-500 hover:text-rose-700 shrink-0">
                                            Delete
                                        </button>
                                    </form>
                                </div>

                                {{-- Inline edit form (hidden by default) --}}
                                <div id="edit-{{ $table->id }}" class="hidden border-t border-slate-100 bg-slate-50 px-5 py-4">
                                    <form method="POST"
                                          action="{{ route('tenant.outlets.tables.update', [$outlet, $table]) }}"
                                          class="flex flex-wrap items-end gap-3">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-ink-muted">Table number</label>
                                            <input type="text" name="table_number"
                                                   value="{{ old('table_number', $table->table_number) }}"
                                                   maxlength="20" required
                                                   class="input-field w-28">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-ink-muted">Capacity</label>
                                            <input type="number" name="capacity"
                                                   value="{{ old('capacity', $table->capacity) }}"
                                                   min="1" max="50" required
                                                   class="input-field w-20">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-ink-muted">Section (optional)</label>
                                            <input type="text" name="section"
                                                   value="{{ old('section', $table->section) }}"
                                                   maxlength="50"
                                                   placeholder="e.g. Indoor"
                                                   class="input-field w-36">
                                        </div>
                                        <button type="submit" class="btn-primary text-xs">Save</button>
                                        <button type="button"
                                                onclick="toggleEdit('edit-{{ $table->id }}')"
                                                class="text-xs text-ink-muted hover:text-ink">Cancel</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            @endif
        </div>

        {{-- ===== RIGHT: ADD TABLE FORM ===== --}}
        <aside class="space-y-4">
            <section class="card p-5">
                <h3 class="mb-4 font-semibold text-ink">Add table</h3>
                <form method="POST"
                      action="{{ route('tenant.outlets.tables.store', $outlet) }}"
                      class="space-y-4">
                    @csrf

                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-muted">Table number / label</label>
                        <input type="text" name="table_number"
                               value="{{ old('table_number') }}"
                               maxlength="20" required
                               placeholder="e.g. 1, A3, Terrace-1"
                               class="input-field w-full">
                        <p class="mt-1 text-xs text-ink-subtle">Can be a number or a label.</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-muted">Seating capacity</label>
                        <input type="number" name="capacity"
                               value="{{ old('capacity', 4) }}"
                               min="1" max="50" required
                               class="input-field w-full">
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-ink-muted">Section (optional)</label>
                        <input type="text" name="section"
                               value="{{ old('section') }}"
                               maxlength="50"
                               placeholder="e.g. Indoor, Terrace, VIP"
                               class="input-field w-full"
                               list="section-suggestions">
                        @if ($sections->isNotEmpty())
                            <datalist id="section-suggestions">
                                @foreach ($sections as $sec)
                                    <option value="{{ $sec }}">
                                @endforeach
                            </datalist>
                        @endif
                        <p class="mt-1 text-xs text-ink-subtle">Tables are grouped by section.</p>
                    </div>

                    <button type="submit" class="btn-primary w-full">Add table</button>
                </form>
            </section>

            {{-- Quick stats --}}
            @if ($tables->isNotEmpty())
                <section class="card p-5">
                    <h3 class="mb-3 font-semibold text-ink">Overview</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">Total tables</dt>
                            <dd class="font-semibold text-ink">{{ $tables->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">Total seats</dt>
                            <dd class="font-semibold text-ink">{{ $tables->sum('capacity') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">Available</dt>
                            <dd class="font-semibold text-emerald-700">{{ $tables->where('status', 'available')->count() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">Occupied</dt>
                            <dd class="font-semibold text-primary">{{ $tables->where('status', 'occupied')->count() }}</dd>
                        </div>
                        @if ($sections->isNotEmpty())
                            <div class="flex justify-between">
                                <dt class="text-ink-muted">Sections</dt>
                                <dd class="font-semibold text-ink">{{ $sections->count() }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>
            @endif
        </aside>
    </div>
</div>

@push('scripts')
<script>
function toggleEdit(id) {
    const el = document.getElementById(id);
    if (el) el.classList.toggle('hidden');
}
</script>
@endpush

</x-layouts.app>
