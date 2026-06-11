<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFacilitySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-roles');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'pool_default_fee' => ['required', 'numeric', 'min:0'],
            'gym_default_fee'  => ['required', 'numeric', 'min:0'],
        ];
    }
}
