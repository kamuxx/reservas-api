<?php

namespace App\Http\Requests\Space;

use Illuminate\Foundation\Http\FormRequest;

class ListSpacesRequest extends FormRequest
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
            'capacity' => 'nullable|integer|min:1',
            'spaces_type_id' => 'nullable|string|exists:space_types,uuid',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
