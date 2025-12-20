<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit_sales');
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', Rule::in(['efectivo', 'transferencia', 'ambos', 'pendiente'])],
            'cash_amount' => ['nullable', 'numeric', 'min:0', 'required_if:payment_method,efectivo,ambos'],
            'transfer_amount' => ['nullable', 'numeric', 'min:0', 'required_if:payment_method,transferencia,ambos'],
            'debt_status' => ['nullable', 'string', Rule::in(['pagado', 'pendiente'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'El método de pago es obligatorio.',
            'payment_method.in' => 'El método de pago debe ser efectivo, transferencia, ambos o pendiente.',
            'cash_amount.required_if' => 'El monto en efectivo es obligatorio cuando el método de pago es efectivo o ambos.',
            'cash_amount.numeric' => 'El monto en efectivo debe ser un número válido.',
            'cash_amount.min' => 'El monto en efectivo no puede ser negativo.',
            'transfer_amount.required_if' => 'El monto por transferencia es obligatorio cuando el método de pago es transferencia o ambos.',
            'transfer_amount.numeric' => 'El monto por transferencia debe ser un número válido.',
            'transfer_amount.min' => 'El monto por transferencia no puede ser negativo.',
            'debt_status.in' => 'El estado de deuda debe ser pagado o pendiente.',
        ];
    }
}
