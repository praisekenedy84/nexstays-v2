<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-reservations') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_mode' => ['required', 'string', Rule::in(['prepaid'])],
            'cancellation_policy' => ['required', 'string', Rule::in(['stayed_nights', 'percentage_refund', 'no_refund'])],
            'refund_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
