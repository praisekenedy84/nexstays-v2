<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Domain\Shared\Services\PaymentMethodSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettleOverstayRequest extends FormRequest
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
            'settlement'     => ['required', 'string', Rule::in(['paid', 'waived'])],
            'method'         => ['required_if:settlement,paid', 'nullable', 'string', Rule::in($enabled !== [] ? $enabled : ['__none__'])],
            'waiver_reason'  => ['required_if:settlement,waived', 'nullable', 'string', 'max:500'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ];
    }
}
