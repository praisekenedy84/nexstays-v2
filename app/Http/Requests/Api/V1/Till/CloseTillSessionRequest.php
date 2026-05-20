<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Till;

use Illuminate\Foundation\Http\FormRequest;

class CloseTillSessionRequest extends FormRequest
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
            'declared_cash' => ['required', 'numeric', 'min:0'],
            'manager_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
