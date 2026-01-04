<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\VentilationType;
use App\Models\RoomRate;

class CreateRoom extends Component
{
    public string $room_number = '';
    public int $beds_count = 1;
    public int $max_capacity = 2;
    public bool $auto_calculate = true;
    public ?int $room_type = null;
    public int $ventilation_type = 1;
    public array $occupancy_prices = [];
    public float $base_price_per_night = 0.0;
    public bool $is_active = true;
    public int $errorFlash = 0;
    
    // Flag para prevenir doble envío
    private bool $isProcessing = false;

    protected function rules(): array
    {
        return [
            'room_number' => 'required|string|unique:rooms,room_number',
            'room_type'=> 'nullable|integer|exists:room_types,id',
            'ventilation_type' => 'required|integer|exists:ventilation_types,id',
            'beds_count' => 'required|integer|min:1|max:15',
            'max_capacity' => 'required|integer|min:1',
            'base_price_per_night' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'room_number.required' => 'El número de habitación es obligatorio.',
            'room_number.unique' => 'Este número de habitación ya existe.',
            'room_type_id.exists' => 'El tipo de habitación seleccionado no es válido.',
            'beds_count.required' => 'El número de camas es obligatorio.',
            'beds_count.min' => 'Debe haber al menos 1 cama.',
            'beds_count.max' => 'El número de camas no puede ser mayor a 15.',
            'max_capacity.required' => 'La capacidad máxima es obligatoria.',
            'max_capacity.min' => 'La capacidad máxima debe ser al menos 1.',
            'ventilation_type.required' => 'El tipo de ventilación es obligatorio.',
            'ventilation_type.exists' => 'El tipo de ventilación seleccionado no es válido.',
        ];
    }

    public function mount(): void
    {
        $this->updateCapacity();
    }

