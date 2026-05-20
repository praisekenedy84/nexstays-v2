<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-purchases') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'department' => ['required', 'string', Rule::in(['kitchen', 'bar'])],
            'supplier_name' => ['required', 'string', 'max:150'],
            'outlet_id' => ['nullable', 'uuid', 'exists:outlets,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'receive_now' => ['sometimes', 'boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.stock_item_id' => ['required', 'uuid', 'exists:stock_items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'receive_now' => $this->boolean('receive_now'),
        ]);
    }
}
