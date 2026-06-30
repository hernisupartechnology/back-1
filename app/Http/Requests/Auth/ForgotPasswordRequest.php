<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para solicitar recuperación de contraseña.
 */
class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'exists:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email'    => 'El correo electrónico no tiene un formato válido.',
            'email.exists'   => 'No encontramos una cuenta con ese correo electrónico.',
        ];
    }
}
