<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Orders;

use App\Domain\Shared\Services\OrderAuthorizationService;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(OrderAuthorizationService::class)->canCreate($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'table_id' => ['nullable', 'uuid', 'exists:outlet_tables,id'],
            'folio_id' => ['nullable', 'uuid', 'exists:folios,id'],
            'covers' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'guest_label' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['sometimes', 'array'],
            'items.*.menu_item_id' => ['required_with:items', 'uuid', 'exists:menu_items,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:99'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
