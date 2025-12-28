<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
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
            'customer_id'      => 'required|exists:customers,id',
            'room_id'          => 'required_without:room_ids|nullable|exists:rooms,id',
            'room_ids'         => 'required_without:room_id|nullable|array|min:1',
            'room_ids.*'       => 'required|integer|exists:rooms,id',
            'room_guests'      => 'nullable|array',
            'room_guests.*'    => 'nullable|array',
            'room_guests.*.*'  => 'nullable|integer|exists:customers,id',
            'guests_count'     => 'required|integer|min:1',
            'total_amount'     => 'required|numeric|min:0',
            'deposit'          => 'required|numeric|min:0',
            'reservation_date' => 'required|date',
            'check_in_date'    => 'required|date|after_or_equal:today',
            'check_out_date'   => 'required|date|after:check_in_date',
            'check_in_time'    => ['nullable', 'regex:/^([0-1]\d|2[0-3]):[0-5]\d$/'],
            'notes'            => 'nullable|string',
            'payment_method'   => 'nullable|string|in:efectivo,transferencia',
            'guest_ids'        => 'nullable|array',
            'guest_ids.*'      => 'nullable|integer|exists:customers,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Debe seleccionar un cliente.',
            'customer_id.exists' => 'El cliente seleccionado no existe.',
            'room_id.required_without' => 'Debe seleccionar al menos una habitación.',
            'room_id.exists' => 'La habitación seleccionada no existe.',
            'room_ids.required_without' => 'Debe seleccionar al menos una habitación.',
            'room_ids.array' => 'Las habitaciones deben ser un array válido.',
            'room_ids.min' => 'Debe seleccionar al menos una habitación.',
            'room_ids.*.required' => 'Cada habitación debe ser válida.',
            'room_ids.*.integer' => 'Cada habitación debe ser un número válido.',
            'room_ids.*.exists' => 'Una o más habitaciones seleccionadas no existen.',
            'guests_count.required' => 'Debe asignar al menos un huésped.',
            'guests_count.integer' => 'El número de huéspedes debe ser un número válido.',
            'guests_count.min' => 'Debe asignar al menos un huésped.',
            'total_amount.required' => 'El monto total es obligatorio.',
            'total_amount.numeric' => 'El monto total debe ser un número válido.',
            'total_amount.min' => 'El monto total no puede ser negativo.',
            'deposit.required' => 'El abono inicial es obligatorio.',
            'deposit.numeric' => 'El abono inicial debe ser un número válido.',
            'deposit.min' => 'El abono inicial no puede ser negativo.',
            'reservation_date.required' => 'La fecha de reserva es obligatoria.',
            'reservation_date.date' => 'La fecha de reserva debe ser una fecha válida.',
            'check_in_date.required' => 'La fecha de check-in es obligatoria.',
            'check_in_date.date' => 'La fecha de check-in debe ser una fecha válida.',
            'check_in_date.after_or_equal' => 'No se puede ingresar una reserva antes del día actual.',
            'check_out_date.required' => 'La fecha de check-out es obligatoria.',
            'check_out_date.date' => 'La fecha de check-out debe ser una fecha válida.',
            'check_out_date.after' => 'La fecha de check-out debe ser posterior a la fecha de check-in.',
            'check_in_time.regex' => 'La hora de ingreso debe tener el formato HH:MM (24 horas).',
        ];
    }
}
