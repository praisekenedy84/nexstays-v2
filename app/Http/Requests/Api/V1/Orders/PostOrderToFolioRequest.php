<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Orders;

use Illuminate\Foundation\Http\FormRequest;

class PostOrderToFolioRequest extends FormRequest
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
            'folio_id' => ['required', 'uuid', 'exists:folios,id'],
        ];
    }
}
