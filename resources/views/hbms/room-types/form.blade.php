@php
    $isEdit = $roomType->exists;
    $existingPhotos = collect($roomType->photos ?? []);
@endphp
<x-layouts.app active-nav="room-types" :title="$isEdit ? 'Edit room type' : 'New room type'">
    <div class="mb-6"><a href="{{ route('tenant.room-types.index') }}" class="text-sm text-primary hover:underline">← Room types</a></div>
    <form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('tenant.room-types.update', $roomType) : route('tenant.room-types.store') }}" class="card max-w-lg space-y-4 p-6">
        @csrf @if($isEdit) @method('PUT') @endif

        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Name</label><input name="name" value="{{ old('name', $roomType->name) }}" required class="input-field"></div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Code</label><input name="code" value="{{ old('code', $roomType->code) }}" required class="input-field"></div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Description</label><textarea name="description" class="input-field" rows="2">{{ old('description', $roomType->description) }}</textarea></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Max adults</label><input type="number" name="max_adults" value="{{ old('max_adults', $roomType->max_adults ?? 2) }}" required class="input-field"></div>
            <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Max children</label><input type="number" name="max_children" value="{{ old('max_children', $roomType->max_children ?? 0) }}" required class="input-field"></div>
        </div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Base rate (TZS)</label><input type="number" step="0.01" name="base_rate" value="{{ old('base_rate', $roomType->base_rate) }}" required class="input-field"></div>

        {{-- Photos --}}
        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Room type photos</label>
            <input type="file" name="photos[]" multiple accept=".jpg,.jpeg,.png,.webp" class="input-field">
            <p class="mt-1 text-xs text-ink-subtle">Up to 8 images, max 5 MB each. JPG, PNG or WebP.</p>
        </div>
        @if ($existingPhotos->isNotEmpty())
            <div>
                <p class="mb-2 text-xs font-medium text-ink-muted">Existing photos</p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($existingPhotos as $photoPath)
                        @php($photoUrl = \App\Domain\HBMS\Models\RoomType::photoUrl($photoPath))
                        <label class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <img src="{{ $photoUrl }}" alt="Room type photo" class="h-20 w-full object-cover">
                            <span class="flex items-center gap-2 px-2 py-1 text-xs">
                                <input type="checkbox" name="remove_photos[]" value="{{ $photoPath }}" @checked(in_array($photoPath, old('remove_photos', []), true))>
                                Remove
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <button type="submit" class="btn-primary">Save</button>
    </form>
</x-layouts.app>
