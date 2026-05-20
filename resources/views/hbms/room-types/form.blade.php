@php $isEdit = $roomType->exists; @endphp
<x-layouts.app active-nav="room-types" :title="$isEdit ? 'Edit room type' : 'New room type'">
    <div class="mb-6"><a href="{{ route('tenant.room-types.index') }}" class="text-sm text-primary hover:underline">← Room types</a></div>
    <form method="POST" action="{{ $isEdit ? route('tenant.room-types.update', $roomType) : route('tenant.room-types.store') }}" class="card max-w-lg space-y-4 p-6">
        @csrf @if($isEdit) @method('PUT') @endif
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Name</label><input name="name" value="{{ old('name', $roomType->name) }}" required class="input-field"></div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Code</label><input name="code" value="{{ old('code', $roomType->code) }}" required class="input-field"></div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Description</label><textarea name="description" class="input-field" rows="2">{{ old('description', $roomType->description) }}</textarea></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Max adults</label><input type="number" name="max_adults" value="{{ old('max_adults', $roomType->max_adults ?? 2) }}" required class="input-field"></div>
            <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Max children</label><input type="number" name="max_children" value="{{ old('max_children', $roomType->max_children ?? 0) }}" required class="input-field"></div>
        </div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Base rate (TZS)</label><input type="number" step="0.01" name="base_rate" value="{{ old('base_rate', $roomType->base_rate) }}" required class="input-field"></div>
        <button type="submit" class="btn-primary">Save</button>
    </form>
</x-layouts.app>
