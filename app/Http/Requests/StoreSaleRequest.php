<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_sales');
    }

    public function rules(): array
    {
        return [
            'room_id' => ['nullable', 'exists:rooms,id'],
            'shift' => ['required', 'string', Rule::in(['dia', 'noche'])],
            'payment_method' => ['required', 'string', Rule::in(['efectivo', 'transferencia', 'ambos', 'pendiente'])],
            'cash_amount' => ['nullable', 'numeric', 'min:0', 'required_if:payment_method,efectivo,ambos'],
            'transfer_amount' => ['nullable', 'numeric', 'min:0', 'required_if:payment_method,transferencia,ambos'],
            'debt_status' => ['nullable', 'string', Rule::in(['pagado', 'pendiente']), 'required_with:room_id'],
            'sale_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.exists' => 'La habitación seleccionada no existe.',
            'shift.required' => 'El turno es obligatorio.',
            'shift.in' => 'El turno debe ser día o noche.',
            'payment_method.required' => 'El método de pago es obligatorio.',
            'payment_method.in' => 'El método de pago debe ser efectivo, transferencia, ambos o pendiente.',
            'cash_amount.required_if' => 'El monto en efectivo es obligatorio cuando el método de pago es efectivo o ambos.',
            'cash_amount.numeric' => 'El monto en efectivo debe ser un número válido.',
            'cash_amount.min' => 'El monto en efectivo no puede ser negativo.',
            'transfer_amount.required_if' => 'El monto por transferencia es obligatorio cuando el método de pago es transferencia o ambos.',
            'transfer_amount.numeric' => 'El monto por transferencia debe ser un número válido.',
            'transfer_amount.min' => 'El monto por transferencia no puede ser negativo.',
            'debt_status.required_with' => 'El estado de deuda es obligatorio cuando se selecciona una habitación.',
            'debt_status.in' => 'El estado de deuda debe ser pagado o pendiente.',
            'sale_date.required' => 'La fecha de venta es obligatoria.',
            'sale_date.date' => 'La fecha de venta debe ser una fecha válida.',
            'items.required' => 'Debe agregar al menos un producto.',
            'items.min' => 'Debe agregar al menos un producto.',
            'items.*.product_id.required' => 'El producto es obligatorio.',
            'items.*.product_id.exists' => 'El producto seleccionado no existe.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
        ];
    }
}
