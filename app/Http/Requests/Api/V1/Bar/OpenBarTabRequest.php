<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Bar;

use Illuminate\Foundation\Http\FormRequest;

class OpenBarTabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-orders') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'guest_label' => ['required', 'string', 'max:100'],
            'folio_id' => ['nullable', 'uuid', 'exists:folios,id'],
        ];
    }
}
