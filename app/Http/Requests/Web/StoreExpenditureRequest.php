<?php

declare(strict_types=1);

namespace App\Http\Requests\Web;

use App\Domain\Expenditures\Models\Expenditure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenditureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-expenditures') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(Expenditure::CATEGORIES)],
            'outlet_id' => ['nullable', 'uuid', 'exists:outlets,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'expense_date' => ['required', 'date'],
        ];
    }
}
