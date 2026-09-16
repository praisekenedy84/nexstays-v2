<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Support\NavigationMenuRegistry;
use App\Support\TenantFeatures;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-roles') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(TenantFeatures::allowedPermissions())],
            'navigation' => ['nullable', 'array'],
            'navigation.*' => ['string', Rule::in(NavigationMenuRegistry::allItemIds())],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.*.in' => 'One or more permissions are not available for this property.',
        ];
    }
}
