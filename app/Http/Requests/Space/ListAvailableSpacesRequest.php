<?php

namespace App\Http\Requests\Space;

use Illuminate\Foundation\Http\FormRequest;

class ListAvailableSpacesRequest extends FormRequest
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
            'fecha_deseada' => 'required|date_format:Y-m-d',
            'space_type_id' => 'nullable|uuid|exists:space_types,uuid',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'fecha_deseada.required' => 'El parámetro fecha_deseada es obligatorio.',
            'fecha_deseada.date_format' => 'El formato de fecha_deseada debe ser YYYY-MM-DD.',
            'space_type_id.exists' => 'El tipo de espacio seleccionado no es válido.',
        ];
    }
}
