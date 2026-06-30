<?php

namespace App\Http\Requests\Household;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para invitar un nuevo miembro al hogar.
 * El email es opcional para menores sin cuenta de correo.
 */
class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'        => ['nullable', 'string', 'email', 'max:255'],
            'name'         => ['required_without:email', 'nullable', 'string', 'max:255'],
            'role_assigned' => ['required', 'in:member,viewer'],
            'is_minor'     => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'role_assigned.required' => 'El rol del nuevo miembro es obligatorio.',
            'role_assigned.in'       => 'El rol debe ser "member" o "viewer".',
            'email.email'            => 'El correo electrónico no tiene un formato válido.',
            'name.required_without'  => 'El nombre es obligatorio si no se proporciona un correo.',
        ];
    }
}
