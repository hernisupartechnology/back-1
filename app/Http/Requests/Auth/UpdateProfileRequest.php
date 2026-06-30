<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación para actualizar el perfil del usuario autenticado.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone'            => ['nullable', 'string', 'max:20'],
            'birthdate'        => ['nullable', 'date'],
            'avatar'           => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'], // 2MB máx
            'current_password' => ['required_with:password', 'string'],
            'password'         => ['nullable', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'            => 'El nombre es obligatorio.',
            'email.unique'             => 'Este correo electrónico ya está en uso.',
            'avatar.image'             => 'El archivo debe ser una imagen.',
            'avatar.max'               => 'La imagen no puede superar los 2MB.',
            'current_password.required_with' => 'Debes ingresar tu contraseña actual para cambiarla.',
            'password.min'             => 'La nueva contraseña debe tener mínimo 6 caracteres.',
            'password.confirmed'       => 'Las contraseñas no coinciden.',
        ];
    }
}
