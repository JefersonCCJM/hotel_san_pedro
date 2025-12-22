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
            'room_id'          => 'required|exists:rooms,id',
            'guests_count'     => 'required|integer|min:1',
            'total_amount'     => 'required|numeric|min:0',
            'deposit'          => 'required|numeric|min:0',
            'reservation_date' => 'required|date',
            'check_in_date'    => 'required|date|after_or_equal:today',
            'check_out_date'   => 'required|date|after:check_in_date',
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
            'check_in_date.after_or_equal' => 'No se puede ingresar una reserva antes del día actual.',
        ];
    }
}
