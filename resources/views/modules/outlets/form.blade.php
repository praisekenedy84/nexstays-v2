@php $isEdit = $outlet->exists; @endphp
<x-layouts.app active-nav="outlets" :title="$isEdit ? 'Edit outlet' : 'New outlet'">
    <div class="mb-6"><a href="{{ route('tenant.outlets.index') }}" class="text-sm text-primary hover:underline">← Outlets</a></div>
    <form method="POST" action="{{ $isEdit ? route('tenant.outlets.update', $outlet) : route('tenant.outlets.store') }}" class="card max-w-lg space-y-4 p-6">
        @csrf @if($isEdit) @method('PUT') @endif
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Name</label><input name="name" value="{{ old('name', $outlet->name) }}" required class="input-field"></div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Type</label>
            <select name="type" required class="input-field">
                @foreach (['restaurant', 'bar', 'lounge'] as $t)
                    <option value="{{ $t }}" @selected(old('type', $outlet->type) === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $outlet->is_active ?? true))> Active</label>
        <button type="submit" class="btn-primary">Save</button>
    </form>
</x-layouts.app>
