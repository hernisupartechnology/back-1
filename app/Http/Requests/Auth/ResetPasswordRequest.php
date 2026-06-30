<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para resetear la contraseña con el token recibido por email.
 */
class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'                 => ['required', 'string'],
            'email'                 => ['required', 'string', 'email', 'exists:users,email'],
            'password'              => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'     => 'El token de recuperación es obligatorio.',
            'email.exists'       => 'No encontramos una cuenta con ese correo electrónico.',
            'password.min'       => 'La nueva contraseña debe tener mínimo 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
