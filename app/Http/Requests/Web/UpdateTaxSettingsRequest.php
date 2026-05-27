<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-roles') ?? false;
    }

    public function rules(): array
    {
        return [
            'tax_inclusive'    => ['required', 'in:0,1'],
            'vat_rate'         => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_code'         => ['required', 'string', 'max:10'],
            'rate_room_charge' => ['required', 'numeric', 'min:0', 'max:100'],
            'rate_restaurant'  => ['required', 'numeric', 'min:0', 'max:100'],
            'rate_bar'         => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'vat_rate.max'         => 'VAT rate must be between 0 and 100 (percent).',
            'rate_room_charge.max' => 'Room charge rate must be between 0 and 100 (percent).',
            'rate_restaurant.max'  => 'Restaurant rate must be between 0 and 100 (percent).',
            'rate_bar.max'         => 'Bar rate must be between 0 and 100 (percent).',
        ];
    }
}
