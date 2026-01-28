<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Handled by middleware mostly, but extra check if needed
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'capacity' => 'required|integer|min:1',
            'spaces_type_id' => 'required|exists:space_types,uuid',
            'location_id' => 'nullable|exists:locations,uuid',
            'status_id' => 'required|exists:status,uuid',
            'pricing_rule_id' => 'required|exists:pricing_rules,uuid',
            'is_active' => 'boolean',
        ];
    }
}
