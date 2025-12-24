<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
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
        $regexPass = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
        return [
            "name" => "required|string|max:255",
            "email" => "required|string|email|max:255|unique:users",
            "password" => "required|string|min:8|confirmed|regex:".$regexPass,  
            "phone" => "required|string|max:20",
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => "El nombre es requerido",
            "name.max" => "El nombre no puede exceder los 255 caracteres",
            "email.required" => "El correo electrónico es requerido",
            "email.email" => "El correo electrónico no es válido",
            "email.max" => "El correo electrónico no puede exceder los 255 caracteres",
            "email.unique" => "El correo electrónico ya está en uso",
            "password.required" => "La contraseña es requerida",
            "password.min" => "La contraseña debe tener al menos 8 caracteres",
            "password.confirmed" => "La confirmación de contraseña no coincide",
            "password.regex" => "La contraseña debe contener al menos una letra minúscula, una letra mayúscula, un número y un carácter especial [@$!%*?&]",
            "phone.required" => "El número de teléfono es requerido",
            "phone.max" => "El número de teléfono no puede exceder los 20 caracteres",
        ];
    }

    public function attributes(): array
    {
        return [
            "name" => "Nombre",
            "email" => "Correo electrónico",
            "password" => "Contraseña",
            "phone" => "Número de teléfono",
        ];
    }
}
