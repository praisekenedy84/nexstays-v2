<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\HBMS;

use Illuminate\Foundation\Http\FormRequest;

class PostFolioWriteOffRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:500'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }
}
