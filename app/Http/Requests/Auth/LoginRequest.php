<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
        $passRegex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
        return [
            "email" => "required|email|exists:users,email",
            "password" => "required|min:8|regex:" . $passRegex,
        ];
    }

    public function messages(): array
    {
        return [
            "email.required" => "El correo electrónico es requerido",
            "email.email" => "El correo electrónico no es válido",
            "email.exists" => "Las credenciales son incorrectas",
            "password.required" => "La contraseña es requerida",
            "password.min" => "La contraseña debe tener al menos 8 caracteres",
            "password.regex" => "Las credenciales son incorrectas",
        ];
    }

    public function attributes(): array
    {
        return [
            "email" => "Correo electrónico",
            "password" => "Contraseña",
        ];
    }
}
