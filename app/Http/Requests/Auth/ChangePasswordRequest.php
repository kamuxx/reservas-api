<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "current_password" => "required|string",
            "new_password" => [
                "required",
                "string",
                "min:12",
                "confirmed",
                "regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/"
            ],
        ];
    }

    public function messages(): array
    {
        return [
            "current_password.required" => "La contraseña actual es requerida",
            "new_password.required" => "La nueva contraseña es requerida",
            "new_password.min" => "La nueva contraseña debe tener al menos 12 caracteres",
            "new_password.confirmed" => "La confirmación de la contraseña no coincide",
            "new_password.regex" => "La contraseña debe contener al menos una mayúscula, una minúscula, un número y un símbolo.",
        ];
    }

    public function attributes(): array
    {
        return [
            "current_password" => "Contraseña actual",
            "new_password" => "Nueva contraseña",
        ];
    }
}
