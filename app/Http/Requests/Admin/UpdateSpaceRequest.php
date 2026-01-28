<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'capacity' => 'sometimes|integer|min:1',
            'spaces_type_id' => 'sometimes|exists:space_types,uuid',
            'location_id' => 'nullable|exists:locations,uuid',
            'status_id' => 'sometimes|exists:status,uuid',
            'pricing_rule_id' => 'sometimes|exists:pricing_rules,uuid',
            'is_active' => 'boolean',
        ];
    }
}
