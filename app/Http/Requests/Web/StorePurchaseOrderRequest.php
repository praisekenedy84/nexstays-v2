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

    protected function prepareForValidation(): void
    {
        // Strip blank rows so validation doesn't fire on empty template rows
        $lines = collect($this->input('lines', []))
            ->filter(fn (array $line) => ! empty($line['stock_item_id']))
            ->values()
            ->all();

        $this->merge([
            'receive_now' => $this->boolean('receive_now'),
            'lines'       => $lines,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'department'               => ['required', 'string', Rule::in(['kitchen', 'bar'])],
            'supplier_name'            => ['required', 'string', 'max:150'],
            'supplier_reference'       => ['nullable', 'string', 'max:100'],
            'outlet_id'                => ['nullable', 'uuid', 'exists:outlets,id'],
            'delivery_date_expected'   => ['nullable', 'date'],
            'payment_terms'            => ['nullable', 'string', 'max:100'],
            'notes'                    => ['nullable', 'string', 'max:2000'],
            'receive_now'              => ['sometimes', 'boolean'],
            'lines'                    => ['required', 'array', 'min:1'],
            'lines.*.stock_item_id'    => ['required', 'uuid', 'exists:stock_items,id'],
            'lines.*.quantity'         => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_cost'        => ['required', 'numeric', 'min:0'],
            'lines.*.line_notes'       => ['nullable', 'string', 'max:500'],
        ];
    }
}
