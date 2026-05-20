<x-layouts.app active-nav="rooms" title="Rooms" subtitle="Room inventory & housekeeping status">
    @can('manage-rooms')
        <div class="mb-4 flex justify-end"><a href="{{ route('tenant.rooms.create') }}" class="btn-primary">Add room</a></div>
    @endcan
    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">Status</label>
            <select name="status" class="input-field min-w-[160px]" onchange="this.form.submit()">
                <option value="all">All statuses</option>
                @foreach (['vacant_clean', 'vacant_dirty', 'occupied', 'out_of_order'] as $s)
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
        @foreach ($statusCounts as $s => $count)
            <span class="rounded-full bg-slate-100 px-3 py-1 text-ink-muted">
                {{ str_replace('_', ' ', $s) }}: <strong class="text-ink">{{ $count }}</strong>
            </span>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse ($rooms as $room)
            <article class="card p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-2xl font-bold text-ink">{{ $room->room_number }}</p>
                        <p class="text-sm text-ink-muted">Floor {{ $room->floor }} · {{ $room->roomType?->name }}</p>
                    </div>
                    <x-ui.status-badge :status="$room->status" />
                </div>
                <div class="mt-4 flex flex-wrap gap-3 text-sm">
                    @can('manage-rooms|manage-room-status')
                        <a href="{{ route('tenant.rooms.edit', $room) }}" class="text-primary hover:underline">{{ auth()->user()?->can('manage-rooms') ? 'Edit' : 'Update status' }}</a>
                    @endcan
                    @can('manage-rooms')
                        <form method="POST" action="{{ route('tenant.rooms.destroy', $room) }}" class="inline" onsubmit="return confirm('Delete room?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Delete</button></form>
                    @endcan
                </div>
            </article>
        @empty
            <p class="col-span-full text-center text-ink-muted">No rooms match filters.</p>
        @endforelse
    </div>

    @if ($rooms->hasPages())
        <div class="mt-6">{{ $rooms->links() }}</div>
    @endif
</x-layouts.app>
