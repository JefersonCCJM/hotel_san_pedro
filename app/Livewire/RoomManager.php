<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Room;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\ReservationDeposit;
use App\Models\ReservationSale;
use App\Models\Product;
use App\Models\Customer;
use App\Enums\RoomStatus;
use App\Enums\VentilationType;
use Carbon\Carbon;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RoomManager extends Component
{
    use WithPagination;

    public $customerId;
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

    // Customer selector properties
    public $customerSearchTerm = '';
    public $showCustomerDropdown = false;
    public $customers = [];

    // Quick rent guests assignment
    public $quickRentGuests = [];

    // Selection and Modals
    public $selectedRoomId = null;
    public $detailData = null;
    public $roomDetailModal = false;
    public $showAddSale = false;
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

    // Add consumption form
    public $newSale = [
        'product_id' => '',
        'quantity' => 1,
        'payment_method' => 'pendiente'
    ];

    // New customer modal
    public $newCustomerModalOpen = false;
    public $newCustomer = [
        'name' => '',
        'identification' => '',
        'phone' => '',
        'email' => '',
        'address' => '',
        'requiresElectronicInvoice' => false,
        'identificationDocumentId' => '',
        'dv' => '',
        'company' => '',
        'tradeName' => '',
        'municipalityId' => '',
        'legalOrganizationId' => '',
        'tributeId' => ''
    ];
    public $creatingCustomer = false;
    public $customerIdentificationMessage = '';
    public $customerIdentificationExists = false;
    public $customerRequiresDV = false;
    public $customerIsJuridicalPerson = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'ventilation_type' => ['except' => ''],
        'date' => ['except' => ''],
    ];

    private function loadCustomers(): void
    {
        try {
            $customers = Customer::withoutGlobalScopes()
                                 ->with('taxProfile')
                                 ->orderBy('created_at', 'desc')
                                 ->get();
            
            $this->customers = $customers->map(function($customer) {
                return [
                    'id' => (int) $customer->id,
                    'name' => (string) ($customer->name ?? ''),
                    'phone' => (string) ($customer->phone ?? 'S/N'),
                    'email' => $customer->email ? (string) $customer->email : null,
                    'taxProfile' => $customer->taxProfile ? [
                        'identification' => (string) ($customer->taxProfile->identification ?? 'S/N'),
                        'dv' => $customer->taxProfile->dv ? (string) $customer->taxProfile->dv : null,
                    ] : null,
                ];
            })->values()->toArray();
        } catch (\Exception $e) {
            \Log::error('Error loading customers in RoomManager: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->customers = [];
        }
    }

    public function mount()
    {
        $this->date = $this->date ?: now()->format('Y-m-d');
        $this->rentForm['check_out'] = Carbon::parse($this->date)->addDay()->format('Y-m-d');
        $this->loadCustomers();
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
    }

    private function loadRoomDetail()
    {
        $room = Room::find($this->selectedRoomId);
        $date = Carbon::parse($this->date)->startOfDay();

        $reservation = $room->reservations()
            ->whereDate('check_in_date', '<=', $date)
            ->whereDate('check_out_date', '>', $date)
            ->with([
                'customer.taxProfile', 
                'sales.product',
                'reservationRooms.guests.taxProfile',
                'guests.taxProfile', // Fallback for legacy reservations
                'reservationDeposits' => function($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ])
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

            // Get guests assigned to this specific room in the reservation
            // First try to get from ReservationRoom (new structure)
            $reservationRoom = $reservation->reservationRooms()
                ->where('room_id', $room->id)
                ->first();
            
            $guests = [];
            
            if ($reservationRoom) {
                // Load guests with taxProfile for the reservation room
                $reservationRoom->load('guests.taxProfile');
                
                if ($reservationRoom->guests->count() > 0) {
                    // New structure: guests are in ReservationRoom
                    $guests = $reservationRoom->guests->map(function($guest) {
                        return [
                            'id' => $guest->id,
                            'name' => $guest->name,
                            'phone' => $guest->phone ?? 'S/N',
                            'email' => $guest->email,
                            'identification' => $guest->taxProfile?->identification ?? 'S/N',
                        ];
                    })->toArray();
                }
            }
            
            // Fallback: use legacy reservation->guests() relationship for backward compatibility
            // Only if no guests found in ReservationRoom
            if (empty($guests)) {
                $reservation->load('guests.taxProfile');
                if ($reservation->guests->count() > 0) {
                    $guests = $reservation->guests->map(function($guest) {
                        return [
                            'id' => $guest->id,
                            'name' => $guest->name,
                            'phone' => $guest->phone ?? 'S/N',
                            'email' => $guest->email,
                            'identification' => $guest->taxProfile?->identification ?? 'S/N',
                        ];
                    })->toArray();
                }
            }

            $this->detailData = [
                'room' => $room->toArray(),
                'reservation' => $reservation->toArray(),
                'customer' => $reservation->customer->toArray(),
                'identification' => $reservation->customer->taxProfile?->identification ?? 'N/A',
                'guests' => $guests,
                'total_hospedaje' => $total_hospedaje,
                'abono_realizado' => $abono,
                'sales_total' => $consumos_pagados + $consumos_pendientes,
                'consumos_pendientes' => $consumos_pendientes,
                'total_debt' => $total_debt,
                'sales' => $reservation->sales->toArray(),
                'stay_history' => $stay_history,
                'deposit_history' => $reservation->reservationDeposits->map(function($deposit) {
                    return [
                        'id' => $deposit->id,
                        'amount' => $deposit->amount,
                        'payment_method' => $deposit->payment_method,
                        'notes' => $deposit->notes,
                        'created_at' => $deposit->created_at->format('d/m/Y H:i'),
                    ];
                })->toArray(),
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
        $reservation->update([
            'deposit' => $reservation->total_amount,
            'is_paid' => true
        ]);

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

    public function addSale()
    {
        $this->validate([
            'newSale.product_id' => 'required|exists:products,id',
            'newSale.quantity' => 'required|integer|min:1',
            'newSale.payment_method' => 'required|in:efectivo,transferencia,pendiente',
        ]);

        $room = Room::find($this->selectedRoomId);
        $date = Carbon::parse($this->date)->startOfDay();
        $dateString = $date->toDateString(); // Format as Y-m-d

        $reservation = $room->reservations()
            ->where('check_in_date', '<=', $dateString)
            ->where('check_out_date', '>', $dateString)
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
        $reservation->update(['deposit' => $newAmount]);

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Abono actualizado.');
    }

    public function addDeposit($reservationId, $amount, $paymentMethod, $notes = null)
    {
        $reservation = Reservation::findOrFail($reservationId);

        // Validate amount
        if ($amount <= 0) {
            $this->dispatch('notify', type: 'error', message: 'El monto debe ser mayor a 0.');
            return;
        }

        // Create deposit record
        ReservationDeposit::create([
            'reservation_id' => $reservationId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
        ]);

        // Update total deposit in reservation
        $reservation->increment('deposit', $amount);

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Abono registrado correctamente.');
    }

    public function editDepositRecord($depositId, $amount, $paymentMethod, $notes = null)
    {
        $deposit = ReservationDeposit::findOrFail($depositId);
        $reservation = $deposit->reservation;

        // Validate amount
        if ($amount <= 0) {
            $this->dispatch('notify', type: 'error', message: 'El monto debe ser mayor a 0.');
            return;
        }

        // Calculate difference to update reservation deposit
        $oldAmount = $deposit->amount;
        $difference = $amount - $oldAmount;

        // Update deposit record
        $deposit->update([
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
        ]);

        // Update total deposit in reservation
        $newDepositTotal = max(0, $reservation->deposit + $difference);
        $reservation->update(['deposit' => $newDepositTotal]);

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Abono actualizado correctamente.');
    }

    public function deleteDepositRecord($depositId)
    {
        $deposit = ReservationDeposit::findOrFail($depositId);
        $reservation = $deposit->reservation;
        $amount = $deposit->amount;

        // Delete deposit record
        $deposit->delete();

        // Update total deposit in reservation (subtract the deleted amount)
        $newDepositTotal = max(0, $reservation->deposit - $amount);
        $reservation->update(['deposit' => $newDepositTotal]);

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Abono eliminado correctamente.');
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
                // Use whereDate() which is specifically designed for date column comparisons
                $reservation = $room->reservations()
                    ->whereDate('check_in_date', '<=', $date)
                    ->whereDate('check_out_date', '>', $date)
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

        // Mark as just updated to skip next listener query (1 second cache)
        Cache::put("room_updated_{$room->id}", true, 1);
        
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

        // Mark as just updated to skip next listener query (1 second cache)
        Cache::put("room_updated_{$room->id}", true, 1);
        
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
                ->whereDate('check_in_date', '<=', $date)
                ->where(function($query) use ($date) {
                    $query->whereDate('check_out_date', '>', $date)
                          ->orWhereDate('check_out_date', '=', $date);
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

        // Mark as just updated to skip next listener query (1 second cache)
        Cache::put("room_updated_{$room->id}", true, 1);
        
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
            'people' => 1,
            'total' => $prices[1] ?? 0,
            'deposit' => 0,
            'check_out' => Carbon::parse($this->date)->addDay()->format('Y-m-d'),
            'payment_method' => 'efectivo',
            'customer_id' => ''
        ];

        // Ensure customers are loaded when opening modal
        if (empty($this->customers)) {
            $this->loadCustomers();
        }

        $this->quickRentModal = true;
        // Reset customer selection when opening modal
        $this->customerSearchTerm = '';
        // Don't show dropdown automatically, let user click to open it
        $this->showCustomerDropdown = false;
        // Reset guests when opening modal
        $this->quickRentGuests = [];
    }

    public function updatedRentForm($value, $key)
    {
        if ($key === 'people') {
            $maxCapacity = (int)$this->rentForm['max_capacity'];

            // Handle empty or null values
            if ($value === '' || $value === null || $value === 0) {
                $this->rentForm['people'] = 1;
                $people = 1;
            } else {
                $people = (int)$value;

                // Validate people count against max capacity
                if ($people > $maxCapacity) {
                    $this->addError('rentForm.people', "La capacidad máxima de esta habitación es de {$maxCapacity} persona(s).");
                    $this->rentForm['people'] = $maxCapacity;
                    $people = $maxCapacity;
                } elseif ($people < 1) {
                    $this->addError('rentForm.people', 'Debe haber al menos 1 persona.');
                    $this->rentForm['people'] = 1;
                    $people = 1;
                }
                // If value is valid (between 1 and maxCapacity), Livewire will automatically clear errors
            }

            // Always ensure guests array matches people count exactly
            // Re-index array first to ensure sequential indices (0, 1, 2, ...)
            $this->quickRentGuests = array_values($this->quickRentGuests);
            
            // If we have more guests than people count, keep only first N guests
            if (count($this->quickRentGuests) > $people) {
                $this->quickRentGuests = array_slice($this->quickRentGuests, 0, $people);
            }
            // Array is already properly indexed and sized correctly
            
            // Clear errors when valid
            $this->resetErrorBag('rentForm.people');
            $this->resetErrorBag('quickRentGuests');

            // Calculate total with validated people count
            $basePrice = $this->rentForm['prices'][$people] ?? ($this->rentForm['prices'][$maxCapacity] ?? 0);
            $start = Carbon::parse($this->date);
            $end = Carbon::parse($this->rentForm['check_out']);
            $diffDays = max(1, $start->diffInDays($end));
            $this->rentForm['total'] = $basePrice * $diffDays;
        } elseif ($key === 'check_out') {
            $p = (int)$this->rentForm['people'];
            $basePrice = $this->rentForm['prices'][$p] ?? ($this->rentForm['prices'][$this->rentForm['max_capacity']] ?? 0);
            $start = Carbon::parse($this->date);
            $end = Carbon::parse($this->rentForm['check_out']);
            $diffDays = max(1, $start->diffInDays($end));
            $this->rentForm['total'] = $basePrice * $diffDays;
        }
    }

    public function storeQuickRent()
    {
        $maxCapacity = (int)$this->rentForm['max_capacity'];

        $this->validate([
            'rentForm.customer_id' => 'required|exists:customers,id',
            'rentForm.people' => "required|integer|min:1|max:{$maxCapacity}",
            'rentForm.check_out' => 'required|date|after:'.$this->date,
            'rentForm.total' => 'required|numeric|min:0',
            'rentForm.deposit' => 'required|numeric|min:0',
            'rentForm.payment_method' => 'required|in:efectivo,transferencia',
        ], [
            'rentForm.people.required' => 'El número de personas es obligatorio.',
            'rentForm.people.integer' => 'El número de personas debe ser un número entero.',
            'rentForm.people.min' => 'Debe haber al menos 1 persona.',
            "rentForm.people.max" => "La capacidad máxima de esta habitación es de {$maxCapacity} persona(s).",
            'rentForm.customer_id.required' => 'Debe seleccionar un huésped.',
            'rentForm.customer_id.exists' => 'El huésped seleccionado no es válido.',
            'rentForm.check_out.required' => 'La fecha de salida es obligatoria.',
            'rentForm.check_out.date' => 'La fecha de salida debe ser una fecha válida.',
            'rentForm.check_out.after' => 'La fecha de salida debe ser posterior a la fecha de entrada.',
        ], [
            'rentForm.check_out' => 'fecha de salida',
        ]);

        $checkInDate = Carbon::parse($this->date);
        if ($checkInDate->isBefore(now()->startOfDay())) {
            $this->addError('rentForm.check_in_date', 'No se puede ingresar una reserva antes del día actual.');
            return;
        }

        // Create reservation within transaction for atomicity
        $reservation = DB::transaction(function() {
            $reservation = Reservation::create([
                'room_id' => $this->rentForm['room_id'],
                'customer_id' => $this->rentForm['customer_id'],
                'check_in_date' => $this->date,
                'check_out_date' => $this->rentForm['check_out'],
                'reservation_date' => now(),
                'guests_count' => $this->rentForm['people'],
                'total_amount' => $this->rentForm['total'],
                'deposit' => $this->rentForm['deposit'],
                'payment_method' => $this->rentForm['payment_method'],
                'is_paid' => $this->rentForm['deposit'] >= $this->rentForm['total'],
                'status' => 'confirmed'
            ]);

            // Create ReservationRoom and attach guests
            $reservationRoom = ReservationRoom::create([
                'reservation_id' => $reservation->id,
                'room_id' => $this->rentForm['room_id'],
            ]);

            // Attach guests to the reservation room if any
            if (!empty($this->quickRentGuests)) {
                $guestIds = array_column($this->quickRentGuests, 'id');
                $validGuestIds = array_filter($guestIds, function ($id): bool {
                    return !empty($id) && is_numeric($id) && $id > 0;
                });

                if (!empty($validGuestIds)) {
                    $validGuestIds = Customer::withoutGlobalScopes()
                        ->whereIn('id', $validGuestIds)
                        ->pluck('id')
                        ->toArray();

                    if (!empty($validGuestIds)) {
                        $reservationRoom->guests()->attach($validGuestIds);
                    }
                }
            }

            return $reservation;
        });

        // Close modal immediately for better UX
        $this->quickRentModal = false;

        // Dispatch notifications first (non-blocking)
        $this->dispatch('notify', type: 'success', message: 'Reserva creada exitosamente.');
        
        // Mark as just updated to skip next listener query (1 second cache)
        Cache::put("room_updated_{$this->rentForm['room_id']}", true, 1);
        
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
        // Use cache to avoid query if we just updated it
        $cacheKey = "room_updated_{$roomId}";
        $justUpdated = Cache::get($cacheKey, false);
        
        if (!$justUpdated) {
            // Actualizar trigger para forzar re-render mínimo (no ejecutar refreshRooms completo)
            // El render() siguiente verá los datos actualizados vía eager loading
            // Esto reduce latencia de ~500ms a ~200ms
            $this->refreshTrigger = now()->timestamp;
            
            // If the updated room detail is open, reload it
            if ($this->selectedRoomId == $roomId) {
                $this->loadRoomDetail();
            }
        }

        // Marcar que hubo actualización por evento (evita que el polling ejecute inmediatamente)
        $this->lastEventUpdate = now()->timestamp;
    }

    public function render()
    {
        // Ensure customers are loaded if empty (in case component was rehydrated)
        if (empty($this->customers)) {
            $this->loadCustomers();
        }

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
        if ($this->status) {
            $query->where('status', $this->status);
        }
        if ($this->ventilation_type) {
            $query->where('ventilation_type', $this->ventilation_type);
        }

        // Eager load reservations for the month range to optimize queries
        // We load a buffer (month range) to support calendar navigation, but
        // the transform will filter to the specific selected date
        // Use whereDate() which is specifically designed for date column comparisons
        $rooms = $query->with([
            'reservations' => function($q) use ($startOfMonth, $endOfMonth) {
                $q->whereDate('check_in_date', '<=', $endOfMonth)
                  ->whereDate('check_out_date', '>=', $startOfMonth)
                  ->with('customer');
            },
            'reservations.sales',
            'rates'
        ])->orderBy('room_number')->paginate(30);

        $rooms->getCollection()->transform(function($room) use ($date) {
            // Note: Reservations are eager loaded above with fresh query from DB
            // After releaseRoom() transaction commits, the next render() will load
            // fresh reservations data automatically via eager loading
            // We don't need to reload here because render() is called after releaseRoom()
            // completes, ensuring the eager loading query sees the updated check_out_date values

            $isFuture = $date->isAfter(now()->endOfDay());
            $reservation = null;

            // Solo buscamos reservas si NO es una fecha futura
            // Buscamos reservas activas (ocupadas) Y reservas que terminan hoy (para botón continuar)
            // Normalizar fechas a startOfDay para comparación consistente
            if (!$isFuture) {
                // First, try to find active reservation (check_out_date > $date)
                // After releaseRoom(), check_out_date should equal $date, so this should return null
                $reservation = $room->reservations->first(function($res) use ($date) {
                    $checkIn = Carbon::parse($res->check_in_date)->startOfDay();
                    $checkOut = Carbon::parse($res->check_out_date)->startOfDay();
                    // Habitación ocupada si: check_in_date <= $date AND check_out_date > $date
                    return $checkIn->lte($date) && $checkOut->gt($date);
                });

                // If no active reservation, check for reservation ending today or starting today (for continue/cancel buttons)
                if (!$reservation) {
                    $reservation = $room->reservations->first(function($res) use ($date) {
                        $checkIn = Carbon::parse($res->check_in_date)->startOfDay();
                        $checkOut = Carbon::parse($res->check_out_date)->startOfDay();
                        $tomorrow = $date->copy()->addDay()->startOfDay();
                        // Reservation ending today: check_in_date <= $date AND check_out_date == $date
                        // Or reservation starting today (one day): check_in_date == $date AND check_out_date == tomorrow
                        // Or reservation ending tomorrow: check_in_date <= $date AND check_out_date == tomorrow
                        return ($checkIn->lte($date) && $checkOut->eq($date)) ||
                               ($checkIn->eq($date) && $checkOut->eq($tomorrow)) ||
                               ($checkIn->lte($date) && $checkOut->eq($tomorrow));
                    });
                }

                // Also check if status is Pendiente Checkout and get the reservation
                if (!$reservation && $room->getDisplayStatus($date) === \App\Enums\RoomStatus::PENDIENTE_CHECKOUT) {
                    $reservation = $room->getPendingCheckoutReservation($date);
                }
            }

            // Store current reservation for display purposes
            // This includes both active reservations and reservations ending today
            $room->current_reservation = $reservation;

            if ($reservation) {
                $checkIn = Carbon::parse($reservation->check_in_date);
                $checkOut = Carbon::parse($reservation->check_out_date);
                $daysTotal = max(1, $checkIn->diffInDays($checkOut));
                $dailyPrice = (float)$reservation->total_amount / $daysTotal;

                $daysUntilSelected = $checkIn->diffInDays($date);
                $costUntilSelected = $dailyPrice * ($daysUntilSelected + 1);
                $costUntilSelected = min((float)$reservation->total_amount, $costUntilSelected);

                $room->is_night_paid = ($reservation->deposit >= $costUntilSelected);

                $stay_debt = (float)($reservation->total_amount - $reservation->deposit);
                $sales_debt = (float)$reservation->sales->where('is_paid', false)->sum('total');
                $room->total_debt = $stay_debt + $sales_debt;
            } else {
                $room->current_reservation = null;
                $room->total_debt = 0;
            }

            // Use getDisplayStatus() with the selected date to get correct status
            // This overrides the accessor which uses today() by default
            // After releaseRoom(), isOccupied($date) should return false, so display_status
            // will be PENDIENTE_ASEO (if cleaning needed) or LIBRE
            $room->display_status = $room->getDisplayStatus($date);
            $room->active_prices = $room->getPricesForDate($date);
            
            // Store cleaning status for the selected date to avoid recalculating in view
            $room->cleaning_status_for_date = $room->cleaningStatus($date);
            
            return $room;
        });

        // Filter by display_status (calculated for the selected date) after transform
        // This ensures the status filter works correctly for each selected date
        if ($this->status) {
            $filteredCollection = $rooms->getCollection()->filter(function($room) {
                return $room->display_status->value === $this->status;
            });
            $rooms->setCollection($filteredCollection);
        }

        // Load DIAN catalogs for customer creation
        $identificationDocuments = \App\Models\DianIdentificationDocument::orderBy('name')->get()->toArray();
        $municipalities = \App\Models\DianMunicipality::orderBy('department')->orderBy('name')->get()->toArray();
        $legalOrganizations = \App\Models\DianLegalOrganization::orderBy('name')->get()->toArray();
        $tributes = \App\Models\DianCustomerTribute::orderBy('name')->get()->toArray();

        return view('livewire.room-manager', [
            'rooms' => $rooms,
            'statuses' => RoomStatus::cases(),
            'ventilationTypes' => VentilationType::cases(),
            'daysInMonth' => $daysInMonth,
            'currentDate' => $date,
            'identificationDocuments' => $identificationDocuments,
            'municipalities' => $municipalities,
            'legalOrganizations' => $legalOrganizations,
            'tributes' => $tributes,
        ]);
    }

    public function updatedNewCustomer($value, $key): void
    {
        if ($key === 'identification') {
            $this->checkCustomerIdentification();
        } elseif ($key === 'identificationDocumentId') {
            $this->updateCustomerRequiredFields();
        }
    }

    public function updateCustomerRequiredFields(): void
    {
        $documentId = $this->newCustomer['identificationDocumentId'] ?? '';

        if (empty($documentId)) {
            $this->customerRequiresDV = false;
            $this->customerIsJuridicalPerson = false;
            $this->newCustomer['dv'] = '';
            return;
        }

        $document = \App\Models\DianIdentificationDocument::find($documentId);

        if ($document) {
            $this->customerRequiresDV = $document->requires_dv ?? false;
            $this->customerIsJuridicalPerson = in_array($document->code ?? '', ['NI', 'NIT'], true);

            if ($this->customerRequiresDV && !empty($this->newCustomer['identification'])) {
                $this->newCustomer['dv'] = $this->calculateVerificationDigit($this->newCustomer['identification']);
            } else {
                $this->newCustomer['dv'] = '';
            }
        } else {
            $this->customerRequiresDV = false;
            $this->customerIsJuridicalPerson = false;
            $this->newCustomer['dv'] = '';
        }
    }

    private function calculateVerificationDigit(string $nit): string
    {
        $nit = preg_replace('/\D/', '', $nit);
        $weights = [71, 67, 59, 53, 47, 43, 41, 37, 29, 23, 19, 17, 13, 7, 3];
        $sum = 0;
        $nitLength = strlen($nit);

        for ($i = 0; $i < $nitLength; $i++) {
            $sum += (int)$nit[$nitLength - 1 - $i] * $weights[$i];
        }

        $remainder = $sum % 11;
        if ($remainder < 2) {
            return (string)$remainder;
        }

        return (string)(11 - $remainder);
    }

    public function checkCustomerIdentification(): void
    {
        $identification = $this->newCustomer['identification'] ?? '';

        if (empty($identification)) {
            $this->customerIdentificationMessage = '';
            $this->customerIdentificationExists = false;
            return;
        }

        $exists = Customer::withoutGlobalScopes()
            ->whereHas('taxProfile', function ($query) use ($identification) {
                $query->where('identification', $identification);
            })
            ->exists();

        if ($exists) {
            $this->customerIdentificationExists = true;
            $this->customerIdentificationMessage = 'Esta identificación ya está registrada.';
        } else {
            $this->customerIdentificationExists = false;
            $this->customerIdentificationMessage = 'Identificación disponible.';
        }

        if ($this->customerRequiresDV && !empty($identification)) {
            $this->newCustomer['dv'] = $this->calculateVerificationDigit($identification);
        }
    }

    public function createCustomer(): void
    {
        $requiresElectronicInvoice = $this->newCustomer['requiresElectronicInvoice'] ?? false;

        $rules = [
            'newCustomer.name' => 'required|string|max:255',
            'newCustomer.identification' => 'required|string|max:10',
            'newCustomer.phone' => 'required|string|max:20',
            'newCustomer.email' => 'nullable|email|max:255',
            'newCustomer.address' => 'nullable|string|max:500',
        ];

        $messages = [
            'newCustomer.name.required' => 'El nombre es obligatorio.',
            'newCustomer.name.max' => 'El nombre no puede exceder 255 caracteres.',
            'newCustomer.identification.required' => 'La identificación es obligatoria.',
            'newCustomer.identification.max' => 'La identificación no puede exceder 10 dígitos.',
            'newCustomer.phone.required' => 'El teléfono es obligatorio.',
            'newCustomer.phone.max' => 'El teléfono no puede exceder 20 caracteres.',
            'newCustomer.email.email' => 'El email debe tener un formato válido.',
            'newCustomer.email.max' => 'El email no puede exceder 255 caracteres.',
            'newCustomer.address.max' => 'La dirección no puede exceder 500 caracteres.',
        ];

        if ($requiresElectronicInvoice) {
            $rules['newCustomer.identificationDocumentId'] = 'required|exists:dian_identification_documents,id';
            $rules['newCustomer.municipalityId'] = 'required|exists:dian_municipalities,factus_id';

            $messages['newCustomer.identificationDocumentId.required'] = 'El tipo de documento es obligatorio para facturación electrónica.';
            $messages['newCustomer.identificationDocumentId.exists'] = 'El tipo de documento seleccionado no es válido.';
            $messages['newCustomer.municipalityId.required'] = 'El municipio es obligatorio para facturación electrónica.';
            $messages['newCustomer.municipalityId.exists'] = 'El municipio seleccionado no es válido.';

            if ($this->customerIsJuridicalPerson) {
                $rules['newCustomer.company'] = 'required|string|max:255';
                $messages['newCustomer.company.required'] = 'La razón social es obligatoria para personas jurídicas (NIT).';
                $messages['newCustomer.company.max'] = 'La razón social no puede exceder 255 caracteres.';
            }
        }

        $this->validate($rules, $messages);

        $this->checkCustomerIdentification();
        if ($this->customerIdentificationExists) {
            $this->addError('newCustomer.identification', 'Esta identificación ya está registrada.');
            return;
        }

        $this->creatingCustomer = true;

        try {
            $customer = Customer::create([
                'name' => mb_strtoupper($this->newCustomer['name']),
                'phone' => $this->newCustomer['phone'],
                'email' => $this->newCustomer['email'] ?? null,
                'address' => $this->newCustomer['address'] ?? null,
                'is_active' => true,
                'requires_electronic_invoice' => $requiresElectronicInvoice,
            ]);

            // Use default values when electronic invoice is not required
            $municipalityId = $requiresElectronicInvoice
                ? ($this->newCustomer['municipalityId'] ?? null)
                : (\App\Models\CompanyTaxSetting::first()?->municipality_id
                    ?? \App\Models\DianMunicipality::first()?->factus_id
                    ?? 149); // Bogotá Factus ID as fallback

            $taxProfileData = [
                'identification' => $this->newCustomer['identification'],
                'dv' => $this->newCustomer['dv'] ?? null,
                'identification_document_id' => $requiresElectronicInvoice
                    ? ($this->newCustomer['identificationDocumentId'] ?? null)
                    : 3, // Default to CC (Cédula de Ciudadanía)
                'legal_organization_id' => $requiresElectronicInvoice
                    ? ($this->newCustomer['legalOrganizationId'] ?? null)
                    : 2, // Default to Persona Natural
                'tribute_id' => $requiresElectronicInvoice
                    ? ($this->newCustomer['tributeId'] ?? null)
                    : 21, // Default to No responsable de IVA
                'municipality_id' => $municipalityId,
                'company' => $requiresElectronicInvoice && $this->customerIsJuridicalPerson ? ($this->newCustomer['company'] ?? null) : null,
                'trade_name' => $requiresElectronicInvoice ? ($this->newCustomer['tradeName'] ?? null) : null,
            ];

            $customer->taxProfile()->create($taxProfileData);

            // Add customer to the list at the beginning (most recent first)
            $newCustomerArray = [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone ?? 'S/N',
                'email' => $customer->email ?? null,
                'taxProfile' => $customer->taxProfile ? [
                    'identification' => $customer->taxProfile->identification ?? 'S/N',
                    'dv' => $customer->taxProfile->dv ?? null,
                ] : null,
            ];
            
            // Ensure customers array exists and prepend new customer
            if (!is_array($this->customers)) {
                $this->customers = [];
            }
            array_unshift($this->customers, $newCustomerArray);

            // Select the newly created customer
            $this->rentForm['customer_id'] = (string) $customer->id;
            $this->customerId = (string) $customer->id;

            // Reset form and close modal
            $this->resetNewCustomerForm();
            $this->newCustomerModalOpen = false;
            $this->showCustomerDropdown = false;
            $this->customerSearchTerm = '';

            // Refresh customer select
            $this->dispatch('notify', type: 'success', message: 'Cliente creado exitosamente.');
        } catch (\Exception $e) {
            \Log::error('Error creating customer: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $this->newCustomer
            ]);
            $this->addError('newCustomer.name', 'Error al crear el cliente. Por favor intente nuevamente.');
        } finally {
            $this->creatingCustomer = false;
        }
    }

    public function openNewCustomerModal(): void
    {
        $this->newCustomerModalOpen = true;
    }

    public function closeNewCustomerModal(): void
    {
        $this->newCustomerModalOpen = false;
        $this->resetNewCustomerForm();
    }

    private function resetNewCustomerForm(): void
    {
        $this->newCustomer = [
            'name' => '',
            'identification' => '',
            'phone' => '',
            'email' => '',
            'address' => '',
            'requiresElectronicInvoice' => false,
            'identificationDocumentId' => '',
            'dv' => '',
            'company' => '',
            'tradeName' => '',
            'municipalityId' => '',
            'legalOrganizationId' => '',
            'tributeId' => ''
        ];
        $this->customerIdentificationMessage = '';
        $this->customerIdentificationExists = false;
        $this->customerRequiresDV = false;
        $this->customerIsJuridicalPerson = false;
    }

    /**
     * Customer selector methods
     */
    public function updatedCustomerSearchTerm($value): void
    {
        // Keep dropdown open when typing (debounced in view)
        // No need to reload customers - filtering happens in computed property
        $this->showCustomerDropdown = true;
    }

    public function openCustomerDropdown(): void
    {
        // Ensure customers are loaded before showing dropdown
        if (empty($this->customers) || !is_array($this->customers)) {
            $this->loadCustomers();
        }
        // Always show dropdown when clicked
        $this->showCustomerDropdown = true;
    }

    public function closeCustomerDropdown(): void
    {
        $this->showCustomerDropdown = false;
    }

    public function selectCustomer($customerId): void
    {
        $this->rentForm['customer_id'] = (string) $customerId;
        $this->customerId = (string) $customerId;
        $this->customerSearchTerm = '';
        $this->showCustomerDropdown = false;

        // Add customer as first guest automatically if not already in list
        $customer = collect($this->customers)->first(function($customer) use ($customerId) {
            return (string)($customer['id'] ?? '') === (string)$customerId;
        });

        if ($customer) {
            // Check if customer is already in guests list
            $existingIndex = null;
            foreach ($this->quickRentGuests as $index => $guest) {
                if ((int)($guest['id'] ?? 0) === (int)$customerId) {
                    $existingIndex = $index;
                    break;
                }
            }

            // If not in list, add as first guest
            if ($existingIndex === null) {
                $taxProfile = $customer['taxProfile'] ?? null;
                $guestData = [
                    'id' => (int)$customerId,
                    'name' => $customer['name'] ?? '',
                    'identification' => ($taxProfile && isset($taxProfile['identification'])) ? $taxProfile['identification'] : 'S/N',
                    'phone' => $customer['phone'] ?? 'S/N',
                    'email' => $customer['email'] ?? null,
                ];
                
                // Add at position 0
                array_unshift($this->quickRentGuests, $guestData);
                $this->quickRentGuests = array_values($this->quickRentGuests);
                
                // Ensure we don't exceed max capacity
                $maxCapacity = (int)($this->rentForm['max_capacity'] ?? 1);
                if (count($this->quickRentGuests) > $maxCapacity) {
                    $this->quickRentGuests = array_slice($this->quickRentGuests, 0, $maxCapacity);
                }
            } else {
                // If already in list but not first, move to first position
                if ($existingIndex > 0) {
                    $guest = $this->quickRentGuests[$existingIndex];
                    unset($this->quickRentGuests[$existingIndex]);
                    array_unshift($this->quickRentGuests, $guest);
                    $this->quickRentGuests = array_values($this->quickRentGuests);
                }
            }
        }
    }

    public function addQuickRentGuest($customer, ?int $targetIndex = null): void
    {
        $maxCapacity = (int)($this->rentForm['max_capacity'] ?? 1);
        $peopleCount = (int)($this->rentForm['people'] ?? 1);

        // Check if already added
        $customerId = (int)($customer['id'] ?? 0);
        $existingIds = array_column($this->quickRentGuests, 'id');
        if (in_array($customerId, $existingIds, true)) {
            $this->addError('quickRentGuests', 'Este cliente ya está asignado como huésped.');
            return;
        }

        // Validate against people count
        if (count($this->quickRentGuests) >= $peopleCount) {
            $this->addError('quickRentGuests', "Ya has asignado {$peopleCount} huésped(es). Aumenta la cantidad de personas para agregar más.");
            return;
        }

        // Validate against max capacity
        if (count($this->quickRentGuests) >= $maxCapacity) {
            $this->addError('quickRentGuests', "La habitación ha alcanzado su capacidad máxima de {$maxCapacity} persona(s).");
            return;
        }

        // Add guest
        $taxProfile = $customer['taxProfile'] ?? null;
        $guestData = [
            'id' => $customerId,
            'name' => $customer['name'] ?? '',
            'identification' => ($taxProfile && isset($taxProfile['identification'])) ? $taxProfile['identification'] : 'S/N',
            'phone' => $customer['phone'] ?? 'S/N',
            'email' => $customer['email'] ?? null,
        ];
        
        // Simply append the guest - the view will match by index in the @for loop
        // We maintain sequential array (0, 1, 2, ...) so slots match correctly
        $this->quickRentGuests[] = $guestData;
        
        // Ensure sequential indexing (this should already be the case, but be safe)
        $this->quickRentGuests = array_values($this->quickRentGuests);
        
        // Clear any previous errors
        $this->resetErrorBag('quickRentGuests');
    }

    public function removeQuickRentGuest($index): void
    {
        if (isset($this->quickRentGuests[$index])) {
            unset($this->quickRentGuests[$index]);
            $this->quickRentGuests = array_values($this->quickRentGuests);
            
            // Don't update people count automatically - let user control it
            // If people count is greater than guests count, empty slots will be shown
        }
    }

    public function addGuestToQuickRent($customerId, ?int $targetIndex = null): void
    {
        $customer = collect($this->customers)->first(function($customer) use ($customerId) {
            return (string)($customer['id'] ?? '') === (string)$customerId;
        });

        if ($customer) {
            $this->addQuickRentGuest($customer, $targetIndex);
            $this->customerSearchTerm = '';
            $this->showCustomerDropdown = false;
        }
    }

    public function clearCustomerSelection(): void
    {
        $customerIdToRemove = (int)$this->customerId;
        
        $this->rentForm['customer_id'] = '';
        $this->customerId = '';
        $this->customerSearchTerm = '';
        $this->showCustomerDropdown = false;
        
        // Remove customer from guests list if it exists
        if ($customerIdToRemove > 0 && !empty($this->quickRentGuests)) {
            $this->quickRentGuests = array_filter($this->quickRentGuests, function($guest) use ($customerIdToRemove) {
                return (int)($guest['id'] ?? 0) !== $customerIdToRemove;
            });
            $this->quickRentGuests = array_values($this->quickRentGuests);
        }
    }

    public function getFilteredCustomersProperty(): array
    {
        // Ensure customers are loaded
        if (empty($this->customers) || !is_array($this->customers)) {
            $this->loadCustomers();
        }
        
        $allCustomers = is_array($this->customers) ? $this->customers : [];

        // Get customer IDs that are already assigned as guests in active reservations for the selected date
        $assignedGuestIds = $this->getAssignedGuestIdsForDate();

        // Filter out already assigned guests
        $availableCustomers = array_filter($allCustomers, function($customer) use ($assignedGuestIds) {
            $customerId = (int)($customer['id'] ?? 0);
            return !in_array($customerId, $assignedGuestIds, true);
        });

        // If no search term, return first 5 available customers
        if (empty($this->customerSearchTerm)) {
            return array_slice($availableCustomers, 0, 5);
        }

        $searchTerm = trim($this->customerSearchTerm);
        if (empty($searchTerm)) {
            return array_slice($availableCustomers, 0, 5);
        }

        $searchTermLower = mb_strtolower($searchTerm);
        $filtered = [];
        $limit = 20;

        foreach ($availableCustomers as $customer) {
            // Optimized: check most common fields first and use early returns
            $name = $customer['name'] ?? '';
            if (!empty($name) && str_contains(mb_strtolower($name), $searchTermLower)) {
                $filtered[] = $customer;
                if (count($filtered) >= $limit) {
                    break;
                }
                continue;
            }

            $taxProfile = $customer['taxProfile'] ?? null;
            $identification = ($taxProfile && isset($taxProfile['identification'])) ? $taxProfile['identification'] : '';
            if (!empty($identification) && str_contains($identification, $searchTerm)) {
                $filtered[] = $customer;
                if (count($filtered) >= $limit) {
                    break;
                }
                continue;
            }

            $phone = $customer['phone'] ?? '';
            if (!empty($phone) && $phone !== 'S/N' && str_contains(mb_strtolower($phone), $searchTermLower)) {
                $filtered[] = $customer;
                if (count($filtered) >= $limit) {
                    break;
                }
            }
        }

        return $filtered;
    }

    /**
     * Get customer IDs that are already assigned as guests in active reservations for the selected date
     */
    private function getAssignedGuestIdsForDate(): array
    {
        try {
            $date = Carbon::parse($this->date)->startOfDay();
            // Use whereRaw with DATE() function for raw query builder
            // whereDate() doesn't work with DB::table, so we use DATE() function
            $dateString = $date->toDateString();
            $guestIdsFromRoomGuests = DB::table('reservation_room_guests')
                ->join('reservation_rooms', 'reservation_room_guests.reservation_room_id', '=', 'reservation_rooms.id')
                ->join('reservations', 'reservation_rooms.reservation_id', '=', 'reservations.id')
                ->whereRaw('DATE(reservations.check_in_date) <= ?', [$dateString])
                ->whereRaw('DATE(reservations.check_out_date) > ?', [$dateString])
                ->pluck('reservation_room_guests.customer_id')
                ->unique()
                ->toArray();

            // Get guest IDs from reservation_guests (legacy structure)
            $guestIdsFromReservations = DB::table('reservation_guests')
                ->join('reservations', 'reservation_guests.reservation_id', '=', 'reservations.id')
                ->whereRaw('DATE(reservations.check_in_date) <= ?', [$dateString])
                ->whereRaw('DATE(reservations.check_out_date) > ?', [$dateString])
                ->pluck('reservation_guests.customer_id')
                ->unique()
                ->toArray();

            // Merge and return unique customer IDs
            return array_values(array_unique(array_merge($guestIdsFromRoomGuests, $guestIdsFromReservations)));
        } catch (\Exception $e) {
            \Log::error('Error getting assigned guest IDs: ' . $e->getMessage());
            return [];
        }
    }
}
