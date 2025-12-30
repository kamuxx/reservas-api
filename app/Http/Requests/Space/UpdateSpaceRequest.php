<?php

namespace App\Http\Requests\Space;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpaceRequest extends FormRequest
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
        $space = $this->route('space');
        return [
            'name' => "sometimes|string|max:255|unique:spaces,name,{$space->uuid},uuid",
            'capacity' => 'sometimes|integer|min:1',
            'description' => 'sometimes|nullable|string|max:255',
            'spaces_type_id' => 'sometimes|exists:space_types,uuid',
            'status_id' => 'sometimes|exists:status,uuid',
            'pricing_rule_id' => 'sometimes|exists:pricing_rules,uuid',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'El nombre del espacio ya está en uso.',
            'name.string' => 'El nombre del espacio debe ser una cadena de texto.',
            'name.max' => 'El nombre del espacio no puede exceder los 255 caracteres.',
            'capacity.integer' => 'La capacidad del espacio debe ser un número entero mayor o igual a 1.',
            'capacity.min' => 'La capacidad mínima es de 1 persona.',
            'description.string' => 'La descripción del espacio debe ser una cadena de texto.',
            'description.max' => 'La descripción del espacio no puede exceder los 255 caracteres.',
            'spaces_type_id.exists' => 'El tipo de espacio no es válido.',
            'status_id.exists' => 'El estado del espacio no es válido.',
            'pricing_rule_id.exists' => 'La regla de precios no es válida.',
            'is_active.boolean' => 'El estado de activación del espacio debe ser un valor booleano.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del espacio',
            'capacity' => 'capacidad',
            'description' => 'descripción',
            'spaces_type_id' => 'tipo de espacio',
            'status_id' => 'estado',
            'pricing_rule_id' => 'regla de precio',
            'is_active' => 'activo',
        ];
    }
}
