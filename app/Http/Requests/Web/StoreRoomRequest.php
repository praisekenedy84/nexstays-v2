<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-rooms') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'room_type_id' => ['required', 'uuid', 'exists:room_types,id'],
            'room_number' => ['required', 'string', 'max:10', 'unique:rooms,room_number'],
            'floor' => ['nullable', 'integer', 'min:0', 'max:99'],
            'status' => ['required', 'string', Rule::in(['vacant_clean', 'vacant_dirty', 'occupied', 'out_of_order', 'blocked'])],
            'is_smoking' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_smoking' => $this->boolean('is_smoking')]);
    }
}
