<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Room;
use App\Enums\RoomStatus;
use App\Enums\VentilationType;
use Illuminate\Validation\ValidationException;

class CreateRoom extends Component
{
    public string $room_number = '';
    public int $beds_count = 1;
    public int $max_capacity = 2;
    public bool $auto_calculate = true;
    public string $ventilation_type = '';
    public array $occupancy_prices = [];

    protected function rules(): array
    {
        return [
            'room_number' => 'required|string|unique:rooms,room_number',
            'beds_count' => 'required|integer|min:1',
            'max_capacity' => 'required|integer|min:1',
            'ventilation_type' => 'required|string|in:' . implode(',', array_column(VentilationType::cases(), 'value')),
            'occupancy_prices' => 'required|array',
            'occupancy_prices.*' => 'required|integer|min:0',
        ];
    }

    protected function messages(): array
    {
        return [
            'room_number.required' => 'El número de habitación es obligatorio.',
            'room_number.unique' => 'Este número de habitación ya existe.',
            'beds_count.required' => 'El número de camas es obligatorio.',
            'beds_count.min' => 'Debe haber al menos 1 cama.',
            'max_capacity.required' => 'La capacidad máxima es obligatoria.',
            'max_capacity.min' => 'La capacidad máxima debe ser al menos 1.',
            'ventilation_type.required' => 'El tipo de ventilación es obligatorio.',
            'occupancy_prices.required' => 'Debe definir precios para al menos una ocupación.',
            'occupancy_prices.*.required' => 'Todos los precios son obligatorios.',
            'occupancy_prices.*.min' => 'Los precios no pueden ser negativos.',
        ];
    }

    public function mount(): void
    {
        $this->updateCapacity();
    }

    public function updatedBedsCount(): void
    {
        if ($this->auto_calculate) {
            $this->updateCapacity();
        }
    }

    public function updatedAutoCalculate(): void
    {
        if ($this->auto_calculate) {
            $this->updateCapacity();
        }
    }

    public function updatedMaxCapacity(): void
    {
        $this->initializePrices();
    }

    private function updateCapacity(): void
    {
        if ($this->auto_calculate) {
            $this->max_capacity = $this->beds_count * 2;
        }
        $this->initializePrices();
    }

    private function initializePrices(): void
    {
        $newPrices = [];
        for ($i = 1; $i <= $this->max_capacity; $i++) {
            $newPrices[$i] = $this->occupancy_prices[$i] ?? ($this->occupancy_prices[$i - 1] ?? 0);
        }
        $this->occupancy_prices = $newPrices;
    }

    public function store(): void
    {
        $this->validate();

        $validated = [
            'room_number' => $this->room_number,
            'beds_count' => $this->beds_count,
            'max_capacity' => $this->max_capacity,
            'ventilation_type' => $this->ventilation_type,
            'occupancy_prices' => array_map('intval', $this->occupancy_prices),
            'status' => RoomStatus::LIBRE->value,
            'last_cleaned_at' => now(),
        ];

        $validated['price_1_person'] = $validated['occupancy_prices'][1] ?? 0;
        $validated['price_2_persons'] = $validated['occupancy_prices'][2] ?? 0;
        $validated['price_per_night'] = $validated['price_2_persons'];

        Room::create($validated);

        session()->flash('success', 'Habitación creada exitosamente.');
        
        $this->redirect(route('rooms.index'));
    }

    public function render()
    {
        return view('livewire.create-room', [
            'ventilationTypes' => VentilationType::cases(),
        ]);
    }
}

