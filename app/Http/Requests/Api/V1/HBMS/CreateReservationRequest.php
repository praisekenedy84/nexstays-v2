<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\HBMS;

use App\Domain\Shared\Services\PaymentMethodSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReservationRequest extends FormRequest
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
            'guest_id' => ['required', 'uuid', 'exists:guests,id'],
            'room_type_id' => ['sometimes', 'nullable', 'uuid', 'exists:room_types,id'],
            'room_id' => ['required', 'uuid', 'exists:rooms,id'],
            'rate_plan_id' => ['nullable', 'uuid', 'exists:rate_plans,id'],
            'check_in_date' => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'adults' => ['required', 'integer', 'min:1', 'max:10'],
            'children' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'source' => [
                'required',
                'string',
                Rule::in(app(PaymentMethodSettingsService::class)->enabledMethods()),
            ],
            'ota_ref' => ['sometimes', 'string', 'max:100'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            'deposit_amount' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in(['inquiry', 'confirmed'])],
        ];
    }
}
