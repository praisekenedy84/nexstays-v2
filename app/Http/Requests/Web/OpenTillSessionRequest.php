<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class OpenTillSessionRequest extends FormRequest
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
        return [
            'outlet_id' => ['nullable', 'uuid', 'exists:outlets,id'],
            'float_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
