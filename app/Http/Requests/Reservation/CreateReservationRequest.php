<?php

namespace App\Http\Requests\Reservation;

use Illuminate\Foundation\Http\FormRequest;

class CreateReservationRequest extends FormRequest
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
            'space_id' => 'required|string|exists:spaces,uuid',
            'event_name' => 'required|string|max:500',
            'event_description' => 'nullable|string',
            'event_date' => 'required|date|date_format:Y-m-d|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ];
    }

    public function messages(): array
    {
        return [
            'space_id.required' => 'El espacio es obligatorio.',
            'space_id.exists' => 'El espacio seleccionado no es válido.',
            'event_name.required' => 'El nombre del evento es obligatorio.',
            'event_date.required' => 'La fecha del evento es obligatoria.',
            'event_date.after_or_equal' => 'La fecha del evento debe ser hoy o una fecha futura.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'end_time.required' => 'La hora de fin es obligatoria.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
