<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-menu') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'uuid', 'exists:menu_categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_available' => ['sometimes', 'boolean'],
            'outlet_filter' => ['nullable', 'uuid'],
            'recipe' => ['sometimes', 'array'],
            'recipe.*.stock_item_id' => ['nullable', 'uuid', 'exists:stock_items,id'],
            'recipe.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'recipe.*.unit' => ['nullable', 'string', 'max:20'],
            'inventory_link_mode' => ['nullable', 'string', 'in:existing,awaiting,recipe'],
            'linked_stock_item_id' => ['nullable', 'uuid', 'exists:stock_items,id'],
            'serve_quantity' => ['nullable', 'numeric', 'min:0.0001'],
            'serve_unit' => ['nullable', 'string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_available' => $this->boolean('is_available', true),
        ]);
    }
}
