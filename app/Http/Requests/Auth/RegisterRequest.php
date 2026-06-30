<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para el registro de nuevos usuarios.
 * El email es nullable para permitir menores sin cuenta de correo.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['required'],
            'phone'                 => ['nullable', 'string', 'max:20'],
            'birthdate'             => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'El nombre es obligatorio.',
            'email.email'        => 'El correo electrónico no tiene un formato válido.',
            'email.unique'       => 'Este correo electrónico ya está registrado.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener mínimo 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
