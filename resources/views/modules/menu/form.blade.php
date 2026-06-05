@php
    $isEdit = $item->exists;
    $existingPhoto = $item->photo ?? null;
    $linkMode = $inventoryLinkMode ?? 'awaiting';
@endphp

<x-layouts.app active-nav="menu" :title="$isEdit ? 'Edit menu item' : 'New menu item'">
    <div class="mb-6">
        <a href="{{ route('tenant.menu-items.index') }}" class="text-sm text-primary hover:underline">← Menu</a>
    </div>

    <form method="POST"
          enctype="multipart/form-data"
          action="{{ $isEdit ? route('tenant.menu-items.update', $item) : route('tenant.menu-items.store') }}"
          class="card max-w-xl space-y-4 p-6">
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

        <div>
            <label class="mb-1.5 block text-xs font-medium text-ink-muted">Photo</label>
            @if ($existingPhoto)
                <div class="mb-3 flex items-start gap-4">
                    <img src="{{ \App\Domain\Shared\Models\MenuItem::photoUrl($existingPhoto) }}"
                         alt="{{ $item->name }}"
                         class="h-24 w-24 rounded-xl border border-slate-200 object-cover">
                    <label class="flex items-center gap-2 text-sm text-ink">
                        <input type="checkbox" name="remove_photo" value="1"
                               @checked(old('remove_photo'))
                               class="rounded border-slate-300 text-rose-600">
                        Remove current photo
                    </label>
                </div>
            @endif
            <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" class="input-field">
        </div>

        <label class="flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" name="is_available" value="1" @checked(old('is_available', $item->is_available ?? true)) class="rounded border-slate-300 text-primary">
            Available on menu
        </label>

        @if ($tracksInventory ?? false)
        <div class="border-t border-slate-100 pt-4 space-y-3" id="beverage-inventory-link">
            <h3 class="text-sm font-semibold text-ink">Beverage inventory link</h3>
            <p class="text-xs text-ink-muted">
                Connect this drink to stock so levels reduce automatically when bar orders close.
            </p>

            <div class="space-y-2 text-sm">
                <label class="flex items-center gap-2">
                    <input type="radio" name="inventory_link_mode" value="existing" @checked($linkMode === 'existing') onchange="toggleInventoryLinkMode()">
                    Link to existing stock item
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="inventory_link_mode" value="awaiting" @checked($linkMode === 'awaiting') onchange="toggleInventoryLinkMode()">
                    Register in inventory (awaiting stock — 0 on hand)
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="inventory_link_mode" value="recipe" @checked($linkMode === 'recipe') onchange="toggleInventoryLinkMode()">
                    Custom recipe (multiple ingredients, e.g. cocktails)
                </label>
            </div>

            <div id="panel-existing" class="space-y-2">
                <label for="linked_stock_item_id" class="block text-xs font-medium text-ink-muted">Stock item</label>
                <select id="linked_stock_item_id" name="linked_stock_item_id" class="input-field">
                    <option value="">— Select stock item —</option>
                    @foreach ($stockItems as $stock)
                        <option value="{{ $stock->id }}" @selected(($linkedStockItemId ?? '') === $stock->id)>
                            {{ $stock->name }}
                            @if ($stock->awaiting_stock)
                                (awaiting stock)
                            @else
                                ({{ $stock->current_stock }} {{ $stock->unit }})
                            @endif
                        </option>
                    @endforeach
                </select>
                @can('manage-inventory')
                    <a href="{{ route('tenant.stock-items.create', ['outlet_id' => request('outlet_id', $item->category?->outlet_id)]) }}" class="text-xs text-primary hover:underline">+ Add new stock item</a>
                @endcan
            </div>

            <div id="panel-awaiting" class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                A matching inventory row will be created at 0 quantity. Receive a purchase order or edit stock when bottles arrive.
            </div>

            <div id="panel-serve-qty" class="grid gap-2 sm:grid-cols-2">
                <div>
                    <label for="serve_quantity" class="mb-1 block text-xs font-medium text-ink-muted">Qty per sale</label>
                    <input id="serve_quantity" type="number" step="0.0001" min="0.0001" name="serve_quantity"
                           value="{{ old('serve_quantity', $serveQuantity ?? 1) }}" class="input-field">
                </div>
                <div>
                    <label for="serve_unit" class="mb-1 block text-xs font-medium text-ink-muted">Unit</label>
                    <input id="serve_unit" name="serve_unit" value="{{ old('serve_unit', $serveUnit ?? 'bottle') }}" class="input-field">
                </div>
            </div>

            @php
                $recipeLines = old('recipe');
                if ($recipeLines === null && $item->relationLoaded('recipeIngredients')) {
                    $recipeLines = $item->recipeIngredients->map(fn ($r) => [
                        'stock_item_id' => $r->stock_item_id,
                        'quantity' => $r->quantity,
                        'unit' => $r->unit,
                    ])->all();
                }
                $recipeLines = $recipeLines ?? [['stock_item_id' => '', 'quantity' => '', 'unit' => 'ml']];
            @endphp

            <div id="panel-recipe" class="space-y-2">
                <p class="text-xs font-medium text-ink-muted">Recipe ingredients</p>
                <div class="space-y-2" id="recipe-lines">
                    @foreach ($recipeLines as $index => $line)
                        <div class="grid gap-2 sm:grid-cols-12 recipe-line">
                            <div class="sm:col-span-6">
                                <select name="recipe[{{ $index }}][stock_item_id]" class="input-field text-sm">
                                    <option value="">— Stock item —</option>
                                    @foreach ($stockItems as $stock)
                                        <option value="{{ $stock->id }}" @selected(($line['stock_item_id'] ?? '') === $stock->id)>
                                            {{ $stock->name }} ({{ $stock->current_stock }} {{ $stock->unit }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-3">
                                <input type="number" step="0.0001" min="0" name="recipe[{{ $index }}][quantity]"
                                       value="{{ $line['quantity'] ?? '' }}" placeholder="Qty" class="input-field text-sm">
                            </div>
                            <div class="sm:col-span-3">
                                <input name="recipe[{{ $index }}][unit]" value="{{ $line['unit'] ?? 'ml' }}"
                                       placeholder="Unit" class="input-field text-sm">
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="text-xs text-primary hover:underline" onclick="addRecipeLine()">+ Add ingredient</button>
            </div>
        </div>
        @endif

        <div class="flex gap-3 pt-2">
            <button type="submit" class="btn-primary">{{ $isEdit ? 'Save' : 'Create' }}</button>
            <a href="{{ route('tenant.menu-items.index') }}" class="btn-outline">Cancel</a>
        </div>
    </form>

    @if ($tracksInventory ?? false)
        <script>
            function toggleInventoryLinkMode() {
                const mode = document.querySelector('input[name="inventory_link_mode"]:checked')?.value;
                document.getElementById('panel-existing').style.display = mode === 'existing' ? 'block' : 'none';
                document.getElementById('panel-awaiting').style.display = mode === 'awaiting' ? 'block' : 'none';
                document.getElementById('panel-recipe').style.display = mode === 'recipe' ? 'block' : 'none';
                document.getElementById('panel-serve-qty').style.display = mode === 'recipe' ? 'none' : 'grid';
            }
            function addRecipeLine() {
                const container = document.getElementById('recipe-lines');
                const index = container.querySelectorAll('.recipe-line').length;
                const template = container.querySelector('.recipe-line');
                const clone = template.cloneNode(true);
                clone.querySelectorAll('select, input').forEach((el) => {
                    const name = el.getAttribute('name');
                    if (name) el.setAttribute('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    if (el.tagName === 'SELECT') el.selectedIndex = 0;
                    else el.value = el.getAttribute('name')?.includes('[unit]') ? 'ml' : '';
                });
                container.appendChild(clone);
            }
            toggleInventoryLinkMode();
        </script>
    @endif
</x-layouts.app>
