<x-layouts.app active-nav="rooms" title="Rooms" subtitle="Room inventory & housekeeping status">
    @php
        $statusMeta = [
            'vacant_clean' => ['label' => 'Vacant clean', 'badge' => 'bg-emerald-100 text-emerald-700', 'card' => 'border-emerald-200 bg-emerald-50/60'],
            'vacant_dirty' => ['label' => 'Vacant dirty', 'badge' => 'bg-amber-100 text-amber-800', 'card' => 'border-amber-200 bg-amber-50/60'],
            'occupied' => ['label' => 'Occupied', 'badge' => 'bg-blue-100 text-blue-700', 'card' => 'border-blue-200 bg-blue-50/60'],
            'out_of_order' => ['label' => 'Out of order', 'badge' => 'bg-red-100 text-red-700', 'card' => 'border-red-200 bg-red-50/60'],
            'blocked' => ['label' => 'Blocked', 'badge' => 'bg-slate-200 text-slate-700', 'card' => 'border-slate-300 bg-slate-100/70'],
        ];
        $roomsByFloor = $rooms->getCollection()->groupBy(fn ($room) => $room->floor ?? 'Unassigned');
    @endphp

    @can('manage-rooms')
        <div class="mb-4 flex justify-end"><a href="{{ route('tenant.rooms.create') }}" class="btn-primary">Add room</a></div>
    @endcan
    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">Status</label>
            <select name="status" class="input-field min-w-[160px]" onchange="this.form.submit()">
                <option value="all">All statuses</option>
                @foreach (['vacant_clean', 'vacant_dirty', 'occupied', 'out_of_order', 'blocked'] as $s)
                    <option value="{{ $s }}" @selected(($status ?? 'all') === $s)>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">Room type</label>
            <select name="room_type_id" class="input-field min-w-[180px]" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach ($roomTypes as $type)
                    <option value="{{ $type->id }}" @selected($roomTypeId === $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="mb-4 flex flex-wrap gap-2 text-xs">
        @foreach ($statusMeta as $statusKey => $meta)
            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 {{ $meta['badge'] }}">
                <span class="size-2 rounded-full bg-current opacity-70"></span>
                {{ $meta['label'] }}: <strong>{{ $statusCounts[$statusKey] ?? 0 }}</strong>
            </span>
        @endforeach
    </div>

    <div class="space-y-6">
        @forelse ($roomsByFloor as $floor => $floorRooms)
            <section class="card p-4 sm:p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">
                        Floor {{ $floor }}
                    </h2>
                    <span class="text-xs text-ink-subtle">{{ $floorRooms->count() }} room(s)</span>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($floorRooms as $room)
                        @php
                            $meta = $statusMeta[$room->status] ?? ['card' => 'border-slate-200 bg-white'];
                            $photoPath = collect($room->photos ?? [])->first();
                            $photoUrl = \App\Domain\HBMS\Models\Room::photoUrl($photoPath);
                        @endphp
                        <article class="rounded-2xl border p-4 shadow-sm transition hover:shadow-md {{ $meta['card'] }}">
                            @if ($photoUrl)
                                <img src="{{ $photoUrl }}" alt="Room {{ $room->room_number }}" class="mb-3 h-28 w-full rounded-xl object-cover">
                            @endif
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-2xl font-bold text-ink">{{ $room->room_number }}</p>
                                    <p class="text-sm text-ink-muted">{{ $room->roomType?->name }}</p>
                                </div>
                                <x-ui.status-badge :status="$room->status" />
                            </div>
                            <p class="mt-2 text-sm font-medium text-ink">Daily rate: @money($room->daily_rate ?? 0)</p>
                            <div class="mt-4 flex flex-wrap gap-3 text-sm">
                                @if ((auth()->user()?->can('manage-rooms') ?? false) || (auth()->user()?->can('manage-room-status') ?? false))
                                    <a href="{{ route('tenant.rooms.edit', $room) }}" class="text-primary hover:underline">Edit</a>
                                @endif
                                @can('manage-rooms')
                                    <form method="POST" action="{{ route('tenant.rooms.destroy', $room) }}" class="inline" onsubmit="return confirm('Delete room?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button></form>
                                @endcan
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="text-center text-ink-muted">No rooms match filters.</p>
        @endforelse
    </div>

    @if ($rooms->hasPages())
        <div class="mt-6">{{ $rooms->links() }}</div>
    @endif
</x-layouts.app>
