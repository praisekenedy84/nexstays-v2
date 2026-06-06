<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Orders;

use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Services\OrderAuthorizationService;
use Illuminate\Foundation\Http\FormRequest;

class AddOrderItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $order = $this->route('order');

        return $user !== null
            && $order instanceof Order
            && app(OrderAuthorizationService::class)->canManage($user, $order);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'uuid', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
