<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'property_code' => ['required', 'string', 'max:100'],
            'username' => ['required_without:email', 'nullable', 'string', 'max:255'],
            'email' => ['required_without:username', 'nullable', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ];
    }

    public function loginIdentifier(): string
    {
        return (string) ($this->validated('username') ?? $this->validated('email'));
    }
}
