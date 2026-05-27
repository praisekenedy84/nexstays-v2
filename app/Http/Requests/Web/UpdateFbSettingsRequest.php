<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Domain\Shared\Services\FbSettingsService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFbSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-roles');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $valid = implode(',', array_keys(FbSettingsService::SETTLEMENT_MODES));

        return [
            'settlement_mode' => ['required', 'string', "in:{$valid}"],
        ];
    }
}
