@php $isEdit = $category->exists; @endphp
<x-layouts.app active-nav="menu-categories" :title="$isEdit ? 'Edit category' : 'New category'">
    <div class="mb-6"><a href="{{ route('tenant.menu-categories.index') }}" class="text-sm text-primary hover:underline">← Categories</a></div>
    <form method="POST" action="{{ $isEdit ? route('tenant.menu-categories.update', $category) : route('tenant.menu-categories.store') }}" class="card max-w-lg space-y-4 p-6">
        @csrf @if($isEdit) @method('PUT') @endif
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Outlet</label>
            <select name="outlet_id" required class="input-field">
                @foreach ($outlets as $outlet)
                    <option value="{{ $outlet->id }}" @selected(old('outlet_id', $category->outlet_id) === $outlet->id)>{{ $outlet->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Name</label><input name="name" value="{{ old('name', $category->name) }}" required class="input-field"></div>
        <div><label class="mb-1.5 block text-xs font-medium text-ink-muted">Display order</label><input type="number" name="display_order" value="{{ old('display_order', $category->display_order ?? 0) }}" class="input-field"></div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))> Active</label>
        <button type="submit" class="btn-primary">Save</button>
    </form>
</x-layouts.app>