    public function updatedBedsCount(): void
    {
        // Limitar el número de camas a máximo 15
        if ($this->beds_count > 15) {
            $this->beds_count = 15;
        }

        // Asegurar mínimo de 1 solo si el valor es válido (no vacío ni null)
        if (isset($this->beds_count) && $this->beds_count !== '' && $this->beds_count < 1) {
            $this->beds_count = 1;
        }

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

    public function updatedOccupancyPrices($value, $key): void
    {
        // Convert 0, '0', empty string, or null to null to treat it as empty/placeholder
        if ($value === 0 || $value === '0' || $value === '' || $value === null) {
            $this->occupancy_prices[$key] = null;
        } else {
            $intValue = (int)$value;
            if ($intValue > 0) {
                $this->occupancy_prices[$key] = $intValue;
            } else {
                $this->occupancy_prices[$key] = null;
            }
        }
    }

    private function updateCapacity(): void
    {
        // Solo validar y corregir si el valor está establecido y es válido
        if (isset($this->beds_count) && $this->beds_count !== '' && $this->beds_count < 1) {
            $this->beds_count = 1;
        }

        if (!isset($this->max_capacity) || $this->max_capacity < 1) {
            $this->max_capacity = 2;
        }

        if ($this->auto_calculate && isset($this->beds_count) && $this->beds_count > 0) {
            $this->max_capacity = $this->beds_count * 2;
        }

        $this->initializePrices();
    }

    private function initializePrices(): void
    {
        if (!isset($this->max_capacity) || $this->max_capacity < 1) {
            $this->max_capacity = 2;
        }

        $newPrices = [];
        for ($i = 1; $i <= $this->max_capacity; $i++) {
            // Preserve existing non-zero values, otherwise set to null (will show as placeholder)
            $existingValue = $this->occupancy_prices[$i] ?? null;
            $previousValue = $this->occupancy_prices[$i - 1] ?? null;

            if ($existingValue !== null && $existingValue > 0) {
                $newPrices[$i] = $existingValue;
            } elseif ($previousValue !== null && $previousValue > 0) {
                $newPrices[$i] = $previousValue;
            } else {
                $newPrices[$i] = null;
            }
        }
        $this->occupancy_prices = $newPrices;
    }

    public function store(): void
    {
        // Prevenir doble envío
        if ($this->isProcessing) {
            $this->dispatch('notify', type: 'warning', message: 'La solicitud está siendo procesada. Por favor espere.');
            return;
        }
        
        $this->isProcessing = true;

        try {

            $this->room_type = $this->room_type ?: null;
            $this->ventilation_type = $this->ventilation_type ?: null;

            // Asegurar que beds_count tenga un valor válido antes de validar
            if (!isset($this->beds_count) || $this->beds_count === '' || $this->beds_count < 1) {
                $this->beds_count = 1;
            }

            // Limitar a máximo 15
            if ($this->beds_count > 15) {
                $this->beds_count = 15;
            }

            // Validate that at least one price is set
            $hasAtLeastOnePrice = false;
            foreach ($this->occupancy_prices as $value) {
                if ($value !== null && $value > 0) {
                    $hasAtLeastOnePrice = true;
                    break;
                }
            }

            if (!$hasAtLeastOnePrice) {
                $this->addError('occupancy_prices', 'Debe definir al menos un precio de ocupación.');
                $this->dispatch('notify', type: 'error', message: 'Por favor revisa los errores en el formulario.');
                $this->errorFlash++;
                $this->isProcessing = false;
                return;
            }

            // Convert null values for validation
            $pricesForValidation = [];
            foreach ($this->occupancy_prices as $key => $value) {
                $pricesForValidation[$key] = $value !== null ? (int)$value : null;
            }

            // Temporarily set occupancy_prices for validation
            $originalPrices = $this->occupancy_prices;
            $this->occupancy_prices = $pricesForValidation;

            // Validate all fields
            try {
                $this->validate();
            } catch (\Illuminate\Validation\ValidationException $e) {
                // Restore original prices before returning
                $this->occupancy_prices = $originalPrices;
                
                // Dispatch error notification
                $this->dispatch('notify', type: 'error', message: 'Por favor completa todos los campos requeridos. 
                ');
                $this->errorFlash++;
                $this->isProcessing = false;
                return;
            }

            // Restore original prices
            $this->occupancy_prices = $originalPrices;

            // Filter out null values and convert to integers for storage
            
            $room = Room::create([
                'room_number' => $this->room_number,
                'room_type_id' => $this->room_type,
                'ventilation_type_id' => $this->ventilation_type,
                'beds_count' => $this->beds_count,
                'max_capacity' => $this->max_capacity,
                'base_price_per_night' => $this->base_price_per_night,
                'is_active' => $this->is_active,
            ]);
            
            $validatedPrices = [];
            foreach ($this->occupancy_prices as $key => $value) {
                if ($value !== null && $value > 0) {
                    $validatedPrices[$key] = (int)$value;
                } else {
                    $validatedPrices[$key] = 0;
                }
            }

            foreach ($validatedPrices as $minGuests => $price) {
                RoomRate::create([
                    'room_id' => $room->id,
                    'min_guests' => $minGuests,
                    'max_guests' => $minGuests,
                    'price_per_night' => $price,
                ]);
            }

            // Reset form with specific values to avoid property not found errors
            $this->room_number = '';
            $this->beds_count = 1;
            $this->max_capacity = 2;
            $this->auto_calculate = true;
            $this->room_type = 0;
            $this->ventilation_type = 0;
            $this->occupancy_prices = [];

            // Re-initialize after reset
            $this->updateCapacity();

            // Dispatch events
            $this->dispatch('room-created', roomId: $room->id);
            $this->dispatch('notify', type: 'success', message: 'Habitación creada exitosamente.');
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render()
    {
        return view('livewire.create-room', [
            'ventilationTypes' => VentilationType::all(),
            'roomTypes' => RoomType::all()
        ]);
    }
}

