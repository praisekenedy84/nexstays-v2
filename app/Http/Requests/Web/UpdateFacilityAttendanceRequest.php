<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFacilityAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('record-facility-attendance') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'visitor_name' => ['nullable', 'string', 'max:150'],
            'party_size' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
