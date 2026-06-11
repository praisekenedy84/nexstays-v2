<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Domain\Facilities\Models\FacilityAttendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RecordFacilityAttendanceRequest extends FormRequest
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
        $settlement = $this->input('settlement');

        return [
            'facility_type' => ['required', Rule::in(FacilityAttendance::TYPES)],
            'visitor_type' => ['required', Rule::in(['walk_in', 'hotel_guest'])],
            'visitor_name' => [
                Rule::requiredIf(fn () => $this->input('visitor_type') === 'walk_in'),
                'nullable',
                'string',
                'max:150',
            ],
            'reservation_id' => [
                Rule::requiredIf(fn () => $this->input('visitor_type') === 'hotel_guest'),
                'nullable',
                'uuid',
                'exists:reservations,id',
            ],
            'party_size' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'amount' => [
                Rule::requiredIf(fn () => $settlement !== 'complimentary'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'settlement' => ['required', Rule::in(FacilityAttendance::SETTLEMENTS)],
            'till_session_id' => [
                'nullable',
                'uuid',
                'exists:till_sessions,id',
            ],
            'cash_tendered' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'transaction_ref' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('visitor_type') === 'walk_in' && $this->input('settlement') === 'folio') {
                $validator->errors()->add('settlement', 'Walk-in visitors cannot be charged to a room folio.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if (! is_array($data)) {
            return $data;
        }

        unset($data['visitor_type']);

        return $data;
    }
}
