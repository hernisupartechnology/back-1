<?php

namespace App\Http\Requests\Household;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para crear un hogar nuevo.
 */
class CreateHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'avatar'      => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del hogar es obligatorio.',
            'name.max'      => 'El nombre no puede superar los 100 caracteres.',
        ];
    }
}
