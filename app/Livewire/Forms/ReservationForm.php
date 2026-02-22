<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ReservationForm extends Form
{
    #[Validate('required|integer|exists:customers,id')]
    public int $customerId = 0;

    #[Validate('required|date|after_or_equal:today')]
    public string $checkIn = '';

    #[Validate('required|date|after:checkIn')]
    public string $checkOut = '';

    #[Validate('required|array|min:1')]
    public array $selectedRoomIds = [];

    #[Validate('nullable|array')]
    public array $roomGuests = [];

    #[Validate('nullable|integer|min:0')]
    public $adults = null;

    #[Validate('nullable|integer|min:0')]
    public $children = null;

    #[Validate('required|integer|min:1')]
    public $total = 0;

    #[Validate('required|integer|min:0')]
    public $deposit = 0;

    #[Validate('required|string|in:efectivo,transferencia')]
    public string $paymentMethod = 'efectivo';

    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    public function rules(): array
    {
        return [
            'customerId' => 'required|integer|exists:customers,id',
            'checkIn' => 'required|date|after_or_equal:today',
            'checkOut' => 'required|date|after:checkIn',
            'selectedRoomIds' => 'required|array|min:1',
            'selectedRoomIds.*' => 'integer|exists:rooms,id',
            'adults' => 'nullable|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'total' => 'required|integer|min:1',
            'deposit' => 'required|integer|min:0',
            'paymentMethod' => 'required|string|in:efectivo,transferencia',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'customerId.required' => 'Por favor selecciona un cliente.',
            'customerId.exists' => 'El cliente seleccionado no existe.',
            'checkIn.required' => 'La fecha de check-in es obligatoria.',
            'checkIn.date' => 'La fecha de check-in debe ser una fecha valida.',
            'checkIn.after_or_equal' => 'La fecha de check-in debe ser hoy o una fecha futura.',
            'checkOut.required' => 'La fecha de check-out es obligatoria.',
            'checkOut.date' => 'La fecha de check-out debe ser una fecha valida.',
            'checkOut.after' => 'La fecha de check-out debe ser posterior a la fecha de check-in.',
            'selectedRoomIds.required' => 'Por favor selecciona al menos una habitacion.',
            'selectedRoomIds.min' => 'Por favor selecciona al menos una habitacion.',
            'selectedRoomIds.*.exists' => 'Una o mas habitaciones seleccionadas no existen.',
            'adults.integer' => 'La cantidad de adultos debe ser un numero valido.',
            'adults.min' => 'La cantidad de adultos no puede ser negativa.',
            'children.integer' => 'La cantidad de ninos debe ser un numero valido.',
            'children.min' => 'La cantidad de ninos no puede ser negativa.',
            'total.required' => 'El total es obligatorio.',
            'total.min' => 'El total debe ser mayor a cero.',
            'deposit.required' => 'El abono inicial es obligatorio.',
            'paymentMethod.required' => 'Debes seleccionar un metodo de pago para el abono.',
            'paymentMethod.in' => 'El metodo de pago seleccionado no es valido.',
            'notes.max' => 'Las observaciones no pueden exceder 1000 caracteres.',
        ];
    }

    public function resetReservation(): void
    {
        $this->customerId = 0;
        $this->checkIn = '';
        $this->checkOut = '';
        $this->selectedRoomIds = [];
        $this->roomGuests = [];
        $this->adults = null;
        $this->children = null;
        $this->total = 0;
        $this->deposit = 0;
        $this->paymentMethod = 'efectivo';
        $this->notes = '';
    }

    public function getBalance(): int
    {
        return (int) max(0, $this->total - $this->deposit);
    }

    public function getNights(): int
    {
        if (empty($this->checkIn) || empty($this->checkOut)) {
            return 0;
        }

        try {
            $checkIn = new \DateTime($this->checkIn);
            $checkOut = new \DateTime($this->checkOut);
            $diff = $checkOut->diff($checkIn);
            return (int) $diff->days;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
