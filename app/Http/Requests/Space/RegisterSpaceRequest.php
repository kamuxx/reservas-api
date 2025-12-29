<?php

namespace App\Http\Requests\Space;

use Illuminate\Foundation\Http\FormRequest;

class RegisterSpaceRequest extends FormRequest
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
            "name" => "required|string|unique:spaces,name|max:255",
            "description" => "nullable|string|max:255",
            "capacity" => "required|integer|min:1",
            "spaces_type_id" => "required|string|exists:space_types,uuid",
            "status_id" => "required|string|exists:status,uuid",
            "pricing_rule_id" => "required|string|exists:pricing_rules,uuid",
            "is_active" => "boolean",
        ];
    }

    public function attributes(): array
    {
        return [
            "name" => "nombre del espacio",
            "description" => "descripción",
            "capacity" => "capacidad",
            "spaces_type_id" => "tipo de espacio",
            "status_id" => "estado",
            "pricing_rule_id" => "regla de precio",
            "is_active" => "activo",
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => "El nombre del espacio es obligatorio.",
            "name.max" => "El nombre no debe exceder los 255 caracteres.",
            "description.max" => "La descripción no debe exceder los 255 caracteres.",
            "capacity.required" => "La capacidad es obligatoria.",
            "capacity.integer" => "La capacidad debe ser un número entero.",
            "capacity.min" => "La capacidad debe ser al menos 1.",
            "spaces_type_id.required" => "El tipo de espacio es obligatorio.",
            "spaces_type_id.exists" => "El tipo de espacio seleccionado no es válido.",
            "status_id.required" => "El estado es obligatorio.",
            "status_id.exists" => "El estado seleccionado no es válido.",
            "pricing_rule_id.required" => "La regla de precio es obligatoria.",
            "pricing_rule_id.exists" => "La regla de precio seleccionada no es válida.",
        ];
    }
}
