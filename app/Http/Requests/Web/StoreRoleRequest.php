<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Support\NavigationMenuRegistry;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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
            'name' => [
                'required', 'string', 'max:100',
                'regex:/^[a-z0-9_]+$/',
                'unique:roles,name',
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(RoleAndPermissionSeeder::PERMISSIONS)],
            'navigation' => ['nullable', 'array'],
            'navigation.*' => ['string', Rule::in(NavigationMenuRegistry::allItemIds())],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Role name may only contain lowercase letters, numbers, and underscores.',
        ];
    }
}
