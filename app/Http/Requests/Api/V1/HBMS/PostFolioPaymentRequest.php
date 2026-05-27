<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\HBMS;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostFolioPaymentRequest extends FormRequest
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
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'method'          => ['required', 'string', Rule::in(['cash', 'card', 'bank_transfer', 'mobile_money', 'other'])],
            'till_session_id' => ['nullable', 'uuid'],
            'notes'           => ['nullable', 'string', 'max:500'],
        ];
    }
}
