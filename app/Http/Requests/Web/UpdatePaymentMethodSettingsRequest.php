<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Domain\Shared\Services\PaymentMethodSettingsService;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentMethodSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-roles');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $validMethods = implode(',', array_keys(PaymentMethodSettingsService::METHODS));

        return [
            'enabled'   => ['nullable', 'array'],
            'enabled.*' => ['string', "in:{$validMethods}"],
        ];
    }
}
