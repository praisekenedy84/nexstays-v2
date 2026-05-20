<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Orders;

use App\Http\Rules\ActiveTillSessionForOutlet;
use Illuminate\Foundation\Http\FormRequest;

class RecordOrderCashPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-till') ?? false;
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
                'required',
                'uuid',
                'exists:till_sessions,id',
                new ActiveTillSessionForOutlet($order),
            ],
            'amount_tendered' => ['required', 'numeric', 'min:'.$order->total],
        ];
    }
}
