@php
    $isEdit = $item->exists;
@endphp

<x-layouts.app active-nav="menu" :title="$isEdit ? 'Edit menu item' : 'New menu item'">
    <div class="mb-6">
        <a href="{{ route('tenant.menu-items.index') }}" class="text-sm text-primary hover:underline">← Menu</a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('tenant.menu-items.update', $item) : route('tenant.menu-items.store') }}" class="card max-w-xl space-y-4 p-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @else
            <input type="hidden" name="outlet_filter" value="{{ request('outlet_id') }}">
        @endif

        <div>
            <label for="category_id" class="mb-1.5 block text-xs font-medium text-ink-muted">Category</label>
            <select id="category_id" name="category_id" required class="input-field">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $item->category_id) === $category->id)>
                        {{ $category->name }}
                        @if ($category->relationLoaded('outlet') || $category->outlet)
                            ({{ $category->outlet?->name }})
                        @endif
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="name" class="mb-1.5 block text-xs font-medium text-ink-muted">Name</label>
            <input id="name" name="name" value="{{ old('name', $item->name) }}" required class="input-field">
        </div>

        <div>
            <label for="description" class="mb-1.5 block text-xs font-medium text-ink-muted">Description</label>
            <textarea id="description" name="description" rows="2" class="input-field">{{ old('description', $item->description) }}</textarea>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="price" class="mb-1.5 block text-xs font-medium text-ink-muted">Price (TZS)</label>
                <input id="price" type="number" step="0.01" min="0" name="price" value="{{ old('price', $item->price) }}" required class="input-field">
            </div>
            <div>
                <label for="cost" class="mb-1.5 block text-xs font-medium text-ink-muted">Cost (optional)</label>
                <input id="cost" type="number" step="0.01" min="0" name="cost" value="{{ old('cost', $item->cost) }}" class="input-field">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" name="is_available" value="1" @checked(old('is_available', $item->is_available ?? true)) class="rounded border-slate-300 text-primary">
            Available on menu
        </label>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Save' : 'Create' }}</button>
            <a href="{{ route('tenant.menu-items.index') }}" class="btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.app>
