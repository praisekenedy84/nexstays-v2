<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\HBMS;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('check-out-guests') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
