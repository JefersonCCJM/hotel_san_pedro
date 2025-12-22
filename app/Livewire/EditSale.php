<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;

class EditSale extends Component
{
    public Sale $sale;
    public $payment_method;
    public $cash_amount;
    public $transfer_amount;
    public $debt_status;
    public $notes;

    protected $rules = [
        'payment_method' => 'required|string|in:efectivo,transferencia,ambos,pendiente',
        'cash_amount' => 'nullable|numeric|min:0',
        'transfer_amount' => 'nullable|numeric|min:0',
        'debt_status' => 'nullable|string|in:pagado,pendiente',
        'notes' => 'nullable|string|max:1000',
    ];

    protected $messages = [
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

    public function mount(Sale $sale)
    {
        $this->sale = $sale->load(['user', 'room', 'items.product']);
        $this->payment_method = $sale->payment_method;
        $this->cash_amount = $sale->cash_amount;
        $this->transfer_amount = $sale->transfer_amount;
        $this->debt_status = $sale->debt_status;
        $this->notes = $sale->notes;
    }

    public function updatedPaymentMethod()
    {
        if ($this->payment_method === 'pendiente') {
            $this->cash_amount = null;
            $this->transfer_amount = null;
            if ($this->sale->room_id) {
                $this->debt_status = 'pendiente';
            }
        } elseif ($this->payment_method === 'efectivo') {
            $this->cash_amount = $this->sale->total;
            $this->transfer_amount = null;
            if ($this->sale->room_id && $this->debt_status === 'pendiente') {
                $this->debt_status = 'pagado';
            }
        } elseif ($this->payment_method === 'transferencia') {
            $this->cash_amount = null;
            $this->transfer_amount = $this->sale->total;
            if ($this->sale->room_id && $this->debt_status === 'pendiente') {
                $this->debt_status = 'pagado';
            }
        } elseif ($this->payment_method === 'ambos') {
            // Keep current values or set defaults
            if (!$this->cash_amount && !$this->transfer_amount) {
                $this->cash_amount = $this->sale->total / 2;
                $this->transfer_amount = $this->sale->total / 2;
            }
        }
    }

    public function validateBeforeSubmit()
    {
        // Validate payment amounts for "ambos" method
        if ($this->payment_method === 'ambos') {
            $this->validate([
                'cash_amount' => 'required|numeric|min:0',
                'transfer_amount' => 'required|numeric|min:0',
            ], [
                'cash_amount.required' => 'El monto en efectivo es obligatorio cuando el método de pago es "Ambos".',
                'transfer_amount.required' => 'El monto por transferencia es obligatorio cuando el método de pago es "Ambos".',
            ]);

            $sum = $this->cash_amount + $this->transfer_amount;
            if (abs($sum - $this->sale->total) > 0.01) {
                $this->addError('payment_method', "La suma de efectivo y transferencia debe ser igual al total: $" . number_format($this->sale->total, 2, ',', '.'));
                return false;
            }
        }

        return true;
    }

    public function render()
    {
        return view('livewire.edit-sale');
    }
}
