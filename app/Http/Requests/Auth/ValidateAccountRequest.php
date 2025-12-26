<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ValidateAccountRequest extends FormRequest
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
            "token" => "required|string",
            "activation_code" => "required|digits:6",
        ];
    }

    public function messages(): array
    {
        return [
            "token.required" => "El token es obligatorio",
            "activation_code.required" => "El codigo de activacion es obligatorio",
            "activation_code.digits" => "El codigo de activacion debe tener 6 digitos",
        ];
    }

    public function attributes(): array
    {
        return [
            "token" => "Token",
            "activation_code" => "Codigo de activacion",
        ];
    }
}
