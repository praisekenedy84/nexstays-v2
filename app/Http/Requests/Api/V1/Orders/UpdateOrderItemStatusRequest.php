<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Orders;

use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Services\OrderAuthorizationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderItemStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $order = $this->route('order');

        return $user !== null
            && $order instanceof Order
            && app(OrderAuthorizationService::class)->canUpdateItemStatus($user, $order);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_item_id' => ['required', 'uuid', 'exists:order_items,id'],
            'status' => ['required', Rule::in(['sent', 'preparing', 'ready', 'served', 'voided'])],
            'reason' => ['nullable', 'string', 'max:120'],
        ];
    }
}
