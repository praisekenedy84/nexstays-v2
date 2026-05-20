<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\HBMS;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostFolioChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('post-folio-charges') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['room_charge', 'restaurant', 'bar', 'lounge', 'misc'])],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'meta' => ['sometimes', 'array'],
            'meta.reference_id' => ['sometimes', 'uuid'],
            'meta.reference_type' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
