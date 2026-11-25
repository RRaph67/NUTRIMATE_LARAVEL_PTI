<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\RedirectsToAuthModal;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    use RedirectsToAuthModal;

    public function authorize(): bool
    {
        return true;
    }

    protected function authPanel(): string
    {
        return 'login';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }
}
