<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Domain\Shared\Services\PaymentMethodSettingsService;
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
        $enabled = app(PaymentMethodSettingsService::class)->enabledMethods();

        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', Rule::in($enabled !== [] ? $enabled : ['__none__'])],
            'notes'  => ['nullable', 'string', 'max:500'],
        ];
    }
}
