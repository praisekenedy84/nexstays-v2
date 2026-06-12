<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class VoidFacilityAttendanceRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
