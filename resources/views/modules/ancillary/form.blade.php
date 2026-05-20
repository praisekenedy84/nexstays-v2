@php $isEdit = $service->exists; @endphp
<x-layouts.app active-nav="ancillary" :title="$isEdit ? 'Edit service' : 'New service'">
    <div class="mb-6"><a href="{{ route('tenant.ancillary-services.index') }}" class="text-sm text-primary hover:underline">← Services</a></div>
    <form method="POST" action="{{ $isEdit ? route('tenant.ancillary-services.update', $service) : route('tenant.ancillary-services.store') }}" class="card max-w-lg space-y-4 p-6">
        @csrf @if($isEdit) @method('PUT') @endif
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Name</label><input name="name" value="{{ old('name', $service->name) }}" required class="input-field"></div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Description</label><textarea name="description" class="input-field" rows="2">{{ old('description', $service->description) }}</textarea></div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Default price</label><input type="number" step="0.01" name="default_price" value="{{ old('default_price', $service->default_price) }}" required class="input-field"></div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active ?? true))> Active</label>
        <button type="submit" class="btn-primary">Save</button>
    </form>
</x-layouts.app>
