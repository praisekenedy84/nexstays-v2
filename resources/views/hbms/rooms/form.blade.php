@php
    $isEdit = $room->exists;
    $statusOnly = $isEdit && ! ($fullEdit ?? false);
@endphp
<x-layouts.app active-nav="rooms" :title="$isEdit ? 'Edit room '.$room->room_number : 'New room'">
    <div class="mb-6"><a href="{{ route('tenant.rooms.index') }}" class="text-sm text-primary hover:underline">← Rooms</a></div>
    <form method="POST" action="{{ $isEdit ? route('tenant.rooms.update', $room) : route('tenant.rooms.store') }}" class="card max-w-lg space-y-4 p-6">
        @csrf @if($isEdit) @method('PUT') @endif
        @unless($statusOnly)
            <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Room type</label>
                <select name="room_type_id" required class="input-field">
                    @foreach ($roomTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('room_type_id', $room->room_type_id) === $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Room number</label><input name="room_number" value="{{ old('room_number', $room->room_number) }}" required class="input-field"></div>
                <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Floor</label><input type="number" name="floor" value="{{ old('floor', $room->floor) }}" class="input-field"></div>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_smoking" value="1" @checked(old('is_smoking', $room->is_smoking))> Smoking room</label>
            <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Notes</label><textarea name="notes" class="input-field" rows="2">{{ old('notes', $room->notes) }}</textarea></div>
        @endunless
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Status</label>
            <select name="status" required class="input-field">
                @foreach (['vacant_clean', 'vacant_dirty', 'occupied', 'out_of_order', 'blocked'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $room->status) === $s)>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">Save</button>
    </form>
</x-layouts.app>
