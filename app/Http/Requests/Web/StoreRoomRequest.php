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
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'amenities' => ['sometimes', 'array'],
            'amenities.*' => ['string', 'max:60'],
            'features' => ['sometimes', 'array'],
            'features.*' => ['string', 'max:60'],
            'photos' => ['sometimes', 'array', 'max:8'],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_smoking' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_smoking' => $this->boolean('is_smoking'),
            'amenities' => $this->normalizeList($this->input('amenities_text', $this->input('amenities'))),
            'features' => $this->normalizeList($this->input('features_text', $this->input('features'))),
        ]);
    }

    /**
     * @param string|array<int, mixed>|null $value
     * @return array<int, string>
     */
    private function normalizeList(string|array|null $value): array
    {
        $items = is_array($value) ? $value : (preg_split('/[\r\n,]+/', (string) $value) ?: []);

        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
