<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomDamageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-damage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'room_id' => ['required', 'uuid', 'exists:rooms,id'],
            'reservation_id' => ['nullable', 'uuid', 'exists:reservations,id'],
            'description' => ['required', 'string', 'max:500'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(['reported', 'invoiced', 'resolved'])],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
