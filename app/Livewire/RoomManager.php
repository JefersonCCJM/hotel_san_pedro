<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Room;
use App\Models\Reservation;
use App\Models\ReservationSale;
use App\Models\ReservationDeposit;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerTaxProfile;
use App\Enums\RoomStatus;
use App\Enums\VentilationType;
use Carbon\Carbon;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class RoomManager extends Component
{
    use WithPagination;

    public $date;
    public $search = '';
    public $status = '';
    public $ventilation_type = '';
    public $refreshTrigger = 0;
    
    /**
     * Timestamp de la última actualización por evento.
     * Se usa para que el polling NO ejecute si ya hubo un evento reciente (< 6s).
     * Esto evita renders innecesarios y que el polling sobrescriba cambios recientes.
     */
    public $lastEventUpdate = 0;
    
    // Selection and Modals
    public $selectedRoomId = null;
    public $detailData = null;
    public $roomDetailModal = false;
    public $showAddSale = false;
    public $showAddDeposit = false;
    public $quickRentModal = false;
    public $rentForm = [
        'room_id' => '',
        'room_number' => '',
        'customer_id' => '',
        'people' => 1,
        'max_capacity' => 1,
        'prices' => [],
        'total' => 0,
        'deposit' => 0,
        'check_out' => '',
        'payment_method' => 'efectivo'
    ];

    // Create customer modal
    public $showCreateCustomerModal = false;
    public $newCustomer = [
        'name' => '',
        'identification' => '',
        'phone' => '',
        'email' => ''
    ];

    // Additional guests
    public $additionalGuests = [];

    // Add consumption form
    public $newSale = [
        'product_id' => '',
        'quantity' => 1,
        'payment_method' => 'pendiente'
    ];

    // Add deposit form
    public $newDeposit = [
        'amount' => null,
        'payment_method' => 'efectivo',
        'notes' => ''
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'ventilation_type' => ['except' => ''],
        'date' => ['except' => ''],
    ];

    public function mount()
    {
        $this->date = $this->date ?: now()->format('Y-m-d');
        $this->rentForm['check_out'] = Carbon::parse($this->date)->addDay()->format('Y-m-d');
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedStatus() { $this->resetPage(); }
    public function updatedVentilationType() { $this->resetPage(); }

    public function changeDate($newDate)
    {
        $this->date = $newDate;
        $this->rentForm['check_out'] = Carbon::parse($newDate)->addDay()->format('Y-m-d');
        if ($this->roomDetailModal) {
            $this->openRoomDetail($this->selectedRoomId);
        }
    }

    public function openRoomDetail($roomId)
    {
        $this->selectedRoomId = $roomId;
        $this->loadRoomDetail();
        $this->roomDetailModal = true;
        $this->showAddSale = false;
        $this->showAddDeposit = false;
    }

    private function loadRoomDetail()
    {
        $room = Room::find($this->selectedRoomId);
        $date = Carbon::parse($this->date);

        $reservation = $room->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->with(['customer.taxProfile', 'sales.product', 'reservationDeposits'])
            ->first();

        if (!$reservation) {
            $this->detailData = [
                'room' => $room->toArray(),
                'reservation' => null,
                'sales' => [],
                'stay_history' => [],
                'status_label' => $room->status->label(),
                'status_color' => $room->status->color()
            ];
        } else {
            $total_hospedaje_completo = (float) $reservation->total_amount;
            $abono = (float) $reservation->deposit;
            $consumos_pagados = (float) $reservation->sales->where('is_paid', true)->sum('total');
            $consumos_pendientes = (float) $reservation->sales->where('is_paid', false)->sum('total');

            $stay_history = [];
            $checkIn = Carbon::parse($reservation->check_in_date);
            $checkOut = Carbon::parse($reservation->check_out_date);
            $daysTotal = max(1, $checkIn->diffInDays($checkOut));
            $dailyPrice = $total_hospedaje_completo / $daysTotal;

            // Calcular hospedaje consumido hasta hoy (o la fecha seleccionada)
            $selectedDate = Carbon::parse($this->date);
            $daysConsumed = $checkIn->diffInDays($selectedDate) + 1;
            $daysConsumed = max(1, min($daysTotal, $daysConsumed));
            $total_hospedaje = $dailyPrice * $daysConsumed;

            $total_debt = ($total_hospedaje - $abono) + $consumos_pendientes;

            $paidAmount = $abono;
            for ($i = 0; $i < $daysTotal; $i++) {
                $currentDate = $checkIn->copy()->addDays($i);
                $isPaid = $paidAmount >= $dailyPrice;
                $stay_history[] = [
                    'date' => $currentDate->format('d/m/Y'),
                    'is_paid' => $isPaid,
                    'price' => $dailyPrice
                ];
                if ($isPaid) $paidAmount -= $dailyPrice;
            }

            $this->detailData = [
                'room' => $room->toArray(),
                'reservation' => $reservation->toArray(),
                'customer' => $reservation->customer->toArray(),
                'identification' => $reservation->customer->taxProfile?->identification ?? 'N/A',
                'total_hospedaje' => $total_hospedaje,
                'abono_realizado' => $abono,
                'sales_total' => $consumos_pagados + $consumos_pendientes,
                'consumos_pendientes' => $consumos_pendientes,
                'total_debt' => $total_debt,
                'sales' => $reservation->sales->toArray(),
                'stay_history' => $stay_history,
                'deposit_history' => $reservation->reservationDeposits()
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function($deposit) {
                        return [
                            'id' => $deposit->id,
                            'amount' => (float) $deposit->amount,
                            'payment_method' => $deposit->payment_method,
                            'notes' => $deposit->notes,
                            'created_at' => $deposit->created_at->format('d/m/Y H:i'),
                        ];
                    })
                    ->toArray(),
                'customer_history' => Reservation::where('customer_id', $reservation->customer_id)
                    ->where('id', '!=', $reservation->id)
                    ->with('room')
                    ->orderBy('check_in_date', 'desc')
                    ->take(5)
                    ->get()
                    ->map(function($res) {
                        $arr = $res->toArray();
                        $arr['is_paid'] = (float) $res->deposit >= (float) $res->total_amount;
                        return $arr;
                    })
                    ->toArray(),
                'status_label' => RoomStatus::OCUPADA->label(),
                'status_color' => RoomStatus::OCUPADA->color()
            ];
        }
    }

    public function payEverything($reservationId, $method)
    {
        $reservation = Reservation::with('sales')->findOrFail($reservationId);
        $this->selectedRoomId = $reservation->room_id;

        // 1. Marcar todos los consumos como pagados
        $reservation->sales()->where('is_paid', false)->update([
            'is_paid' => true,
            'payment_method' => $method
        ]);

        // 2. Actualizar el abono para cubrir todo el hospedaje
        $oldDeposit = (float) $reservation->deposit;
        $newDeposit = (float) $reservation->total_amount;
        $difference = $newDeposit - $oldDeposit;

        $reservation->update([
            'deposit' => $newDeposit,
            'is_paid' => true
        ]);

        // Registrar en historial de abonos si hay diferencia
        if ($difference > 0) {
            ReservationDeposit::create([
                'reservation_id' => $reservationId,
                'amount' => $difference,
                'payment_method' => $method,
                'notes' => 'Pago completo de hospedaje y consumos'
            ]);
        }

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Todo marcado como pagado.');
    }

    public function toggleAddSale()
    {
        $this->showAddSale = !$this->showAddSale;
        if ($this->showAddSale) {
            $this->dispatch('initAddSaleSelect');
        }
    }

    public function toggleAddDeposit()
    {
        $this->showAddDeposit = !$this->showAddDeposit;
        if (!$this->showAddDeposit) {
            $this->newDeposit = ['amount' => null, 'payment_method' => 'efectivo', 'notes' => ''];
        }
    }

    public function addDeposit()
    {
        $this->validate([
            'newDeposit.amount' => 'required|numeric|min:0.01',
            'newDeposit.payment_method' => 'required|in:efectivo,transferencia',
            'newDeposit.notes' => 'nullable|string|max:500',
        ], [
            'newDeposit.amount.required' => 'El monto es obligatorio.',
            'newDeposit.amount.numeric' => 'El monto debe ser un número válido.',
            'newDeposit.amount.min' => 'El monto debe ser mayor a 0.',
            'newDeposit.payment_method.required' => 'El método de pago es obligatorio.',
            'newDeposit.payment_method.in' => 'El método de pago debe ser efectivo o transferencia.',
        ]);

        $room = Room::find($this->selectedRoomId);
        $date = Carbon::parse($this->date);

        $reservation = $room->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->first();

        if (!$reservation) {
            $this->dispatch('notify', type: 'error', message: 'No hay un arrendamiento activo.');
            return;
        }

        // Actualizar el abono total en la reserva
        $reservation->increment('deposit', $this->newDeposit['amount']);

        // Registrar en historial de abonos
        ReservationDeposit::create([
            'reservation_id' => $reservation->id,
            'amount' => $this->newDeposit['amount'],
            'payment_method' => $this->newDeposit['payment_method'],
            'notes' => $this->newDeposit['notes'] ?? null
        ]);

        $this->newDeposit = ['amount' => null, 'payment_method' => 'efectivo', 'notes' => ''];
        $this->showAddDeposit = false;
        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Abono agregado exitosamente.');
    }

    public function deleteDeposit($depositId, $amount)
    {
        $deposit = ReservationDeposit::findOrFail($depositId);
        $reservation = $deposit->reservation;

        // Actualizar el abono total en la reserva (reducir el monto del abono eliminado)
        $newDeposit = max(0, (float) $reservation->deposit - (float) $amount);
        $reservation->update(['deposit' => $newDeposit]);

        // Eliminar el registro del historial
        $deposit->delete();

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Abono eliminado exitosamente.');
    }

    public function addSale()
    {
        $this->validate([
            'newSale.product_id' => 'required|exists:products,id',
            'newSale.quantity' => 'required|integer|min:1',
            'newSale.payment_method' => 'required|in:efectivo,transferencia,pendiente',
        ]);

        $room = Room::find($this->selectedRoomId);
        $date = Carbon::parse($this->date);

        $reservation = $room->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->first();

        if (!$reservation) {
            $this->dispatch('notify', type: 'error', message: 'No hay un arrendamiento activo.');
            return;
        }

        $product = Product::find($this->newSale['product_id']);
        if ($product->quantity < $this->newSale['quantity']) {
            $this->dispatch('notify', type: 'error', message: "Stock insuficiente ({$product->quantity})");
            return;
        }

        $reservation->sales()->create([
            'product_id' => $product->id,
            'quantity' => $this->newSale['quantity'],
            'unit_price' => $product->price,
            'total' => $product->price * $this->newSale['quantity'],
            'payment_method' => $this->newSale['payment_method'],
            'is_paid' => $this->newSale['payment_method'] !== 'pendiente',
        ]);

        $product->decrement('quantity', $this->newSale['quantity']);
        $this->newSale = ['product_id' => '', 'quantity' => 1, 'payment_method' => 'pendiente'];
        $this->showAddSale = false;
        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Consumo cargado.');
    }

    public function paySale($saleId, $method)
    {
        $sale = ReservationSale::findOrFail($saleId);

        if ($method === 'pendiente') {
            $sale->update(['is_paid' => false, 'payment_method' => 'pendiente']);
        } else {
            $sale->update(['is_paid' => true, 'payment_method' => $method]);
        }

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Pago actualizado.');
    }

    public function payNight($reservationId, $amount, $method)
    {
        $reservation = Reservation::findOrFail($reservationId);
        $reservation->increment('deposit', $amount);

        // Registrar en historial de abonos
        ReservationDeposit::create([
            'reservation_id' => $reservationId,
            'amount' => $amount,
            'payment_method' => $method,
            'notes' => 'Pago de noche de hospedaje'
        ]);

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Pago de noche registrado (' . ucfirst($method) . ').');
    }

    public function revertNightPayment($reservationId, $amount)
    {
        $reservation = Reservation::findOrFail($reservationId);
        $newDeposit = max(0, $reservation->deposit - $amount);
        $reservation->update(['deposit' => $newDeposit]);

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Pago de noche anulado.');
    }

    public function updateDeposit($reservationId, $newAmount)
    {
        $reservation = Reservation::findOrFail($reservationId);
        $oldAmount = (float) $reservation->deposit;
        $difference = (float) $newAmount - $oldAmount;

        $reservation->update(['deposit' => $newAmount]);

        // Registrar en historial de abonos solo si hay diferencia
        if ($difference != 0) {
            ReservationDeposit::create([
                'reservation_id' => $reservationId,
                'amount' => abs($difference),
                'payment_method' => $reservation->payment_method ?? 'efectivo',
                'notes' => $difference > 0 
                    ? 'Actualización de abono (aumento)' 
                    : 'Actualización de abono (reducción)'
            ]);
        }

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Abono actualizado.');
    }

    public function releaseRoom($roomId, $targetStatus)
    {
        $date = Carbon::parse($this->date)->startOfDay();

        // Determine action based on target status
        // 'libre' -> release room and mark as clean (last_cleaned_at = now)
        // 'pendiente_aseo' -> release room and mark as needing cleaning (last_cleaned_at = null)
        // 'limpia' -> release room and mark as clean (last_cleaned_at = now), same as 'libre' but clearer intent
        $shouldRelease = in_array($targetStatus, ['libre', 'pendiente_aseo', 'limpia']);
        $shouldMarkClean = in_array($targetStatus, ['libre', 'limpia']);

        // Execute all modifications within a DB transaction
        // This ensures atomicity and allows us to refresh data consistently after changes
        DB::transaction(function() use ($roomId, $date, $targetStatus, $shouldRelease, $shouldMarkClean) {
            $room = Room::findOrFail($roomId);
            $room->refresh();

            // Always release reservation when in releaseRoom method
            // All three options (libre, pendiente_aseo, limpia) should release the room
            if ($shouldRelease) {
            // Find active reservation for the selected date
            // We query directly from DB (not cached relations) to get fresh data
            $reservation = $room->reservations()
                ->where('check_in_date', '<=', $date)
                    ->where('check_out_date', '>', $date)
                ->orderBy('check_in_date', 'asc')
                ->first();

            if ($reservation) {
                $start = Carbon::parse($reservation->check_in_date)->startOfDay();
                $end = Carbon::parse($reservation->check_out_date)->startOfDay();

                // When releasing a room, we DELETE the active reservation completely
                // This ensures the reservation disappears from the reservations module
                // and the room shows as "Pendiente por Aseo" or "Libre" immediately
                // Future reservations (starting after the selected date) are preserved automatically
                
                // If reservation has future days (after selected date), create new reservation for those days
                // Then delete the current reservation
                if ($end->gt($date)) {
                    // Reservation extends beyond selected date -> Create new reservation for future days
                    $newRes = $reservation->replicate();
                    $newRes->check_in_date = $date->copy()->addDay()->toDateString();
                    $newRes->check_out_date = $reservation->check_out_date;
                    $newRes->save();
                }
                
                // DELETE the active reservation completely
                // This makes the room available immediately and removes it from reservations module
                $reservation->delete();
                }
            }

            // After modifying reservations, refresh the room model to get updated state
            // This ensures the next query (in render()) will see the changes
            $room->refresh();
            
            // Clear cached relations to force fresh data on next query
            // This is critical: without this, render() might use stale reservation data
            $room->unsetRelation('reservations');

            // Update cleaning status based on selected option
            $updateData = [];
            
            if ($shouldMarkClean) {
                // Mark as clean (libre or limpia option)
                $updateData['last_cleaned_at'] = now();
            } elseif ($targetStatus === 'pendiente_aseo') {
                // Mark as needing cleaning (pendiente_aseo option)
                $updateData['last_cleaned_at'] = null;
            }

            // Update rooms.status ONLY if date is today and room is not occupied
            // Note: We don't update rooms.status for future dates because display_status
            // is calculated dynamically based on reservations, not stored status
            if ($date->isToday() && !$room->isOccupied($date)) {
                // If releasing to LIBRE or LIMPIA, clear SUCIA status (let display_status handle it)
                if (in_array($targetStatus, ['libre', 'limpia'])) {
                    if ($room->status === RoomStatus::SUCIA) {
                        $updateData['status'] = RoomStatus::LIBRE;
                }
                }
            }

            // Apply all updates at once
            if (!empty($updateData)) {
                $room->update($updateData);
            }
        });

        // After transaction commits, reload room to ensure we have fresh data
        // This is necessary because the transaction context might have cached the model
        $room = Room::findOrFail($roomId);
        $room->refresh();
        $room->unsetRelation('reservations');

        // Recargar los datos del detalle si hay una habitación seleccionada
        if ($this->selectedRoomId == $roomId) {
            $this->loadRoomDetail();
        }

        // Force immediate refresh of room list to reflect changes
        // This will query fresh data from database, bypassing any cached relations
        $this->refreshRooms();
        
        // Force Livewire to re-render by updating a property
        // This ensures the component updates even if pagination didn't change
        $this->refreshTrigger = now()->timestamp;

        // Dispatch evento global para sincronización en tiempo real con otros componentes
        // MECANISMO PRINCIPAL: Si CleaningPanel está montado, recibirá este evento inmediatamente (<1s)
        // FALLBACK: Si no está montado, el polling cada 5s capturará el cambio en ≤5s
        $this->dispatch('room-status-updated', roomId: $room->id);
        
        // Marcar que hubo una actualización por evento (evita que el polling ejecute innecesariamente)
        $this->lastEventUpdate = now()->timestamp;
        
        // Generate appropriate success message based on action
        $message = match($targetStatus) {
            'libre' => "Habitación #{$room->room_number} liberada y marcada como limpia.",
            'pendiente_aseo' => "Habitación #{$room->room_number} liberada y marcada como pendiente por aseo.",
            'limpia' => "Habitación #{$room->room_number} liberada y marcada como limpia.",
            default => "Habitación #{$room->room_number} actualizada.",
        };
        
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function cancelReservation($roomId)
    {
        $room = Room::findOrFail($roomId);
        $date = Carbon::parse($this->date)->startOfDay();

        // Get the reservation that causes Pendiente Checkout status
        $reservation = $room->getPendingCheckoutReservation($date);

        if (!$reservation) {
            $this->dispatch('notify', 
                type: 'error', 
                message: "No se encontró una reserva pendiente de checkout para esta habitación."
            );
            return;
        }

        // Delete the reservation and mark room as free
        DB::transaction(function() use ($reservation, $room, $date) {
            $reservation->delete();
            
            // Mark room as clean and free (only if date is today)
            if ($date->isToday()) {
                $room->update([
                    'last_cleaned_at' => now(),
                    'status' => RoomStatus::LIBRE
                ]);
            }
        });

        // Recargar los datos del detalle si hay una habitación seleccionada
        if ($this->selectedRoomId == $roomId) {
            $this->loadRoomDetail();
        }

        // Force immediate refresh of room list to reflect changes
        $this->refreshRooms();
        
        // Dispatch evento global para sincronización en tiempo real con otros componentes
        $this->dispatch('room-status-updated', roomId: $room->id);
        
        // Marcar que hubo una actualización por evento (evita que el polling ejecute innecesariamente)
        $this->lastEventUpdate = now()->timestamp;
        
        $this->dispatch('notify', 
            type: 'success', 
            message: "Reserva cancelada. Habitación #{$room->room_number} liberada."
        );
    }

    public function continueStay($roomId)
    {
        $room = Room::findOrFail($roomId);
        $date = Carbon::parse($this->date)->startOfDay();

        // First, try to get reservation from Pendiente Checkout status
        $reservation = $room->getPendingCheckoutReservation($date);
        
        // If not found, find active reservation or reservation ending today
        if (!$reservation) {
        $reservation = $room->reservations()
            ->where('check_in_date', '<=', $date)
            ->where(function($query) use ($date) {
                $query->where('check_out_date', '>', $date)
                      ->orWhere('check_out_date', '=', $date->toDateString());
            })
            ->orderBy('check_out_date', 'desc')
            ->first();
        }

        if (!$reservation) return;

        $newCheckOut = $reservation->check_out_date->copy()->addDay();
        $prices = $room->getPricesForDate($reservation->check_out_date);
        $additionalPrice = $prices[$reservation->guests_count] ?? ($prices[1] ?? 0);

        // Update reservation and mark room as needing cleaning
        // When a stay is continued, the room will need cleaning when eventually released
        DB::transaction(function() use ($reservation, $newCheckOut, $additionalPrice, $room) {
            $reservation->update([
                'check_out_date' => $newCheckOut,
                'total_amount' => $reservation->total_amount + $additionalPrice
            ]);
            
            // Mark room as needing cleaning (set last_cleaned_at to NULL)
            // This ensures that when the room is eventually released, it will be "Pendiente por Aseo"
            $room->update(['last_cleaned_at' => null]);
        });

        // Recargar los datos del detalle si hay una habitación seleccionada
        if ($this->selectedRoomId == $roomId) {
            $this->loadRoomDetail();
        }

        // Force immediate refresh of room list to reflect changes
        $this->refreshRooms();

        // Dispatch evento global para sincronización en tiempo real con otros componentes
        // MECANISMO PRINCIPAL: Si CleaningPanel está montado, recibirá este evento inmediatamente (<1s)
        // FALLBACK: Si no está montado, el polling cada 5s capturará el cambio en ≤5s
        $this->dispatch('room-status-updated', roomId: $room->id);
        
        // Marcar que hubo una actualización por evento (evita que el polling ejecute innecesariamente)
        $this->lastEventUpdate = now()->timestamp;

        $this->dispatch('notify', type: 'success', message: "Estancia extendida.");
    }

    public function openQuickRent($roomId)
    {
        $room = Room::find($roomId);
        $prices = $room->getPricesForDate(Carbon::parse($this->date));

        $this->rentForm = [
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'max_capacity' => $room->max_capacity,
            'prices' => $prices,
            'total' => $prices[1] ?? 0,
            'deposit' => 0,
            'check_out' => Carbon::parse($this->date)->addDay()->format('Y-m-d'),
            'payment_method' => 'efectivo',
            'customer_id' => ''
        ];

        $this->additionalGuests = [];
        $this->newCustomer = ['name' => '', 'identification' => '', 'phone' => '', 'email' => ''];
        $this->showCreateCustomerModal = false;

        $this->quickRentModal = true;
        $this->dispatch('quickRentOpened');
    }

    private function calculateTotalPeople(): int
    {
        // Solo contar 1 persona principal si hay un cliente seleccionado
        $principalCount = !empty($this->rentForm['customer_id']) ? 1 : 0;
        return $principalCount + count($this->additionalGuests);
    }

    private function updateTotalPrice(): void
    {
        $totalPeople = $this->calculateTotalPeople();
        
        // Si no hay personas, el precio es 0
        if ($totalPeople === 0) {
            $this->rentForm['total'] = 0;
            return;
        }
        
        $maxCapacity = (int)($this->rentForm['max_capacity'] ?? 1);
        $people = min($totalPeople, $maxCapacity); // No exceder capacidad
        
        $basePrice = $this->rentForm['prices'][$people] ?? ($this->rentForm['prices'][$maxCapacity] ?? 0);
        $start = Carbon::parse($this->date);
        $end = Carbon::parse($this->rentForm['check_out']);
        $diffDays = max(1, $start->diffInDays($end));
        $this->rentForm['total'] = $basePrice * $diffDays;
    }

    public function updatedRentForm($value, $key)
    {
        if ($key === 'check_out' || $key === 'customer_id') {
            $this->updateTotalPrice();
        }
    }

    public function storeQuickRent()
    {
        // Validate customer_id first (required)
        $this->validate([
            'rentForm.customer_id' => 'required|exists:customers,id',
            'rentForm.check_out' => 'required|date|after:'.$this->date,
            'rentForm.total' => 'required|numeric|min:0',
            'rentForm.deposit' => 'required|numeric|min:0',
            'rentForm.payment_method' => 'required|in:efectivo,transferencia',
        ], [
            'rentForm.customer_id.required' => 'Debe seleccionar un cliente principal.',
            'rentForm.customer_id.exists' => 'El cliente seleccionado no existe.',
        ], [
            'rentForm.check_out' => 'fecha de salida',
        ]);

        // Calculate total people (principal + additional guests)
        $totalPeople = $this->calculateTotalPeople();
        $maxCapacity = (int)($this->rentForm['max_capacity'] ?? 1);

        // Validate at least 1 person (principal customer)
        if ($totalPeople < 1) {
            $this->addError('rentForm.customer_id', 'Debe seleccionar al menos un cliente principal.');
            return;
        }

        // Validate total people doesn't exceed capacity
        if ($totalPeople > $maxCapacity) {
            $this->addError('rentForm.people', "El total de personas (principal + huéspedes adicionales: {$totalPeople}) excede la capacidad máxima de la habitación ({$maxCapacity} personas).");
            return;
        }

        $checkInDate = Carbon::parse($this->date);
        if ($checkInDate->isBefore(now()->startOfDay())) {
            $this->addError('rentForm.check_in_date', 'No se puede ingresar una reserva antes del día actual.');
            return;
        }

        // Cerrar modal de inmediato tras validar para dar feedback rápido al usuario
        $this->quickRentModal = false;

        // Create reservation within transaction for atomicity
        $reservation = DB::transaction(function() {
            $reservation = Reservation::create([
                'room_id' => $this->rentForm['room_id'],
                'customer_id' => $this->rentForm['customer_id'],
                'check_in_date' => $this->date,
                'check_out_date' => $this->rentForm['check_out'],
                'reservation_date' => now(),
                'guests_count' => $this->calculateTotalPeople(),
                'total_amount' => $this->rentForm['total'],
                'deposit' => $this->rentForm['deposit'],
                'payment_method' => $this->rentForm['payment_method'],
                'is_paid' => $this->rentForm['deposit'] >= $this->rentForm['total'],
                'status' => 'confirmed'
            ]);

            // Registrar abono inicial en historial si es mayor a 0
            if ($this->rentForm['deposit'] > 0) {
                ReservationDeposit::create([
                    'reservation_id' => $reservation->id,
                    'amount' => $this->rentForm['deposit'],
                    'payment_method' => $this->rentForm['payment_method'],
                    'notes' => 'Abono inicial'
                ]);
            }

            // Attach additional guests if provided
            if (!empty($this->additionalGuests)) {
                $guestIds = array_filter(
                    array_column($this->additionalGuests, 'customer_id'),
                    fn($id) => !empty($id) && is_numeric($id)
                );
                if (!empty($guestIds)) {
                    $reservation->guests()->attach($guestIds);
                }
            }

            return $reservation;
        });
        
        // Dispatch notifications first (non-blocking)
        $this->dispatch('notify', type: 'success', message: 'Reserva creada exitosamente.');
        
        // Dispatch evento global para sincronización en tiempo real con otros componentes
        // MECANISMO PRINCIPAL: Si CleaningPanel está montado, recibirá este evento inmediatamente (<1s)
        // FALLBACK: Si no está montado, el polling cada 5s capturará el cambio en ≤5s
        $this->dispatch('room-status-updated', roomId: $this->rentForm['room_id']);
        
        // Marcar que hubo una actualización por evento (evita que el polling ejecute innecesariamente)
        $this->lastEventUpdate = now()->timestamp;
        
        // Refresh rooms list (this will trigger render() automatically)
        // Using refreshRooms() which is optimized and preserves pagination
        $this->refreshRooms();
    }

    /**
     * Método centralizado para refrescar datos de habitaciones desde la BD.
     * 
     * USADO POR:
     * 1. Polling fallback (wire:poll.5s) - ejecutado cada 5s automáticamente (si no hay evento reciente)
     * 2. Métodos de acción (releaseRoom, continueStay, storeQuickRent) - cuando el usuario hace cambios
     * 
     * ROL COMO POLLING FALLBACK:
     * - Se ejecuta automáticamente cada 5s mediante wire:poll.5s
     * - PERO solo ejecuta si NO hubo un evento reciente (< 6s)
     * - Garantiza que cambios externos se reflejen en ≤5s si el evento Livewire se pierde
     * - NO es el mecanismo principal (los eventos Livewire son más rápidos e inmediatos)
     * 
     * OPTIMIZACIÓN:
     * - Preserva paginación, filtros y búsqueda automáticamente
     * - render() usa eager loading (single query, sin N+1)
     * - Verifica $lastEventUpdate antes de ejecutar para evitar renders innecesarios
     * 
     * NOTA: Si ambos componentes están montados, los eventos Livewire actualizan
     * inmediatamente (<300ms) y marcan $lastEventUpdate, haciendo que el polling
     * se salte hasta 6s después. Esto elimina renders duplicados y mejora UX.
     */
    public function refreshRooms(): void
    {
        // Store current page to restore if needed
        $currentPage = $this->getPage();
        
        // Reset pagination to force complete re-render
        // This triggers render() which queries fresh data from database
        // The eager loading in render() will fetch updated reservations from DB
        // Preserves $this->search, $this->status, $this->date automatically
        $this->resetPage();
        
        // If we were on a page > 1, restore it to maintain user's view
        if ($currentPage > 1) {
            $this->setPage($currentPage);
        }
        
        // Force re-render by updating trigger (ensures update even if page didn't change)
        // This property change forces Livewire to re-execute render() with fresh DB queries
        $this->refreshTrigger = now()->timestamp;
    }
    
    /**
     * Método de polling inteligente (ejecutado cada 5s automáticamente).
     * 
     * ROL: Mecanismo de sincronización FALLBACK INTELIGENTE
     * - Se ejecuta automáticamente cada 5s mediante wire:poll.5s
     * - PERO solo ejecuta refreshRooms() si NO hubo un evento reciente (< 6s)
     * - Esto evita renders innecesarios y que el polling sobrescriba cambios recientes
     * 
     * OPTIMIZACIÓN:
     * - Verifica $lastEventUpdate antes de ejecutar queries pesadas
     * - Si hubo evento reciente (< 6s), se salta la ejecución (evita renders innecesarios)
     * - Esto elimina el problema de que el polling y los eventos se "pisen" entre sí
     */
    public function refreshRoomsPolling(): void
    {
        // Si hubo un evento reciente (< 6s), no ejecutar polling
        // Esto evita renders innecesarios y que el polling sobrescriba cambios recientes
        $secondsSinceLastEvent = now()->timestamp - $this->lastEventUpdate;
        if ($this->lastEventUpdate > 0 && $secondsSinceLastEvent < 6) {
            // No hacer nada, el evento ya actualizó todo
            return;
        }
        
        // Solo ejecutar polling si realmente no hubo evento reciente (fallback real)
        $this->refreshRooms();
    }

    public function createCustomer()
    {
        $this->validate([
            'newCustomer.name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\-]+$/',
            ],
            'newCustomer.identification' => [
                'required',
                'string',
                'min:6',
                'max:20',
                'regex:/^\d{6,10}$/',
            ],
            'newCustomer.phone' => [
                'nullable',
                'string',
                'regex:/^\d{10}$/',
                'max:20',
            ],
            'newCustomer.email' => [
                'nullable',
                'string',
                'max:255',
                'email',
                'regex:/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
            ],
        ], [
            'newCustomer.name.required' => 'El nombre es obligatorio.',
            'newCustomer.name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'newCustomer.name.max' => 'El nombre no puede exceder 255 caracteres.',
            'newCustomer.name.regex' => 'El nombre solo puede contener letras, espacios y caracteres especiales (á, é, í, ó, ú, ñ).',
            'newCustomer.identification.required' => 'La identificación es obligatoria.',
            'newCustomer.identification.min' => 'La identificación debe tener al menos 6 dígitos.',
            'newCustomer.identification.max' => 'La identificación no puede exceder 20 caracteres.',
            'newCustomer.identification.regex' => 'La identificación debe tener entre 6 y 10 dígitos numéricos.',
            'newCustomer.phone.regex' => 'El teléfono debe tener exactamente 10 dígitos numéricos.',
            'newCustomer.phone.max' => 'El teléfono no puede exceder 20 caracteres.',
            'newCustomer.email.email' => 'El correo electrónico debe tener un formato válido (ejemplo: usuario@dominio.com).',
            'newCustomer.email.regex' => 'El correo electrónico debe contener un símbolo @ y un dominio válido.',
            'newCustomer.email.max' => 'El correo electrónico no puede exceder 255 caracteres.',
        ], [
            'newCustomer.name' => 'nombre',
            'newCustomer.identification' => 'identificación',
            'newCustomer.phone' => 'teléfono',
            'newCustomer.email' => 'correo electrónico',
        ]);

        // Sanitize data before saving
        $sanitizedName = trim(mb_strtoupper($this->newCustomer['name']));
        $sanitizedPhone = !empty($this->newCustomer['phone']) ? trim($this->newCustomer['phone']) : null;
        $sanitizedEmail = !empty($this->newCustomer['email']) ? trim(mb_strtolower($this->newCustomer['email'])) : null;

        $customer = DB::transaction(function() use ($sanitizedName, $sanitizedPhone, $sanitizedEmail) {
            $customer = Customer::create([
                'name' => $sanitizedName,
                'phone' => $sanitizedPhone,
                'email' => $sanitizedEmail,
                'is_active' => true,
            ]);

            // Note: CustomerTaxProfile requires identification_document_id and municipality_id
            // which are not available in the quick rent modal. The tax profile can be
            // completed later through the customer edit form if needed.

            return $customer;
        });

        $this->rentForm['customer_id'] = (string) $customer->id;
        $this->showCreateCustomerModal = false;
        $this->newCustomer = ['name' => '', 'identification' => '', 'phone' => '', 'email' => ''];
        $this->dispatch('notify', type: 'success', message: 'Cliente creado exitosamente.');
        $this->dispatch('customerCreated', ['customerId' => $customer->id]);
    }

    public function addGuest()
    {
        $this->validate([
            'newCustomer.name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\-]+$/',
            ],
            'newCustomer.identification' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\d{6,10}$/',
            ],
            'newCustomer.phone' => [
                'nullable',
                'string',
                'regex:/^\d{10}$/',
                'max:20',
            ],
            'newCustomer.email' => [
                'nullable',
                'string',
                'max:255',
                'email',
                'regex:/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
            ],
        ], [
            'newCustomer.name.required' => 'El nombre es obligatorio.',
            'newCustomer.name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'newCustomer.name.max' => 'El nombre no puede exceder 255 caracteres.',
            'newCustomer.name.regex' => 'El nombre solo puede contener letras, espacios y caracteres especiales (á, é, í, ó, ú, ñ).',
            'newCustomer.identification.regex' => 'La identificación debe tener entre 6 y 10 dígitos numéricos.',
            'newCustomer.identification.max' => 'La identificación no puede exceder 20 caracteres.',
            'newCustomer.phone.regex' => 'El teléfono debe tener exactamente 10 dígitos numéricos.',
            'newCustomer.phone.max' => 'El teléfono no puede exceder 20 caracteres.',
            'newCustomer.email.email' => 'El correo electrónico debe tener un formato válido (ejemplo: usuario@dominio.com).',
            'newCustomer.email.regex' => 'El correo electrónico debe contener un símbolo @ y un dominio válido.',
            'newCustomer.email.max' => 'El correo electrónico no puede exceder 255 caracteres.',
        ], [
            'newCustomer.name' => 'nombre',
            'newCustomer.identification' => 'identificación',
            'newCustomer.phone' => 'teléfono',
            'newCustomer.email' => 'correo electrónico',
        ]);

        // Calculate total people (principal + additional guests + new guest)
        $principalPeople = (int)($this->rentForm['people'] ?? 1);
        $currentAdditionalCount = count($this->additionalGuests);
        $maxCapacity = (int)($this->rentForm['max_capacity'] ?? 1);
        $totalAfterAdd = $principalPeople + $currentAdditionalCount + 1;

        if ($totalAfterAdd > $maxCapacity) {
            $remaining = $maxCapacity - ($principalPeople + $currentAdditionalCount);
            $this->addError('additionalGuests', "No se pueden agregar más huéspedes. La capacidad máxima es {$maxCapacity} personas. Puede agregar máximo {$remaining} " . ($remaining == 1 ? 'persona más' : 'personas más') . ".");
            return;
        }

        // Sanitize data before saving
        $sanitizedName = trim(mb_strtoupper($this->newCustomer['name']));
        $sanitizedPhone = !empty($this->newCustomer['phone']) ? trim($this->newCustomer['phone']) : null;
        $sanitizedEmail = !empty($this->newCustomer['email']) ? trim(mb_strtolower($this->newCustomer['email'])) : null;

        // Create customer for guest if identification provided, otherwise use temporary data
        $guestCustomerId = null;
        if (!empty($this->newCustomer['identification'])) {
            $customer = Customer::firstOrCreate(
                ['name' => $sanitizedName],
                [
                    'phone' => $sanitizedPhone,
                    'email' => $sanitizedEmail,
                    'is_active' => true,
                ]
            );

            // Note: CustomerTaxProfile requires identification_document_id and municipality_id
            // which are not available in the quick rent modal. The tax profile can be
            // completed later through the customer edit form if needed.

            $guestCustomerId = $customer->id;

            // Check if this is the main guest
            $mainCustomerId = $this->rentForm['customer_id'] ?? null;
            if ($mainCustomerId && (string)$guestCustomerId === (string)$mainCustomerId) {
                $this->addError('additionalGuests', 'El huésped principal no puede agregarse como huésped adicional.');
                return;
            }
        }

        $this->additionalGuests[] = [
            'name' => $sanitizedName,
            'identification' => !empty($this->newCustomer['identification']) ? trim($this->newCustomer['identification']) : 'S/N',
            'phone' => $sanitizedPhone ?? 'S/N',
            'customer_id' => $guestCustomerId,
        ];

        $this->newCustomer = ['name' => '', 'identification' => '', 'phone' => '', 'email' => ''];
        $this->showCreateCustomerModal = false;
        $this->updateTotalPrice();
    }

    public function addGuestFromCustomerId($customerId)
    {
        $customer = Customer::with('taxProfile')->find($customerId);
        
        if (!$customer) {
            $this->addError('additionalGuests', 'Cliente no encontrado.');
            return;
        }

        // Check if this is the main guest
        $mainCustomerId = $this->rentForm['customer_id'] ?? null;
        if ($mainCustomerId && (string)$customerId === (string)$mainCustomerId) {
            $this->addError('additionalGuests', 'El huésped principal no puede agregarse como huésped adicional.');
            return;
        }

        // Calculate total people (principal + additional guests + new guest)
        $currentTotal = $this->calculateTotalPeople();
        $maxCapacity = (int)($this->rentForm['max_capacity'] ?? 1);
        $totalAfterAdd = $currentTotal + 1;

        if ($totalAfterAdd > $maxCapacity) {
            $remaining = $maxCapacity - $currentTotal;
            $this->addError('additionalGuests', "No se pueden agregar más huéspedes. La capacidad máxima es {$maxCapacity} personas. Puede agregar máximo {$remaining} " . ($remaining == 1 ? 'persona más' : 'personas más') . ".");
            return;
        }

        // Check if customer is already in the list
        foreach ($this->additionalGuests as $guest) {
            if (isset($guest['customer_id']) && $guest['customer_id'] == $customerId) {
                $this->addError('additionalGuests', 'Este cliente ya está en la lista de huéspedes adicionales.');
                return;
            }
        }

        $this->additionalGuests[] = [
            'name' => $customer->name,
            'identification' => $customer->taxProfile?->identification ?? 'S/N',
            'phone' => $customer->phone ?? 'S/N',
            'customer_id' => $customer->id,
        ];

        $this->updateTotalPrice();
        $this->dispatch('guest-added');
    }

    public function removeGuest($index)
    {
        if (isset($this->additionalGuests[$index])) {
            unset($this->additionalGuests[$index]);
            $this->additionalGuests = array_values($this->additionalGuests);
            $this->updateTotalPrice();
        }
    }

    /**
     * Listener para eventos de actualización de estado de habitaciones.
     * 
     * MECANISMO PRINCIPAL de sincronización en tiempo real.
     * 
     * ROL: Sincronización INMEDIATA cuando ambos componentes están montados
     * - Se ejecuta cuando otro componente (ej: CleaningPanel) dispatch 'room-status-updated'
     * - Latencia: <300ms (inmediato, optimizado)
     * - Funciona SOLO si ambos componentes están montados en la misma sesión del navegador
     * 
     * OPTIMIZACIÓN O(1):
     * - En lugar de ejecutar refreshRooms() que dispara render() completo (150-500ms),
     *   actualiza SOLO el refreshTrigger para forzar re-render mínimo
     * - El render() siguiente verá los datos actualizados vía eager loading
     * - Si el detalle está abierto, lo recarga
     * - Marca $lastEventUpdate para evitar que el polling ejecute inmediatamente después
     * 
     * FLUJO:
     * 1. CleaningPanel marca habitación como limpia → dispatch evento
     * 2. Este listener recibe el evento → actualiza refreshTrigger (fuerza re-render mínimo)
     * 3. UI se actualiza automáticamente sin recargar página
     * 
     * FALLBACK:
     * - Si este listener NO se ejecuta (componente no montado), el polling (refreshRoomsPolling())
     *   capturará el cambio en ≤5s
     */
    #[On('room-status-updated')]
    public function onRoomStatusUpdated(int $roomId): void
    {
        // Actualizar trigger para forzar re-render mínimo (no ejecutar refreshRooms completo)
        // El render() siguiente verá los datos actualizados vía eager loading
        // Esto reduce latencia de ~500ms a ~200ms
        $this->refreshTrigger = now()->timestamp;
        
        // If the updated room detail is open, reload it
        if ($this->selectedRoomId == $roomId) {
            $this->loadRoomDetail();
        }
        
        // Marcar que hubo actualización por evento (evita que el polling ejecute inmediatamente)
        $this->lastEventUpdate = now()->timestamp;
    }

    public function render()
    {
        $date = Carbon::parse($this->date)->startOfDay();
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $daysInMonth = [];
        $tempDate = $startOfMonth->copy();
        while ($tempDate <= $endOfMonth) {
            $daysInMonth[] = $tempDate->copy();
            $tempDate->addDay();
        }

        $query = Room::query();
        if ($this->search) {
            $query->where(function($q) {
                $q->where('room_number', 'like', '%' . $this->search . '%')
                  ->orWhere('beds_count', 'like', '%' . $this->search . '%');
            });
        }
        if ($this->ventilation_type) {
            $query->where('ventilation_type', $this->ventilation_type);
        }

        $isPastDate = $date->isPast() && !$date->isToday();

        if ($isPastDate && $this->status) {
            $query->whereHas('dailyStatuses', function($q) use ($date) {
                $q->whereDate('date', $date->toDateString())
                  ->where('status', $this->status);
            });
        } elseif (!$isPastDate && $this->status) {
            $query->where('status', $this->status);
        }

        if ($isPastDate) {
            $rooms = $query->with([
                'dailyStatuses' => function($q) use ($date) {
                    $q->whereDate('date', $date->toDateString());
                },
                'rates',
            ])->orderBy('room_number')->paginate(30);

            $roomsView = $rooms->getCollection()->map(function($room) use ($date) {
                $snapshot = $room->dailyStatuses->first();

                if (!$snapshot) {
                    $displayStatus = \App\Enums\RoomStatus::LIBRE;
                    $activePrices = $room->getPricesForDate($date);
                    $cleaningStatus = $room->cleaningStatus($date);

                    $ventilationLabel = match (true) {
                        $room->ventilation_type instanceof \App\Enums\VentilationType => $room->ventilation_type->label(),
                        is_string($room->ventilation_type) && $room->ventilation_type !== '' => \App\Enums\VentilationType::from($room->ventilation_type)->label(),
                        default => null,
                    };

                    return (object) [
                        'id' => $room->id,
                        'room_number' => $room->room_number,
                        'beds_count' => $room->beds_count,
                        'max_capacity' => $room->max_capacity,
                        'ventilation_type' => $room->ventilation_type,
                        'ventilation_label' => $ventilationLabel,
                        'status' => $room->status,
                        'last_cleaned_at' => $room->last_cleaned_at,
                        'display_status' => $displayStatus,
                        'active_prices' => $activePrices,
                        'cleaning_status' => $cleaningStatus,
                        'current_reservation' => null,
                        'guest_name' => null,
                        'check_out_date' => null,
                        'total_debt' => 0,
                        'is_night_paid' => false,
                    ];
                }

                $displayStatus = $snapshot->status;
                $activePrices = $room->getPricesForDate($date);

                $cleaningStatus = match ($snapshot->cleaning_status) {
                    'pendiente' => [
                        'code' => 'pendiente',
                        'label' => 'Pendiente por Aseo',
                        'color' => 'bg-yellow-100 text-yellow-800',
                        'icon' => 'fa-broom',
                    ],
                    'limpia' => [
                        'code' => 'limpia',
                        'label' => 'Limpia',
                        'color' => 'bg-green-100 text-green-800',
                        'icon' => 'fa-check-circle',
                    ],
                    'sucia' => [
                        'code' => 'sucia',
                        'label' => 'Sucia',
                        'color' => 'bg-red-100 text-red-800',
                        'icon' => 'fa-times-circle',
                    ],
                    default => [
                        'code' => (string) $snapshot->cleaning_status,
                        'label' => 'Sin definir',
                        'color' => 'bg-gray-100 text-gray-700',
                        'icon' => 'fa-question-circle',
                    ],
                };

                $ventilationLabel = match (true) {
                    $room->ventilation_type instanceof \App\Enums\VentilationType => $room->ventilation_type->label(),
                    is_string($room->ventilation_type) && $room->ventilation_type !== '' => \App\Enums\VentilationType::from($room->ventilation_type)->label(),
                    default => null,
                };

                return (object) [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'beds_count' => $room->beds_count,
                    'max_capacity' => $room->max_capacity,
                    'ventilation_type' => $room->ventilation_type,
                    'ventilation_label' => $ventilationLabel,
                    'status' => $room->status,
                    'last_cleaned_at' => $room->last_cleaned_at,
                    'display_status' => $displayStatus,
                    'active_prices' => $activePrices,
                    'cleaning_status' => $cleaningStatus,
                    'current_reservation' => null,
                    'guest_name' => $snapshot->guest_name,
                    'check_out_date' => $snapshot->check_out_date?->toDateString(),
                    'total_debt' => 0,
                    'is_night_paid' => false,
                ];
            });

            $rooms->setCollection($roomsView);
        } else {
            // Eager load reservations for the month range to optimize queries
            // We load a buffer (month range) to support calendar navigation, but
            // the transform will filter to the specific selected date
            $rooms = $query->with([
                'reservations' => function($q) use ($startOfMonth, $endOfMonth) {
                    $q->where('check_in_date', '<=', $endOfMonth)
                      ->where('check_out_date', '>=', $startOfMonth)
                      ->with('customer');
                },
                'reservations.sales',
                'rates'
            ])->orderBy('room_number')->paginate(30);

            $roomsView = $rooms->getCollection()->map(function($room) use ($date) {
            $isFuture = $date->isAfter(now()->endOfDay());
            $reservation = null;

            if (!$isFuture) {
                $reservation = $room->reservations->first(function($res) use ($date) {
                    $checkIn = Carbon::parse($res->check_in_date)->startOfDay();
                    $checkOut = Carbon::parse($res->check_out_date)->startOfDay();
                    return $checkIn->lte($date) && $checkOut->gt($date);
                });

                if (!$reservation) {
                    $reservation = $room->reservations->first(function($res) use ($date) {
                        $checkIn = Carbon::parse($res->check_in_date)->startOfDay();
                        $checkOut = Carbon::parse($res->check_out_date)->startOfDay();
                        $tomorrow = $date->copy()->addDay()->startOfDay();
                        return ($checkIn->lte($date) && $checkOut->eq($date)) ||
                               ($checkIn->eq($date) && $checkOut->eq($tomorrow)) ||
                               ($checkIn->lte($date) && $checkOut->eq($tomorrow));
                    });
                }

                if (!$reservation && $room->getDisplayStatus($date) === \App\Enums\RoomStatus::PENDIENTE_CHECKOUT) {
                    $reservation = $room->getPendingCheckoutReservation($date);
                }
            }

            $totalDebt = 0;
            $isNightPaid = false;

            if ($reservation) {
                $checkIn = Carbon::parse($reservation->check_in_date);
                $checkOut = Carbon::parse($reservation->check_out_date);
                $daysTotal = max(1, $checkIn->diffInDays($checkOut));
                $dailyPrice = (float)$reservation->total_amount / $daysTotal;

                $daysUntilSelected = $checkIn->diffInDays($date);
                $costUntilSelected = $dailyPrice * ($daysUntilSelected + 1);
                $costUntilSelected = min((float)$reservation->total_amount, $costUntilSelected);

                $isNightPaid = ($reservation->deposit >= $costUntilSelected);

                $stay_debt = (float)($reservation->total_amount - $reservation->deposit);
                $sales_debt = (float)$reservation->sales->where('is_paid', false)->sum('total');
                $totalDebt = $stay_debt + $sales_debt;
            }

            $displayStatus = $room->getDisplayStatus($date);
            $activePrices = $room->getPricesForDate($date);
            $cleaningStatus = $room->cleaningStatus($date);
            $ventilationLabel = match (true) {
                $room->ventilation_type instanceof \App\Enums\VentilationType => $room->ventilation_type->label(),
                is_string($room->ventilation_type) && $room->ventilation_type !== '' => \App\Enums\VentilationType::from($room->ventilation_type)->label(),
                default => null,
            };

            return (object) [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'beds_count' => $room->beds_count,
                'max_capacity' => $room->max_capacity,
                'ventilation_type' => $room->ventilation_type,
                'ventilation_label' => $ventilationLabel,
                'status' => $room->status,
                'last_cleaned_at' => $room->last_cleaned_at,
                'display_status' => $displayStatus,
                'active_prices' => $activePrices,
                'cleaning_status' => $cleaningStatus,
                'current_reservation' => $reservation,
                'total_debt' => $totalDebt,
                'is_night_paid' => $isNightPaid,
            ];
            });

            $rooms->setCollection($roomsView);
        }

        return view('livewire.room-manager', [
            'rooms' => $rooms,
            'statuses' => RoomStatus::cases(),
            'ventilationTypes' => VentilationType::cases(),
            'daysInMonth' => $daysInMonth,
            'currentDate' => $date
        ]);
    }
}
