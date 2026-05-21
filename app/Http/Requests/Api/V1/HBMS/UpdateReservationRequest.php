<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\HBMS;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
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
            'room_id' => ['sometimes', 'uuid', 'exists:rooms,id'],
            'check_in_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'check_out_date' => ['sometimes', 'date', 'after:check_in_date'],
            'adults' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'children' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'rate_plan_id' => ['sometimes', 'nullable', 'uuid', 'exists:rate_plans,id'],
            'special_requests' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'deposit_amount' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
