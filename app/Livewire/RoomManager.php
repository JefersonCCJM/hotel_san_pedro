<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\VentilationType;
use App\Models\ReservationRoom;
use App\Models\Reservation;
use App\Enums\RoomDisplayStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoomManager extends Component
{
    use WithPagination;

    // Propiedades de estado
    public string $activeTab = 'rooms';
    public $currentDate = null;
    public $date = null;
    public $search = '';
    public $statusFilter = null;
    public $ventilationTypeFilter = null;

    // Modales
    public bool $quickRentModal = false;
    public bool $roomDetailModal = false;
    public bool $roomEditModal = false;
    public bool $createRoomModal = false;
    public bool $releaseHistoryDetailModal = false;
    public bool $roomReleaseConfirmationModal = false;
    public bool $guestsModal = false;

    // Datos de modales
    public ?array $detailData = null;
    public ?array $rentForm = null;
    public ?array $additionalGuests = null;
    public ?array $releaseHistoryDetail = null;
    public ?array $roomEditData = null;
    public ?array $newSale = null;
    public ?array $newDeposit = null;
    public bool $showAddSale = false;
    public bool $showAddDeposit = false;

    // Propiedades derivadas
    public $daysInMonth = null;
    public ?array $statuses = null;
    public $ventilationTypes = null;
    public ?object $releaseHistory = null;

    protected $listeners = [
        'room-created' => '$refresh',
        'room-updated' => '$refresh',
        'refreshRooms' => '$refresh',
    ];

    public function mount($date = null, $search = null, $status = null)
    {
        $this->currentDate = $date ? Carbon::parse($date) : now();
        $this->date = $this->currentDate;
        $this->search = $search ?? '';
        $this->statusFilter = $status;
        
        // Generar array de días del mes
        $startOfMonth = $this->currentDate->copy()->startOfMonth();
        $daysCount = $this->currentDate->daysInMonth;
        $this->daysInMonth = collect(range(1, $daysCount))
            ->map(fn($day) => $startOfMonth->copy()->day($day))
            ->toArray();

        // Cargar catálogos
        $this->loadStatuses();
        $this->loadVentilationTypes();

        // Cargar datos iniciales
        $this->loadReleaseHistory();
    }

    public function loadStatuses()
    {
        $this->statuses = RoomDisplayStatus::cases();
    }

    public function loadVentilationTypes()
    {
        $this->ventilationTypes = VentilationType::all(['id', 'code', 'name']);
    }

    protected function getRoomsQuery()
    {
        $query = Room::query();

        if ($this->search) {
            $query->where('room_number', 'like', '%' . $this->search . '%');
        }

        if ($this->ventilationTypeFilter) {
            $query->where('ventilation_type_id', $this->ventilationTypeFilter);
        }

        $startOfMonth = $this->currentDate->copy()->startOfMonth();
        $endOfMonth = $this->currentDate->copy()->endOfMonth();

        return $query->with([
            'roomType',
            'ventilationType',
            'reservationRooms' => function($q) use ($startOfMonth, $endOfMonth) {
                $q->where('check_in_date', '<=', $endOfMonth->toDateString())
                  ->where('check_out_date', '>=', $startOfMonth->toDateString())
                  ->with(['reservation' => function($r) {
                      $r->with(['customer', 'sales', 'payments']);
                  }]);
            },
            'rates',
            'maintenanceBlocks' => function($q) {
                $q->where('status_id', function($subq) {
                    $subq->select('id')->from('room_maintenance_block_statuses')
                        ->where('code', 'active');
                });
            }
        ])
        ->orderBy('room_number');
    }

    public function loadReleaseHistory()
    {
        // Cargar historial de liberación de habitaciones
        // Se implementará cuando exista la tabla de historial
        $this->releaseHistory = collect([]);
    }

    /**
     * Carga huéspedes de la reserva activa de una habitación
     * Placeholder que devuelve datos mínimos para el modal.
     */
    public function loadRoomGuests($roomId)
    {
        $room = Room::with([
            'reservationRooms.reservation.customer',
            'reservationRooms.reservation.customer.taxProfile',
            'reservationRooms.guests.taxProfile',
        ])
            ->find($roomId);

        if (!$room) {
            return ['guests' => [], 'customer' => null];
        }

        // Detectar reservation_room activa en la fecha
        $activeReservationRoom = $room->reservationRooms
            ->first(function($rr) {
                return $rr->check_in_date <= $this->date->toDateString()
                    && $rr->check_out_date >= $this->date->toDateString();
            });

        $activeReservation = $activeReservationRoom?->reservation ?? $room->getActiveReservation($this->date);
        $customer = $activeReservation?->customer;

        $mainGuest = $customer ? [
            'id' => $customer->id,
            'name' => $customer->name,
            'identification' => $customer->taxProfile?->identification,
            'phone' => $customer->phone ?? null,
            'email' => $customer->email ?? null,
            'is_main' => true,
        ] : null;

        $additionalGuests = collect();
        if ($activeReservationRoom && $activeReservationRoom->guests) {
            $additionalGuests = $activeReservationRoom->guests->map(function($guest) {
                return [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'identification' => $guest->taxProfile?->identification,
                    'phone' => $guest->phone ?? null,
                    'email' => $guest->email ?? null,
                    'is_main' => false,
                ];
            });
        }

        $guests = collect();
        if ($mainGuest) {
            $guests->push($mainGuest);
        }
        $guests = $guests->merge($additionalGuests);

        return [
            'room_number' => $room->room_number,
            'main_guest' => $mainGuest,
            'guests' => $guests->values()->toArray(),
        ];
    }


    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function refreshRoomsPolling()
    {
        // Livewire automatically re-renders, no need to manually load
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedVentilationTypeFilter()
    {
        $this->resetPage();
    }

    public function goToDate($date)
    {
        $this->date = Carbon::parse($date);
        $this->currentDate = $this->date;
    }

    public function nextDay()
    {
        $this->date = $this->date->copy()->addDay();
    }

    public function previousDay()
    {
        $this->date = $this->date->copy()->subDay();
    }

    /**
     * Cambia la fecha actual y regenera el arreglo de días del mes para los filtros.
     */
    public function changeDate($newDate)
    {
        $this->date = Carbon::parse($newDate);
        $this->currentDate = $this->date;

        $startOfMonth = $this->currentDate->copy()->startOfMonth();
        $daysCount = $this->currentDate->daysInMonth;
        $this->daysInMonth = collect(range(1, $daysCount))
            ->map(fn($day) => $startOfMonth->copy()->day($day))
            ->toArray();

        $this->resetPage();
    }

    public function goToToday()
    {
        $this->date = now();
        $this->currentDate = $this->date;
    }

    public function openRoomDetail($roomId)
    {
        $room = Room::with([
            'reservationRooms' => function($q) {
                $q->where('check_in_date', '<=', $this->date->toDateString())
                  ->where('check_out_date', '>=', $this->date->toDateString());
            },
            'reservationRooms.reservation.customer',
            'reservationRooms.reservation.sales.product',
            'reservationRooms.reservation.payments',
            'rates',
            'maintenanceBlocks'
        ])->find($roomId);

        if (!$room) {
            return;
        }

        $activeReservation = $room->getActiveReservation($this->date);
        $sales = collect();
        $payments = collect();
        $totalHospedaje = 0;
        $abonoRealizado = 0;
        $salesTotal = 0;
        $totalDebt = 0;
        $identification = null;
        $stayHistory = [];

        if ($activeReservation) {
            $sales = $activeReservation->sales ?? collect();
            $payments = $activeReservation->payments ?? collect();

            $reservationRoom = $room->reservationRooms->first();
            $nights = 0;
            $pricePerNight = 0;

            if ($reservationRoom) {
                $checkIn = Carbon::parse($reservationRoom->check_in_date);
                $checkOut = Carbon::parse($reservationRoom->check_out_date);
                $nights = $reservationRoom->nights ?? $checkIn->diffInDays($checkOut);
                if ($nights <= 0) {
                    $nights = 1; // mostrar al menos una noche
                }

                $pricePerNight = (float)($reservationRoom->price_per_night ?? 0);
                if ($pricePerNight == 0 && $activeReservation->total_amount && $nights > 0) {
                    $pricePerNight = (float)$activeReservation->total_amount / $nights;
                }
                if ($pricePerNight == 0 && $room->rates?->isNotEmpty()) {
                    $pricePerNight = (float)($room->rates->sortBy('min_guests')->first()->price_per_night ?? 0);
                }
                if ($pricePerNight == 0) {
                    $pricePerNight = (float)($room->base_price_per_night ?? 0);
                }

                for ($i = 0; $i < $nights; $i++) {
                    $stayHistory[] = [
                        'date' => $checkIn->copy()->addDays($i)->format('Y-m-d'),
                        'price' => $pricePerNight,
                        'is_paid' => false, // TODO: flag real por noche si existe
                    ];
                }

                $totalHospedaje = $pricePerNight * $nights;
            }

            if ($totalHospedaje == 0) {
                $totalHospedaje = (float)($activeReservation->total_amount ?? 0);
            }

            $abonoRealizado = (float)($payments->sum('amount') ?? 0);
            $salesTotal = (float)($sales->sum('total') ?? 0);
            $salesDebt = (float)($sales->where('is_paid', false)->sum('total') ?? 0);
            $totalDebt = ($totalHospedaje - $abonoRealizado) + $salesDebt;
            $identification = $activeReservation->customer->taxProfile->identification ?? null;
        }

        $this->detailData = [
            'room' => $room,
            'reservation' => $activeReservation,
            'display_status' => $room->getDisplayStatus($this->date),
            'sales' => $sales->map(function($sale) {
                return [
                    'id' => $sale->id,
                    'product' => [
                        'name' => $sale->product->name ?? null,
                    ],
                    'quantity' => $sale->quantity ?? 0,
                    'is_paid' => (bool)($sale->is_paid ?? false),
                    'payment_method' => $sale->payment_method ?? null,
                    'total' => (float)($sale->total ?? 0),
                ];
            })->values()->toArray(),
            'payments_history' => $payments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => (float)($payment->amount ?? 0),
                    'method' => $payment->paymentMethod->name ?? null,
                    'created_at' => $payment->created_at,
                ];
            })->values()->toArray(),
            'total_hospedaje' => $totalHospedaje,
            'abono_realizado' => $abonoRealizado,
            'sales_total' => $salesTotal,
            'total_debt' => $totalDebt,
            'identification' => $identification,
            'stay_history' => $stayHistory,
            'deposit_history' => $payments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => (float)($payment->amount ?? 0),
                    'payment_method' => $payment->paymentMethod->name ?? 'N/A',
                    'notes' => $payment->notes ?? null,
                    'created_at' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i') : null,
                ];
            })->values()->toArray(),
            'refunds_history' => [],
            'is_past_date' => $this->date->lt(now()->startOfDay()),
        ];

        $this->roomDetailModal = true;
    }

    public function closeRoomDetail()
    {
        $this->roomDetailModal = false;
        $this->detailData = null;
    }

    public function toggleAddSale(): void
    {
        $this->showAddSale = !$this->showAddSale;
        if ($this->showAddSale) {
            $this->newSale = [
                'product_id' => null,
                'quantity' => 1,
                'payment_method' => 'efectivo',
            ];
        } else {
            $this->newSale = null;
        }
    }

    public function toggleAddDeposit(): void
    {
        $this->showAddDeposit = !$this->showAddDeposit;
        if ($this->showAddDeposit) {
            $this->newDeposit = [
                'amount' => null,
                'payment_method' => 'efectivo',
                'notes' => null,
            ];
        } else {
            $this->newDeposit = null;
        }
    }

    public function addSale(): void
    {
        // Placeholder: integrate with Sales logic if/when available
        $this->dispatch('notify', type: 'error', message: 'Registrar consumo no está habilitado todavía.');
    }

    public function addDeposit(): void
    {
        // Placeholder: integrate with payments when ready
        $this->dispatch('notify', type: 'error', message: 'Agregar abono no está habilitado todavía.');
    }

    public function openQuickRent($roomId)
    {
        $room = Room::with('rates')->find($roomId);
        if ($room) {
            // Calculate base price from rates or fallback to base_price_per_night
            $basePrice = 0;
            if ($room->rates && $room->rates->isNotEmpty()) {
                $firstRate = $room->rates->sortBy('min_guests')->first();
                $basePrice = $firstRate->price_per_night ?? 0;
            }
            if ($basePrice == 0 && $room->base_price_per_night) {
                $basePrice = $room->base_price_per_night;
            }

            $this->rentForm = [
                'room_id' => $roomId,
                'room_number' => $room->room_number,
                'check_in_date' => $this->date->toDateString(),
                'check_out_date' => $this->date->copy()->addDay()->toDateString(),
                'client_id' => null,
                'guests_count' => 1,
                'max_capacity' => $room->max_capacity,
                'total' => $basePrice,
                'deposit' => 0,
                'payment_method' => 'efectivo',
            ];
            $this->additionalGuests = [];
            $this->quickRentModal = true;
            $this->dispatch('quickRentOpened');
        }
    }

    public function closeQuickRent()
    {
        $this->quickRentModal = false;
        $this->rentForm = null;
        $this->additionalGuests = null;
    }

    public function addGuestFromCustomerId($customerId)
    {
        $customer = \App\Models\Customer::find($customerId);
        
        if (!$customer) {
            $this->dispatch('notify', type: 'error', message: 'Cliente no encontrado.');
            return;
        }

        // Check if already added
        if (is_array($this->additionalGuests)) {
            foreach ($this->additionalGuests as $guest) {
                if (isset($guest['customer_id']) && $guest['customer_id'] == $customerId) {
                    $this->dispatch('notify', type: 'error', message: 'Este cliente ya fue agregado como huésped adicional.');
                    return;
                }
            }
        } else {
            $this->additionalGuests = [];
        }

        // Add guest
        $this->additionalGuests[] = [
            'customer_id' => $customer->id,
            'name' => $customer->name,
            'identification' => $customer->taxProfile?->identification ?? 'N/A',
        ];

        $this->dispatch('guest-added');
        $this->dispatch('notify', type: 'success', message: 'Huésped adicional agregado.');
    }

    public function removeGuest($index)
    {
        if (isset($this->additionalGuests[$index])) {
            unset($this->additionalGuests[$index]);
            $this->additionalGuests = array_values($this->additionalGuests);
            $this->dispatch('notify', type: 'success', message: 'Huésped removido.');
        }
    }

    public function submitQuickRent()
    {
        if (!$this->rentForm) {
            return;
        }

        try {
            $validated = [
                'room_id' => $this->rentForm['room_id'],
                'check_in_date' => $this->rentForm['check_in_date'],
                'check_out_date' => $this->rentForm['check_out_date'],
                'client_id' => $this->rentForm['client_id'],
                'guests_count' => $this->rentForm['guests_count'],
            ];

            $room = Room::with('rates')->find($validated['room_id']);
            if (!$room) {
                throw new \RuntimeException('Habitación no encontrada');
            }

            $checkIn = Carbon::parse($validated['check_in_date']);
            $checkOut = Carbon::parse($validated['check_out_date']);
            $nights = max(1, $checkIn->diffInDays($checkOut));

            // Precio por noche: rate más baja por min_guests, o base
            $pricePerNight = 0;
            if ($room->rates && $room->rates->isNotEmpty()) {
                $pricePerNight = (float)($room->rates->sortBy('min_guests')->first()->price_per_night ?? 0);
            }
            if ($pricePerNight === 0) {
                $pricePerNight = (float)($room->base_price_per_night ?? 0);
            }

            $totalAmount = $pricePerNight * $nights;
            $depositAmount = 0; // No se captura abono inicial en quick rent
            $balanceDue = $totalAmount - $depositAmount;

            $paymentStatusCode = $balanceDue <= 0 ? 'paid' : ($depositAmount > 0 ? 'partial' : 'pending');
            $paymentStatusId = DB::table('payment_statuses')->where('code', $paymentStatusCode)->value('id');

            $reservationCode = sprintf('RSV-%s-%s', now()->format('YmdHis'), Str::upper(Str::random(4)));

            // Crear reserva y estadía
            $reservation = Reservation::create([
                'reservation_code' => $reservationCode,
                'client_id' => $validated['client_id'],
                'status_id' => 1, // pending
                'total_guests' => $validated['guests_count'],
                'adults' => $validated['guests_count'],
                'children' => 0,
                'total_amount' => $totalAmount,
                'deposit_amount' => $depositAmount,
                'balance_due' => $balanceDue,
                'payment_status_id' => $paymentStatusId,
                'source_id' => 1, // reception
                'created_by' => auth()->id(),
            ]);

            // Crear reservation_room
            ReservationRoom::create([
                'reservation_id' => $reservation->id,
                'room_id' => $validated['room_id'],
                'check_in_date' => $validated['check_in_date'],
                'check_out_date' => $validated['check_out_date'],
                'nights' => $nights,
                'price_per_night' => $pricePerNight,
            ]);

            $this->dispatch('notify', type: 'success', message: 'Arriendo registrado exitosamente.');
            $this->closeQuickRent();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Error: ' . $e->getMessage());
        }
    }

    public function storeQuickRent()
    {
        return $this->submitQuickRent();
    }

    public function openRoomEdit($roomId)
    {
        $room = Room::with(['roomType', 'ventilationType', 'rates'])->find($roomId);
        if ($room) {
            $this->roomEditData = [
                'room' => $room,
                'ventilation_types' => $this->ventilationTypes,
                'statuses' => $this->statuses,
                'isOccupied' => $room->isOccupied(),
            ];
            $this->roomEditModal = true;
        }
    }

    public function closeRoomEdit()
    {
        $this->roomEditModal = false;
        $this->roomEditData = null;
    }

    public function openReleaseHistoryDetail($roomId)
    {
        $room = Room::find($roomId);
        if ($room) {
            $this->releaseHistoryDetail = [
                'room' => $room,
                'history' => collect([]), // Se implementará cuando exista la tabla
            ];
            $this->releaseHistoryDetailModal = true;
        }
    }

    public function closeReleaseHistoryDetail()
    {
        $this->releaseHistoryDetailModal = false;
        $this->releaseHistoryDetail = null;
    }

    public function openRoomReleaseConfirmation($roomId)
    {
        $room = Room::find($roomId);
        if ($room && $room->isOccupied()) {
            $this->detailData = [
                'room' => $room,
                'reservation' => $room->getActiveReservation($this->date),
            ];
            $this->roomReleaseConfirmationModal = true;
        }
    }

    public function closeRoomReleaseConfirmation()
    {
        $this->roomReleaseConfirmationModal = false;
    }

    public function loadRoomReleaseData($roomId, $isCancellation = false)
    {
        $room = Room::with([
            'reservationRooms.reservation' => function($q) {
                $q->with(['customer', 'sales.product', 'payments']);
            }
        ])->find($roomId);

        if (!$room) {
            return [
                'room_id' => $roomId,
                'room_number' => null,
                'reservation' => null,
                'sales' => [],
                'payments_history' => [],
                'refunds_history' => [],
                'total_hospedaje' => 0,
                'abono_realizado' => 0,
                'sales_total' => 0,
                'total_debt' => 0,
                'identification' => null,
                'is_cancellation' => $isCancellation,
            ];
        }

        $activeReservation = $room->getActiveReservation($this->date ?? now());
        $sales = collect();

        $totalHospedaje = 0;
        $abonoRealizado = 0;
        $salesTotal = 0;
        $totalDebt = 0;
        $payments = collect();
        $identification = null;

        if ($activeReservation) {
            $sales = $activeReservation->sales ?? collect();
            $payments = $activeReservation->payments ?? collect();

            $totalHospedaje = (float)($activeReservation->total_amount ?? 0);
            $abonoRealizado = (float)($activeReservation->deposit_amount ?? 0);
            $salesTotal = (float)($sales->sum('total') ?? 0);
            $salesDebt = (float)($sales->where('is_paid', false)->sum('total') ?? 0);
            $totalDebt = ($totalHospedaje - $abonoRealizado) + $salesDebt;
            $identification = $activeReservation->customer->taxProfile->identification ?? null;
        }

        return [
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'reservation' => $activeReservation ? $activeReservation->toArray() : null,
            'sales' => $sales->map(function($sale) {
                return [
                    'id' => $sale->id,
                    'product' => [
                        'name' => $sale->product->name ?? null,
                    ],
                    'quantity' => $sale->quantity ?? 0,
                    'is_paid' => (bool)($sale->is_paid ?? false),
                    'payment_method' => $sale->payment_method ?? null,
                    'total' => (float)($sale->total ?? 0),
                ];
            })->values()->toArray(),
            'payments_history' => $payments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => (float)($payment->amount ?? 0),
                    'method' => $payment->method ?? null,
                    'created_at' => $payment->created_at,
                ];
            })->values()->toArray(),
            'refunds_history' => [],
            'total_hospedaje' => $totalHospedaje,
            'abono_realizado' => $abonoRealizado,
            'sales_total' => $salesTotal,
            'total_debt' => $totalDebt,
            'identification' => $identification,
            'cancel_url' => null,
            'is_cancellation' => $isCancellation,
        ];
    }

    public function updateCleaningStatus($roomId, $status)
    {
        try {
            $room = Room::find($roomId);
            
            if (!$room) {
                $this->dispatch('notify', type: 'error', message: 'Habitación no encontrada.');
                return;
            }

            // Update cleaning status based on the status parameter
            if ($status === 'limpia') {
                $room->last_cleaned_at = now();
                $room->save();
                $this->dispatch('notify', type: 'success', message: 'Habitación marcada como limpia.');
            } elseif ($status === 'pendiente') {
                $room->last_cleaned_at = null;
                $room->save();
                $this->dispatch('notify', type: 'success', message: 'Habitación marcada como pendiente de limpieza.');
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Error al actualizar estado de limpieza: ' . $e->getMessage());
        }
    }

    public function confirmReleaseRoom($roomId)
    {
        // Implementar lógica de liberación de habitación
        try {
            $room = Room::find($roomId);
            if ($room && $room->isOccupied()) {
                // Realizar checkout y liberar habitación
                $this->dispatch('notify', type: 'success', message: 'Habitación liberada exitosamente.');
                $this->closeRoomReleaseConfirmation();
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Libera la habitación (checkout). Placeholder hasta implementar lógica completa.
     * @param int $roomId
     * @param string|null $status Ej: 'libre'
     */
    public function releaseRoom($roomId, $status = null)
    {
        try {
            $room = Room::find($roomId);
            if (!$room) {
                $this->dispatch('notify', type: 'error', message: 'Habitación no encontrada.');
                return;
            }
            // Buscar la estadía activa en reservation_rooms
            $activeReservationRoom = $room->reservationRooms()
                ->where('check_in_date', '<=', $this->date->toDateString())
                ->where('check_out_date', '>', $this->date->toDateString())
                ->orderBy('check_in_date', 'asc')
                ->first();

            if ($activeReservationRoom) {
                // Cerrar estadía adelantando check_out_date a hoy y marcando hora
                $activeReservationRoom->check_out_date = $this->date->toDateString();
                $activeReservationRoom->check_out_time = now()->format('H:i:s');
                $activeReservationRoom->save();

                // Opcional: marcar reservation como pendiente de checkout si hay deuda
                if ($activeReservationRoom->reservation) {
                    $reservation = $activeReservationRoom->reservation;
                    $paymentsTotal = (float)($reservation->payments?->sum('amount') ?? 0);
                    $salesDebt = (float)($reservation->sales?->where('is_paid', false)->sum('total') ?? 0);
                    $balance = (float)($reservation->total_amount ?? 0) - $paymentsTotal + $salesDebt;

                    $reservation->balance_due = $balance;
                    $reservation->payment_status_id = DB::table('payment_statuses')
                        ->where('code', $balance <= 0 ? 'paid' : ($paymentsTotal > 0 ? 'partial' : 'pending'))
                        ->value('id');
                    $reservation->save();
                }

                $this->dispatch('notify', type: 'success', message: 'Habitación liberada.');
            } else {
                $this->dispatch('notify', type: 'info', message: 'No se encontró una estadía activa para liberar.');
            }

            $this->closeRoomReleaseConfirmation();
            $this->refreshRoomsPolling();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Error al liberar habitación: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $rooms = $this->getRoomsQuery()->paginate(30);

        // Enriquecer rooms con estados y deudas
        $rooms->getCollection()->transform(function($room) {
            $room->display_status = $room->getDisplayStatus($this->date);
            $room->current_reservation = $room->getActiveReservation($this->date);
            if ($room->current_reservation) {
                $room->current_reservation->loadMissing(['customer']);
            }

            if ($room->current_reservation) {
                $reservationRoom = $room->reservationRooms?->first(function($rr) {
                    return $rr->check_in_date <= $this->date->toDateString()
                        && $rr->check_out_date >= $this->date->toDateString();
                });

                $checkIn = $reservationRoom?->check_in_date ? Carbon::parse($reservationRoom->check_in_date) : null;
                $checkOut = $reservationRoom?->check_out_date ? Carbon::parse($reservationRoom->check_out_date) : null;

                $nights = 0;
                if ($checkIn && $checkOut) {
                    $nights = max(1, $checkIn->diffInDays($checkOut));
                }

                $pricePerNight = (float)($reservationRoom->price_per_night ?? 0);
                if ($pricePerNight === 0 && $room->current_reservation->total_amount && $nights > 0) {
                    $pricePerNight = (float)$room->current_reservation->total_amount / $nights;
                }
                if ($pricePerNight === 0 && $room->rates && $room->rates->isNotEmpty()) {
                    $pricePerNight = (float)($room->rates->sortBy('min_guests')->first()->price_per_night ?? 0);
                }
                if ($pricePerNight === 0) {
                    $pricePerNight = (float)($room->base_price_per_night ?? 0);
                }

                $paymentsTotal = (float)($room->current_reservation->payments?->sum('amount') ?? 0);

                // Nights consumed up to current date (inclusive of current night if within range)
                $nightsConsumed = 0;
                if ($checkIn && $checkOut && $this->date) {
                    if ($this->date->lt($checkIn)) {
                        $nightsConsumed = 0;
                    } elseif ($this->date->gte($checkOut)) {
                        $nightsConsumed = $nights;
                    } else {
                        $nightsConsumed = max(1, $checkIn->diffInDays($this->date->copy()->addDay()));
                    }
                }

                $expectedPaidUntilToday = $pricePerNight * $nightsConsumed;
                $room->is_night_paid = $expectedPaidUntilToday > 0
                    ? $paymentsTotal >= $expectedPaidUntilToday
                    : false;

                $totalStay = $pricePerNight * $nights;
                if ($totalStay <= 0 && $room->current_reservation->total_amount) {
                    $totalStay = (float)$room->current_reservation->total_amount;
                }

                // Prefer stored balance_due (source of truth) when present
                $storedBalance = $room->current_reservation->balance_due;

                $sales_debt = 0;
                if ($room->current_reservation->sales) {
                    $sales_debt = (float)$room->current_reservation->sales->where('is_paid', false)->sum('total');
                }
                $computedDebt = ($totalStay - $paymentsTotal) + $sales_debt;
                $room->total_debt = $storedBalance !== null ? (float)$storedBalance + $sales_debt : $computedDebt;
            } else {
                $room->total_debt = 0;
                $room->is_night_paid = true;
            }
            
            return $room;
        });

        // Aplicar filtro de estado si existe (después de enriquecer)
        if ($this->statusFilter) {
            $rooms->setCollection(
                $rooms->getCollection()->filter(function($room) {
                    return $room->display_status === $this->statusFilter;
                })
            );
        }

        return view('livewire.room-manager', [
            'daysInMonth' => $this->daysInMonth,
            'currentDate' => $this->currentDate,
            'rooms' => $rooms,
        ]);
    }
}
