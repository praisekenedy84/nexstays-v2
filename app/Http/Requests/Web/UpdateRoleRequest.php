<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Support\NavigationMenuRegistry;
use Database\Seeders\RoleAndPermissionSeeder;
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
            'permissions.*' => ['string', Rule::in(RoleAndPermissionSeeder::PERMISSIONS)],
            'navigation' => ['nullable', 'array'],
            'navigation.*' => ['string', Rule::in(NavigationMenuRegistry::allItemIds())],
        ];
    }
}
