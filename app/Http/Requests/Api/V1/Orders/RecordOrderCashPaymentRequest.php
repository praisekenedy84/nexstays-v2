<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Orders;

use App\Domain\Shared\Models\Order;
use App\Domain\Shared\Services\OrderAuthorizationService;
use App\Http\Rules\ActiveTillSessionForOutlet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordOrderCashPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $order = $this->route('order');

        return $user !== null
            && $user->can('manage-till')
            && $order instanceof Order
            && app(OrderAuthorizationService::class)->canManage($user, $order);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var \App\Domain\Shared\Models\Order $order */
        $order = $this->route('order');

        return [
            'till_session_id' => [
                'nullable',
                'uuid',
                'exists:till_sessions,id',
                Rule::when($this->filled('till_session_id'), [new ActiveTillSessionForOutlet($order)]),
            ],
            'amount_tendered' => ['required', 'numeric', 'min:'.$order->total],
        ];
    }
}
