<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-room-types') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('room_type')?->id ?? $this->route('room_type');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', Rule::unique('room_types', 'code')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'max_adults' => ['required', 'integer', 'min:1', 'max:10'],
            'max_children' => ['required', 'integer', 'min:0', 'max:10'],
            'base_rate' => ['required', 'numeric', 'min:0'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['string'],
        ];
    }
}
