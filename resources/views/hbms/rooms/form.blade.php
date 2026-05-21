@php
    $isEdit = $room->exists;
    $existingPhotos = collect($room->photos ?? []);
    $amenitiesValue = old('amenities_text', collect($room->amenities ?? [])->implode(', '));
    $featuresValue = old('features_text', collect($room->features ?? [])->implode(', '));
@endphp
<x-layouts.app active-nav="rooms" :title="$isEdit ? 'Edit room '.$room->room_number : 'New room'">
    <div class="mb-6"><a href="{{ route('tenant.rooms.index') }}" class="text-sm text-primary hover:underline">← Rooms</a></div>
    <form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('tenant.rooms.update', $room) : route('tenant.rooms.store') }}" class="card max-w-lg space-y-4 p-6">
        @csrf @if($isEdit) @method('PUT') @endif
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
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Daily rate (TZS)</label>
            <input type="number" name="daily_rate" step="0.01" min="0" value="{{ old('daily_rate', $room->daily_rate) }}" required class="input-field">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Amenities</label>
            <textarea name="amenities_text" class="input-field" rows="2" placeholder="Wi-Fi, Smart TV, Mini bar">{{ $amenitiesValue }}</textarea>
            <p class="mt-1 text-xs text-ink-subtle">Separate values with commas or new lines.</p>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Features</label>
            <textarea name="features_text" class="input-field" rows="2" placeholder="Ocean view, Balcony, Accessible">{{ $featuresValue }}</textarea>
            <p class="mt-1 text-xs text-ink-subtle">Use this for distinctive room highlights.</p>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_smoking" value="1" @checked(old('is_smoking', $room->is_smoking))> Smoking room</label>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Room photos</label>
            <input type="file" name="photos[]" multiple accept=".jpg,.jpeg,.png,.webp" class="input-field">
            <p class="mt-1 text-xs text-ink-subtle">Up to 8 images, max 5MB each.</p>
        </div>
        @if ($existingPhotos->isNotEmpty())
            <div>
                <p class="mb-2 text-xs font-medium text-ink-muted">Existing photos</p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($existingPhotos as $photoPath)
                        @php($photoUrl = \App\Domain\HBMS\Models\Room::photoUrl($photoPath))
                        <label class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <img src="{{ $photoUrl }}" alt="Room photo" class="h-20 w-full object-cover">
                            <span class="flex items-center gap-2 px-2 py-1 text-xs">
                                <input type="checkbox" name="remove_photos[]" value="{{ $photoPath }}" @checked(in_array($photoPath, old('remove_photos', []), true))>
                                Remove
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Notes</label><textarea name="notes" class="input-field" rows="2">{{ old('notes', $room->notes) }}</textarea></div>
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
