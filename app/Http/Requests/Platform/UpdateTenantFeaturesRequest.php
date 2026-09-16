<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Support\TenantFeatures;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantFeaturesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('platform_admin') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'features' => ['nullable', 'array'],
            'features.*' => ['string', Rule::in(TenantFeatures::keys())],
        ];
    }
}
