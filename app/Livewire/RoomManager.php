<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Locked;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\VentilationType;
use App\Models\ReservationRoom;
use App\Models\Reservation;
use App\Models\Payment;
use App\Models\RoomReleaseHistory;
use App\Models\Stay;
use App\Enums\RoomDisplayStatus;
use App\Support\HotelTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
    public bool $assignGuestsModal = false;
    public bool $roomDailyHistoryModal = false;
    public bool $isReleasingRoom = false;
    public bool $editPricesModal = false;
    public bool $allGuestsModal = false;

    // Datos de modales
    public ?array $detailData = null;
    public ?array $rentForm = null;
    public ?array $assignGuestsForm = null;
    public ?array $roomDailyHistoryData = null;
    public ?array $editPricesForm = null;
    public ?array $allGuestsForm = null;
    
    // Computed properties para UX (no persistidos)
    public function getBalanceDueProperty()
    {
        if (!$this->rentForm) return 0;
        $total = (float)($this->rentForm['total'] ?? 0);
        $deposit = (float)($this->rentForm['deposit'] ?? 0);
        return max(0, $total - $deposit);
    }
    
    public function getPaymentStatusBadgeProperty()
    {
        if (!$this->rentForm) return ['text' => 'Sin datos', 'color' => 'gray'];
        
        $total = (float)($this->rentForm['total'] ?? 0);
        $deposit = (float)($this->rentForm['deposit'] ?? 0);
        
        if ($deposit >= $total && $total > 0) {
            return ['text' => 'Pagado', 'color' => 'emerald'];
        } elseif ($deposit > 0) {
            return ['text' => 'Pago parcial', 'color' => 'amber'];
        } else {
            return ['text' => 'Pendiente de pago', 'color' => 'red'];
        }
    }
    
    // Métodos para botones rápidos de pago
    public function setDepositFull()
    {
        if ($this->rentForm) {
            $this->rentForm['deposit'] = $this->rentForm['total'];
        }
    }
    
    public function setDepositHalf()
    {
        if ($this->rentForm) {
            $this->rentForm['deposit'] = round($this->rentForm['total'] / 2, 2);
        }
    }
    
    public function setDepositNone()
    {
        if ($this->rentForm) {
            $this->rentForm['deposit'] = 0;
        }
    }

    /**
     * Calcula el número total de huéspedes (principal + adicionales) con fallback a 1.
     */
    private function calculateGuestCount(): int
    {
        if (!$this->rentForm) {
            return 1;
        }

        $principal = !empty($this->rentForm['client_id']) ? 1 : 0;
        $additional = is_array($this->additionalGuests) ? count($this->additionalGuests) : 0;

        return max(1, $principal + $additional);
    }

    /**
     * Selecciona la tarifa adecuada según cantidad de huéspedes.
     * REGLA HOTELERA: Cada tarifa tiene un rango válido [min_guests, max_guests].
     * - Busca la primera tarifa cuyo rango contiene el número de huéspedes.
     * - max_guests debe ser > 0 (no existen rangos abiertos ambiguos en hotelería).
     * - Fallback al base_price_per_night si no hay tarifas o ninguna coincide.
     * 
     * @param Room $room Habitación con sus tarifas cargadas
     * @param int $guests Número de huéspedes
     * @return float Precio por noche válido (siempre > 0 si existe base_price)
     */
    private function findRateForGuests(Room $room, int $guests): float
    {
        // Validar entrada
        if ($guests <= 0) {
            \Log::warning('findRateForGuests: Invalid guests count', ['guests' => $guests, 'room_id' => $room->id]);
            return (float)($room->base_price_per_night ?? 0);
        }

        $rates = $room->rates;

        // Si hay tarifas configuradas, buscar coincidencia exacta
        if ($rates && $rates->isNotEmpty()) {
            foreach ($rates as $rate) {
                $min = (int)($rate->min_guests ?? 0);
                $max = (int)($rate->max_guests ?? 0);
                
                // Validar que min y max sean valores válidos
                if ($min <= 0 || $max <= 0) {
                    \Log::warning('findRateForGuests: Invalid rate range', [
                        'rate_id' => $rate->id,
                        'min_guests' => $rate->min_guests,
                        'max_guests' => $rate->max_guests,
                        'room_id' => $room->id,
                    ]);
                    continue; // Saltar tarifa inválida
                }
                
                // Coincidencia: guests está dentro del rango [min, max]
                if ($guests >= $min && $guests <= $max) {
                    $price = (float)($rate->price_per_night ?? 0);
                    if ($price > 0) {
                        \Log::info('findRateForGuests: Rate found', [
                            'room_id' => $room->id,
                            'guests' => $guests,
                            'rate_id' => $rate->id,
                            'min' => $min,
                            'max' => $max,
                            'price_per_night' => $price,
                        ]);
                        return $price;
                    }
                }
            }
            
            // No se encontró tarifa coincidente
            \Log::warning('findRateForGuests: No matching rate found', [
                'room_id' => $room->id,
                'guests' => $guests,
                'available_rates' => $rates->map(fn($r) => [
                    'id' => $r->id,
                    'min' => $r->min_guests,
                    'max' => $r->max_guests,
                    'price' => $r->price_per_night,
                ])->toArray(),
            ]);
        } else {
            \Log::warning('findRateForGuests: No rates configured', [
                'room_id' => $room->id,
                'guests' => $guests,
            ]);
        }

        // Fallback: usar base_price_per_night
        $basePrice = (float)($room->base_price_per_night ?? 0);
        if ($basePrice > 0) {
            \Log::info('findRateForGuests: Using base_price fallback', [
                'room_id' => $room->id,
                'guests' => $guests,
                'base_price_per_night' => $basePrice,
            ]);
            return $basePrice;
        }

        // Último recurso: precio por defecto 0 (será detectado por validación)
        \Log::error('findRateForGuests: No price found', [
            'room_id' => $room->id,
            'guests' => $guests,
            'has_rates' => $rates && $rates->isNotEmpty(),
            'base_price' => $room->base_price_per_night,
        ]);
        return 0.0;
    }

    /**
     * Garantiza que exista un registro de noche para una fecha específica en una estadía.
     * 
     * SINGLE SOURCE OF TRUTH para el cobro por noches:
     * - Si ya existe una noche para esa fecha, no hace nada
     * - Si no existe, crea una nueva noche con precio calculado desde tarifas
     * - El precio se calcula basándose en la cantidad de huéspedes de la reserva
     * 
     * REGLA: Cada noche que una habitación está ocupada debe tener un registro en stay_nights
     * 
     * @param \App\Models\Stay $stay La estadía activa
     * @param \Carbon\Carbon $date Fecha de la noche a crear
     * @return \App\Models\StayNight|null La noche creada o existente, o null si falla
     */
    private function ensureNightForDate(\App\Models\Stay $stay, Carbon $date): ?\App\Models\StayNight
    {
        try {
            // Verificar si ya existe una noche para esta fecha
            $existingNight = \App\Models\StayNight::where('stay_id', $stay->id)
                ->whereDate('date', $date->toDateString())
                ->first();

            if ($existingNight) {
                // Ya existe, retornar sin crear
                return $existingNight;
            }

            // Obtener reserva y habitación para calcular precio
            $reservation = $stay->reservation;
            $room = $stay->room;

            if (!$reservation || !$room) {
                \Log::error('ensureNightForDate: Missing reservation or room', [
                    'stay_id' => $stay->id,
                    'date' => $date->toDateString()
                ]);
                return null;
            }

            // Cargar asignación de habitación en la reserva (fuente contractual de noches/precio)
            $reservationRoom = $reservation->reservationRooms()
                ->where('room_id', $room->id)
                ->first();

            // Regla de consistencia: si la stay ya tiene noches, reutilizar el último precio.
            $lastNight = \App\Models\StayNight::where('stay_id', $stay->id)
                ->orderByDesc('date')
                ->first();

            $price = $lastNight && (float)($lastNight->price ?? 0) > 0
                ? (float)$lastNight->price
                : 0.0;

            if ($price <= 0) {
                // REGLA: para reservas, el precio por noche se deriva del contrato de reserva,
                // nunca de la tarifa base de habitación.
                if ($reservationRoom) {
                    $reservationRoomPrice = (float)($reservationRoom->price_per_night ?? 0);
                    if ($reservationRoomPrice > 0) {
                        $price = $reservationRoomPrice;
                    } else {
                        $reservationRoomSubtotal = (float)($reservationRoom->subtotal ?? 0);
                        $reservationRoomNights = (int)($reservationRoom->nights ?? 0);

                        if ($reservationRoomNights <= 0 && !empty($reservationRoom->check_in_date) && !empty($reservationRoom->check_out_date)) {
                            $checkInDate = Carbon::parse($reservationRoom->check_in_date);
                            $checkOutDate = Carbon::parse($reservationRoom->check_out_date);
                            $reservationRoomNights = max(1, $checkInDate->diffInDays($checkOutDate));
                        }

                        if ($reservationRoomSubtotal > 0 && $reservationRoomNights > 0) {
                            $price = round($reservationRoomSubtotal / $reservationRoomNights, 2);
                        }
                    }
                }
            }

            if ($price <= 0) {
                // Fallback contractual: total de reserva dividido por noches totales configuradas.
                $totalAmount = (float)($reservation->total_amount ?? 0);
                $totalNights = (int)max(0, $reservation->reservationRooms()->sum('nights'));

                if ($totalAmount > 0 && $totalNights > 0) {
                    $price = round($totalAmount / $totalNights, 2);
                }
            }

            if ($price <= 0 && $reservationRoom) {
                $reservationRoomPrice = (float)($reservationRoom->price_per_night ?? 0);
                if ($reservationRoomPrice > 0) {
                    $price = $reservationRoomPrice;
                } else {
                    $reservationRoomSubtotal = (float)($reservationRoom->subtotal ?? 0);
                    $reservationRoomNights = (int)($reservationRoom->nights ?? 0);

                    if ($reservationRoomNights <= 0 && !empty($reservationRoom->check_in_date) && !empty($reservationRoom->check_out_date)) {
                        $checkInDate = Carbon::parse($reservationRoom->check_in_date);
                        $checkOutDate = Carbon::parse($reservationRoom->check_out_date);
                        $reservationRoomNights = max(1, $checkInDate->diffInDays($checkOutDate));
                    }

                    if ($reservationRoomSubtotal > 0 && $reservationRoomNights > 0) {
                        $price = round($reservationRoomSubtotal / $reservationRoomNights, 2);
                    }
                }
            }

            // Crear la noche
            $stayNight = \App\Models\StayNight::create([
                'stay_id' => $stay->id,
                'reservation_id' => $reservation->id,
                'room_id' => $room->id,
                'date' => $date->toDateString(),
                'price' => $price,
                'is_paid' => false, // Por defecto, pendiente
            ]);

            \Log::info('ensureNightForDate: Night created', [
                'stay_id' => $stay->id,
                'reservation_id' => $reservation->id,
                'room_id' => $room->id,
                'date' => $date->toDateString(),
                'price' => $price,
                'contract_total' => (float)($reservation->total_amount ?? 0)
            ]);

            return $stayNight;
        } catch (\Exception $e) {
            \Log::error('ensureNightForDate: Error creating night', [
                'stay_id' => $stay->id,
                'date' => $date->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Recalcula total, noches y guests_count cuando cambia personas o fechas.
     */
    private function recalculateQuickRentTotals(?Room $room = null): void
    {
        if (!$this->rentForm) {
            return;
        }

        $roomModel = $room ?? Room::with('rates')->find($this->rentForm['room_id'] ?? null);
        if (!$roomModel) {
            return;
        }

        $guests = $this->calculateGuestCount();

        $checkIn = Carbon::parse($this->rentForm['check_in_date'] ?? $this->date->toDateString());
        $checkOut = Carbon::parse($this->rentForm['check_out_date'] ?? $this->date->copy()->addDay()->toDateString());
        $nights = max(1, $checkIn->diffInDays($checkOut));

        $pricePerNight = $this->findRateForGuests($roomModel, $guests);
        $total = $pricePerNight * $nights;

        $this->rentForm['guests_count'] = $guests;
        $this->rentForm['total'] = $total;
    }

    /**
     * Obtiene el ID del método de pago por código en payments_methods.
     */
    private function getPaymentMethodId(string $code): ?int
    {
        return DB::table('payments_methods')
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->value('id');
    }

    /**
     * Detecta si un pago negativo corresponde a una reversión técnica
     * y no a una devolución real al cliente.
     */
    private function isPaymentReversalEntry(Payment $payment): bool
    {
        if ((float)($payment->amount ?? 0) >= 0) {
            return false;
        }

        return $this->extractReversedPaymentIdFromReference((string)($payment->reference ?? '')) !== null;
    }

    /**
     * Extrae el ID del pago original desde una referencia de reversión.
     * Soporta formatos históricos: "Anulacion de pago #123" y "reversal_of:123".
     */
    private function extractReversedPaymentIdFromReference(?string $reference): ?int
    {
        if (!$reference) {
            return null;
        }

        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        $normalizedReference = Str::lower(Str::ascii($reference));

        if (preg_match('/anulacion\s+de\s+pago\s*#\s*(\d+)/i', $normalizedReference, $matches)) {
            return (int)($matches[1] ?? 0) ?: null;
        }

        if (preg_match('/reversal_of\s*:\s*(\d+)/i', $normalizedReference, $matches)) {
            return (int)($matches[1] ?? 0) ?: null;
        }

        return null;
    }

    /**
     * Separa pagos válidos y devoluciones reales para el detalle de habitación.
     * - Excluye pagos positivos que luego fueron revertidos.
     * - Excluye reversión técnica del historial de devoluciones.
     *
     * @return array{valid_deposits:\Illuminate\Support\Collection, refunds:\Illuminate\Support\Collection}
     */
    private function splitPaymentsForRoomDetail($payments): array
    {
        $paymentsCollection = collect($payments);

        $reversedPaymentIds = $paymentsCollection
            ->filter(fn($payment) => $this->isPaymentReversalEntry($payment))
            ->map(fn($payment) => $this->extractReversedPaymentIdFromReference((string)($payment->reference ?? '')))
            ->filter(fn($id) => !empty($id))
            ->map(fn($id) => (int)$id)
            ->unique()
            ->values();

        $validDeposits = $paymentsCollection
            ->filter(function ($payment) use ($reversedPaymentIds) {
                $amount = (float)($payment->amount ?? 0);
                if ($amount <= 0) {
                    return false;
                }

                return !$reversedPaymentIds->contains((int)($payment->id ?? 0));
            })
            ->values();

        $refunds = $paymentsCollection
            ->filter(function ($payment) {
                $amount = (float)($payment->amount ?? 0);
                if ($amount >= 0) {
                    return false;
                }

                return !$this->isPaymentReversalEntry($payment);
            })
            ->values();

        return [
            'valid_deposits' => $validDeposits,
            'refunds' => $refunds,
        ];
    }

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

    protected $listeners = [
        'room-created' => '$refresh',
        'room-updated' => '$refresh',
        'refreshRooms' => 'loadRooms',
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

        // El historial se carga en render() cuando se necesita
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

    /**
     * Obtiene el historial de liberación paginado.
     * Se calcula en render() para evitar problemas de serialización en Livewire.
     */
    protected function getReleaseHistory()
    {
        // Verificar si la tabla existe antes de intentar consultarla
        if (!Schema::hasTable('room_release_history')) {
            // Si la tabla no existe, retornar una colección vacía paginada
            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                15,
                1,
                ['path' => request()->url(), 'pageName' => 'releaseHistoryPage']
            );
        }
        
        try {
            // Cargar historial de liberación de habitaciones filtrado por fecha
            $query = RoomReleaseHistory::query()
                ->with(['room', 'customer', 'releasedBy'])
                ->orderBy('release_date', 'desc')
                ->orderBy('created_at', 'desc');
            
            // Filtrar por fecha: mostrar liberaciones del mes actual
            // Si hay fecha seleccionada, filtrar por ese mes
            if ($this->currentDate) {
                $startOfMonth = $this->currentDate->copy()->startOfMonth();
                $endOfMonth = $this->currentDate->copy()->endOfMonth();
                
                $query->whereBetween('release_date', [
                    $startOfMonth->toDateString(),
                    $endOfMonth->toDateString()
                ]);
            }
            // Si no hay fecha seleccionada, mostrar TODAS las liberaciones (sin filtro de fecha)
            
            // Aplicar búsqueda si existe
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('room_number', 'like', '%' . $this->search . '%')
                      ->orWhere('customer_name', 'like', '%' . $this->search . '%')
                      ->orWhere('customer_identification', 'like', '%' . $this->search . '%');
                });
            }
            
            // Paginar resultados
            $paginator = $query->paginate(15, pageName: 'releaseHistoryPage');
            
            // Log para debugging
            \Log::info('Release history query executed', [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'activeTab' => $this->activeTab,
                'currentDate' => $this->currentDate?->toDateString(),
                'search' => $this->search,
            ]);
            
            return $paginator;
        } catch (\Exception $e) {
            \Log::error('Error loading release history', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // En caso de error, retornar una colección vacía paginada
            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                15,
                1,
                ['path' => request()->url(), 'pageName' => 'releaseHistoryPage']
            );
        }
    }
    
    /**
     * @deprecated Usar getReleaseHistory() en render() en su lugar
     */
    public function loadReleaseHistory()
    {
        // Este método ya no es necesario, pero se mantiene para compatibilidad
        // El historial se carga directamente en render()
    }

    /**
     * Carga huéspedes de la reserva activa de una habitación.
     * 
     * SINGLE SOURCE OF TRUTH:
     * - Cliente principal: SIEMPRE viene de $reservation->customer (reservations.client_id)
     * - Huéspedes adicionales: SIEMPRE vienen de $reservationRoom->getGuests()
     *   que usa: reservation_room_guests → reservation_guest_id → reservation_guests.guest_id → customers.id
     * 
     * Usa STAY (ocupación real con timestamps) en lugar de ReservationRoom (fechas).
     */
    /**
     * Resumen diario para vision general del hotel.
     */
    protected function getDailyOverviewStats(): array
    {
        $selectedDate = $this->date instanceof Carbon
            ? $this->date->copy()
            : Carbon::parse($this->date ?? now());

        $dateString = $selectedDate->toDateString();
        $dayStart = $selectedDate->copy()->startOfDay();
        $dayEnd = $selectedDate->copy()->endOfDay();

        $activeRoomsQuery = Room::query();
        if (Schema::hasColumn('rooms', 'is_active')) {
            $activeRoomsQuery->where('is_active', true);
        }

        $roomsTotal = (clone $activeRoomsQuery)->count();
        if ($roomsTotal === 0) {
            $roomsTotal = Room::query()->count();
        }

        $occupiedStaysQuery = Stay::query()
            ->whereIn('status', ['active', 'pending_checkout'])
            ->where('check_in_at', '<=', $dayEnd)
            ->where(function ($query) use ($dayStart) {
                $query->whereNull('check_out_at')
                    ->orWhere('check_out_at', '>', $dayStart);
            });

        $roomsOccupied = (clone $occupiedStaysQuery)
            ->distinct()
            ->count('room_id');

        $occupiedReservationIds = (clone $occupiedStaysQuery)
            ->whereNotNull('reservation_id')
            ->distinct()
            ->pluck('reservation_id');

        $activeReservationIds = ReservationRoom::query()
            ->join('reservations', 'reservations.id', '=', 'reservation_rooms.reservation_id')
            ->whereNull('reservations.deleted_at')
            ->whereDate('reservation_rooms.check_in_date', '<=', $dateString)
            ->whereDate('reservation_rooms.check_out_date', '>=', $dateString)
            ->distinct()
            ->pluck('reservation_rooms.reservation_id');

        $reservationsActive = $activeReservationIds->count();

        $guestsTotal = 0;
        $adultsTotal = 0;
        $childrenTotal = 0;

        if ($occupiedReservationIds->isNotEmpty()) {
            $reservationGuestsSummary = Reservation::query()
                ->whereIn('id', $occupiedReservationIds)
                ->selectRaw('
                    COALESCE(SUM(total_guests), 0) as total_guests_sum,
                    COALESCE(SUM(adults), 0) as adults_sum,
                    COALESCE(SUM(children), 0) as children_sum
                ')
                ->first();

            $guestsTotal = (int) ($reservationGuestsSummary->total_guests_sum ?? 0);
            $adultsTotal = (int) ($reservationGuestsSummary->adults_sum ?? 0);
            $childrenTotal = (int) ($reservationGuestsSummary->children_sum ?? 0);
        }

        $arrivalsToday = Stay::query()
            ->whereNotNull('reservation_id')
            ->whereNotNull('check_in_at')
            ->whereDate('check_in_at', $dateString)
            ->distinct()
            ->count('reservation_id');

        $departuresToday = Stay::query()
            ->whereNotNull('reservation_id')
            ->whereNotNull('check_out_at')
            ->where('status', 'finished')
            ->whereDate('check_out_at', $dateString)
            ->distinct()
            ->count('reservation_id');

        $roomsAvailable = max(0, $roomsTotal - $roomsOccupied);
        $occupancyRate = $roomsTotal > 0
            ? (int) round(($roomsOccupied / $roomsTotal) * 100)
            : 0;

        return [
            'date' => $dateString,
            'rooms_total' => $roomsTotal,
            'rooms_occupied' => $roomsOccupied,
            'rooms_available' => $roomsAvailable,
            'occupancy_rate' => $occupancyRate,
            'reservations_active' => $reservationsActive,
            'arrivals_today' => $arrivalsToday,
            'departures_today' => $departuresToday,
            'guests_total' => $guestsTotal,
            'adults_total' => $adultsTotal,
            'children_total' => $childrenTotal,
        ];
    }

    /**
     * Obtiene resumen de llegadas para una fecha (reservas con check_in en ese dia).
     *
     * @return array{count:int,items:array<int,array<string,mixed>>}
     */
    private function formatReservationRoomsForSummary(Reservation $reservation): string
    {
        if (!$reservation->relationLoaded('reservationRooms') || $reservation->reservationRooms->isEmpty()) {
            return 'Sin habitaciones asignadas';
        }

        $roomNumbers = $reservation->reservationRooms
            ->map(static fn ($reservationRoom) => $reservationRoom->room?->room_number)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return empty($roomNumbers) ? 'Sin habitaciones asignadas' : implode(', ', $roomNumbers);
    }

    /**
     * Estado operacional para tarjetas de resumen de reservas.
     *
     * checked_in: ya tuvo check-in registrado.
     * pending_checkin: aun no registra check-in.
     * cancelled: reserva cancelada (soft delete).
     *
     * @return array{key:string,label:string,badge:string}
     */
    private function resolveReservationStatusForSummary(Reservation $reservation): array
    {
        $isCancelled = method_exists($reservation, 'trashed') && $reservation->trashed();
        if ($isCancelled) {
            return [
                'key' => 'cancelled',
                'label' => 'Cancelada',
                'badge' => 'bg-red-100 text-red-700 border border-red-200',
            ];
        }

        $operationalStayStatuses = ['active', 'pending_checkout', 'finished'];
        $hasCheckIn = false;

        if ($reservation->relationLoaded('stays')) {
            $hasCheckIn = $reservation->stays->contains(
                static fn ($stay) => !empty($stay->check_in_at)
                    || in_array((string) ($stay->status ?? ''), $operationalStayStatuses, true),
            );
        } else {
            $hasCheckIn = $reservation->stays()
                ->where(function ($query) use ($operationalStayStatuses) {
                    $query->whereNotNull('check_in_at')
                        ->orWhereIn('status', $operationalStayStatuses);
                })
                ->exists();
        }

        if ($hasCheckIn) {
            return [
                'key' => 'checked_in',
                'label' => 'Check-in realizado',
                'badge' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
            ];
        }

        return [
            'key' => 'pending_checkin',
            'label' => 'Pendiente check-in',
            'badge' => 'bg-amber-100 text-amber-800 border border-amber-200',
        ];
    }

    private function buildReservationModalPayloadForSummary(
        Reservation $reservation,
        bool $canDoCheckIn,
        bool $canDoPayments
    ): array {
        $reservationRooms = $reservation->relationLoaded('reservationRooms')
            ? $reservation->reservationRooms
            : collect();

        $firstReservationRoom = $reservationRooms->first();
        $checkInDate = $reservationRooms
            ->pluck('check_in_date')
            ->filter()
            ->sort()
            ->first() ?? $firstReservationRoom?->check_in_date;
        $checkOutDate = $reservationRooms
            ->pluck('check_out_date')
            ->filter()
            ->sortDesc()
            ->first() ?? $firstReservationRoom?->check_out_date;

        $statusMeta = $this->resolveReservationStatusForSummary($reservation);
        $isCancelled = $statusMeta['key'] === 'cancelled';
        $hasOperationalStay = $statusMeta['key'] === 'checked_in';

        $payments = $reservation->relationLoaded('payments') ? $reservation->payments : collect();
        $paymentsTotalRaw = $payments->isNotEmpty()
            ? (float) $payments->sum('amount')
            : (float) ($reservation->deposit_amount ?? 0);

        $stayNightsTotalRaw = (float) ($reservation->stay_nights_total ?? 0);
        $reservationRoomsTotalRaw = (float) $reservationRooms->sum(
            static fn ($reservationRoom) => (float) ($reservationRoom->subtotal ?? 0),
        );
        $enteredReservationTotalRaw = (float) ($reservation->total_amount ?? 0);
        $totalAmountRaw = $enteredReservationTotalRaw > 0
            ? $enteredReservationTotalRaw
            : max(0, $reservationRoomsTotalRaw, $stayNightsTotalRaw);
        $balanceRaw = max(0, $totalAmountRaw - $paymentsTotalRaw);

        $latestPositivePayment = $payments
            ->where('amount', '>', 0)
            ->sortByDesc('id')
            ->first(static function ($candidatePayment) use ($payments) {
                $candidateId = (int) ($candidatePayment->id ?? 0);
                if ($candidateId <= 0) {
                    return false;
                }

                $reversalReference = 'Anulacion de pago #' . $candidateId;

                return !$payments->contains(
                    static fn ($existingPayment) => (float) ($existingPayment->amount ?? 0) < 0
                        && (string) ($existingPayment->reference ?? '') === $reversalReference,
                );
            });

        $customer = $reservation->customer;
        $customerName = (string) ($customer?->name ?? 'Sin cliente asignado');
        $customerIdentificationValue = $customer?->identification_number ?: ($customer?->taxProfile?->identification ?? null);
        $customerIdentificationType = $customer?->identificationType?->name;
        $customerIdentification = $customerIdentificationValue
            ? (string) ($customerIdentificationType
                ? ($customerIdentificationType . ': ' . $customerIdentificationValue)
                : $customerIdentificationValue)
            : '-';

        $canCheckIn = !$isCancelled && $canDoCheckIn && !$hasOperationalStay;
        $canPay = !$isCancelled && $canDoPayments;

        $statusLabel = $statusMeta['label'];

        return [
            'id' => (int) $reservation->id,
            'reservation_code' => (string) ($reservation->reservation_code ?? ''),
            'customer_name' => $customerName,
            'customer_identification' => $customerIdentification,
            'customer_phone' => (string) ($customer?->phone ?? '-'),
            'rooms' => $this->formatReservationRoomsForSummary($reservation),
            'check_in' => $checkInDate ? Carbon::parse($checkInDate)->format('d/m/Y') : 'N/A',
            'check_out' => $checkOutDate ? Carbon::parse($checkOutDate)->format('d/m/Y') : 'N/A',
            'check_in_time' => $reservation->check_in_time ? substr((string) $reservation->check_in_time, 0, 5) : 'N/A',
            'guests_count' => (int) ($reservation->total_guests ?? 0),
            'total' => number_format($totalAmountRaw, 0, ',', '.'),
            'deposit' => number_format($paymentsTotalRaw, 0, ',', '.'),
            'balance' => number_format($balanceRaw, 0, ',', '.'),
            'total_amount_raw' => $totalAmountRaw,
            'payments_total_raw' => $paymentsTotalRaw,
            'balance_raw' => $balanceRaw,
            'edit_url' => $isCancelled ? null : route('reservations.edit', $reservation),
            'check_in_url' => $canCheckIn ? route('reservations.check-in', $reservation) : null,
            'payment_url' => $canPay ? route('reservations.register-payment', $reservation) : null,
            'cancel_payment_url' => null,
            'pdf_url' => $isCancelled ? null : route('reservations.download', $reservation),
            'guests_document_view_url' => !$isCancelled && !empty($reservation->guests_document_path)
                ? route('reservations.guest-document.view', $reservation)
                : null,
            'guests_document_download_url' => !$isCancelled && !empty($reservation->guests_document_path)
                ? route('reservations.guest-document.download', $reservation)
                : null,
            'notes' => $reservation->notes ?? 'Sin notas adicionales',
            'status' => $statusLabel,
            'status_key' => $statusMeta['key'],
            'status_badge_class' => $statusMeta['badge'],
            'has_operational_stay' => $hasOperationalStay,
            'can_cancel' => false,
            'can_checkin' => $canCheckIn,
            'can_pay' => $canPay,
            'can_cancel_payment' => false,
            'last_payment_amount' => $latestPositivePayment ? (float) ($latestPositivePayment->amount ?? 0) : 0.0,
            'cancelled_at' => $isCancelled && $reservation->deleted_at
                ? $reservation->deleted_at->format('d/m/Y H:i')
                : null,
        ];
    }

    protected function getArrivalsSummaryForDate(Carbon $date, int $limit = 6): array
    {
        $dateString = $date->toDateString();
        $canDoCheckIn = auth()->check() && auth()->user()->can('edit_reservations');
        $canDoPayments = auth()->check() && auth()->user()->can('edit_reservations');

        $reservations = Reservation::withTrashed()
            ->whereHas('reservationRooms', function ($query) use ($dateString) {
                $query->whereDate('check_in_date', $dateString);
            })
            ->with([
                'customer:id,name,phone,identification_number,identification_type_id',
                'customer.identificationType:id,name',
                'customer.taxProfile:id,customer_id,identification',
                'payments:id,reservation_id,amount,reference',
                'stays:id,reservation_id,status',
                'reservationRooms' => function ($query) use ($dateString) {
                    $query->whereDate('check_in_date', $dateString)
                        ->with('room:id,room_number');
                },
            ])
            ->orderBy('id')
            ->get();

        $statusCounts = [
            'checked_in' => 0,
            'pending_checkin' => 0,
            'cancelled' => 0,
        ];

        foreach ($reservations as $reservation) {
            $statusKey = $this->resolveReservationStatusForSummary($reservation)['key'];
            if (array_key_exists($statusKey, $statusCounts)) {
                $statusCounts[$statusKey]++;
            }
        }

        $items = $reservations
            ->take(max(1, $limit))
            ->map(function (Reservation $reservation) use ($canDoCheckIn, $canDoPayments): array {
                $statusMeta = $this->resolveReservationStatusForSummary($reservation);

                return [
                    'id' => (int) $reservation->id,
                    'code' => (string) ($reservation->reservation_code ?: ('RES-' . $reservation->id)),
                    'customer' => (string) ($reservation->customer?->name ?? 'Sin cliente'),
                    'rooms' => $this->formatReservationRoomsForSummary($reservation),
                    'status_key' => $statusMeta['key'],
                    'status_label' => $statusMeta['label'],
                    'status_badge_class' => $statusMeta['badge'],
                    'modal_payload' => $this->buildReservationModalPayloadForSummary(
                        $reservation,
                        $canDoCheckIn,
                        $canDoPayments,
                    ),
                ];
            })
            ->values()
            ->all();

        return [
            'count' => $reservations->count(),
            'status_counts' => $statusCounts,
            'items' => $items,
        ];
    }

    /**
     * Resumen rapido de reservas para recepcion: hoy y manana.
     */
    protected function getReceptionReservationsSummary(): array
    {
        $today = Carbon::today();
        $tomorrow = Carbon::today()->copy()->addDay();

        $todayData = $this->getArrivalsSummaryForDate($today);
        $tomorrowData = $this->getArrivalsSummaryForDate($tomorrow);

        return [
            'today_date' => $today->format('d/m/Y'),
            'tomorrow_date' => $tomorrow->format('d/m/Y'),
            'today_count' => (int) ($todayData['count'] ?? 0),
            'tomorrow_count' => (int) ($tomorrowData['count'] ?? 0),
            'today_status_counts' => $todayData['status_counts'] ?? [],
            'tomorrow_status_counts' => $tomorrowData['status_counts'] ?? [],
            'today_items' => $todayData['items'] ?? [],
            'tomorrow_items' => $tomorrowData['items'] ?? [],
        ];
    }

    public function loadRoomGuests($roomId)
    {
        try {
            // Cargar room con relaciones necesarias (eager loading optimizado)
            $room = Room::with([
                'stays.reservation.customer.taxProfile',
                'stays.reservation.reservationRooms' => function ($q) use ($roomId) {
                    $q->where('room_id', $roomId);
                }
            ])->find($roomId);

            if (!$room) {
                return [
                    'room_number' => null,
                    'guests' => [],
                    'main_guest' => null,
                ];
            }

            // Obtener la Stay que intersecta con la fecha consultada
            $stay = $room->getAvailabilityService()->getStayForDate($this->date ?? Carbon::today());

            // GUARD CLAUSE: Si no hay stay o reserva, retornar vacío
            if (!$stay || !$stay->reservation) {
                return [
                    'room_number' => $room->room_number,
                    'guests' => [],
                    'main_guest' => null,
                ];
            }

            $reservation = $stay->reservation;

            // 1. Huésped principal - SINGLE SOURCE OF TRUTH: reservations.client_id
            $mainGuest = null;
            if ($reservation->customer) {
                $mainGuest = [
                    'id' => $reservation->customer->id,
                    'name' => $reservation->customer->name,
                    'identification' => $reservation->customer->taxProfile?->identification ?? null,
                    'phone' => $reservation->customer->phone ?? null,
                    'email' => $reservation->customer->email ?? null,
                    'is_main' => true,
                ];
            }

            // 2. ReservationRoom DE ESTA HABITACIÓN ESPECÍFICA
            $reservationRoom = $reservation->reservationRooms->firstWhere('room_id', $room->id);

            // 3. Huéspedes adicionales - SINGLE SOURCE OF TRUTH: reservationRoom->getGuests()
            // Ruta: reservation_room_guests → reservation_guest_id → reservation_guests.guest_id → customers.id
            $additionalGuests = collect();
            if ($reservationRoom) {
                try {
                    $guestsCollection = $reservationRoom->getGuests();
                    
                    if ($guestsCollection && $guestsCollection->isNotEmpty()) {
                        $additionalGuests = $guestsCollection->map(function($guest) {
                            // Cargar taxProfile si no está cargado
                            if (!$guest->relationLoaded('taxProfile')) {
                                $guest->load('taxProfile');
                            }
                            
                            return [
                                'id' => $guest->id,
                                'name' => $guest->name,
                                'identification' => $guest->taxProfile?->identification ?? null,
                                'phone' => $guest->phone ?? null,
                                'email' => $guest->email ?? null,
                                'is_main' => false,
                            ];
                        });
                    }
                } catch (\Exception $e) {
                    // Si falla la carga de guests, retornar colección vacía sin romper el flujo
                    \Log::warning('Error loading additional guests in loadRoomGuests', [
                        'room_id' => $room->id,
                        'reservation_room_id' => $reservationRoom->id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                    $additionalGuests = collect();
                }
            }

            // 4. Combinar huésped principal y adicionales
            $guests = collect();
            if ($mainGuest) {
                $guests->push($mainGuest);
            }
            $guests = $guests->merge($additionalGuests);

            return [
                'room_number' => $room->room_number,
                'guests' => $guests->values()->toArray(),
                'main_guest' => $mainGuest,
            ];
        } catch (\Exception $e) {
            // Protección total: nunca lanzar excepciones
            \Log::error('Error in loadRoomGuests', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'room_number' => null,
                'guests' => [],
                'main_guest' => null,
            ];
        }
    }


    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        // El historial se carga automáticamente en render() cuando activeTab === 'history'
    }

    public function refreshRoomsPolling()
    {
        if ($this->isReleasingRoom) {
            return; // NO refrescar mientras se libera una habitación
        }
        // Livewire automatically re-renders, no need to manually load
    }

    /**
     * Forzar recarga de habitaciones desde BD tras eventos.
     */
    public function loadRooms()
    {
        $this->resetPage();
    }

    /**
     * Continúa la estadía (extiende el checkout por un día).
     * 
     * Reactiva la estadía extendiendo la fecha de checkout de la reserva.
     * Esto quita el estado de "pending_checkout" permitiendo que la habitación
     * siga ocupada un día más.
     * 
     * REGLAS DE NEGOCIO:
     * - Solo funciona para estadías que están en "pending_checkout" (check_out_date = hoy)
     * - Extiende reservation_rooms.check_out_date en 1 día
     * - NO toca pagos (el total_amount se mantiene, se pagará después)
     * - NO crea nueva estadía (la stay actual continúa)
     * - NO rompe auditoría (solo extiende fecha)
     * 
     * @param int $roomId ID de la habitación
     * @return void
     */
    public function continueStay(int $roomId): void
    {
        try {
            $room = Room::findOrFail($roomId);
            $availabilityService = $room->getAvailabilityService();
            $today = Carbon::today();

            // Validar que no sea fecha histórica
            if ($availabilityService->isHistoricDate($today)) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'No se pueden hacer cambios en fechas históricas.'
                ]);
                return;
            }

            // Obtener stay activa para hoy
            $stay = $availabilityService->getStayForDate($today);

            if (!$stay) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'No hay una estadía activa para continuar.'
                ]);
                return;
            }

            // Obtener reserva y reservation_room
            $reservation = $stay->reservation;
            if (!$reservation) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'La estadía no tiene reserva asociada.'
                ]);
                return;
            }

            $reservationRoom = $reservation->reservationRooms()
                ->where('room_id', $roomId)
                ->first();

            if (!$reservationRoom) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'No se encontró la relación reserva-habitación.'
                ]);
                return;
            }

            // Verificar que la fecha de checkout sea hoy (estado pending_checkout)
            $checkoutDate = Carbon::parse($reservationRoom->check_out_date);
            $today = HotelTime::endOfOperatingDay($today);
            
            if (!$checkoutDate->equalTo($today)) {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => 'La estadía no está en estado de checkout pendiente para continuar.'
                ]);
                return;
            }

            // Extender el checkout por un día
            $newCheckOutDate = $checkoutDate->copy()->addDay();

            // Actualizar reservation_room (solo la fecha, NO tocar pagos)
            $reservationRoom->update([
                'check_out_date' => $newCheckOutDate->toDateString()
            ]);

            // Asegurar que el stay esté activo (por si acaso tiene status incorrecto)
            if ($stay->status !== 'active') {
                $stay->update([
                    'status' => 'active',
                    'check_out_at' => null // Asegurar que siga activa
                ]);
            }

            // 🔥 GENERAR NOCHE PARA LA NOCHE REAL (crítico)
            // 🔐 REGLA HOTELERA: La noche cobrable es la ANTERIOR al nuevo checkout
            // Ejemplo: Checkout anterior 19, nuevo checkout 20 → Noche cobrable: 19 (NO 20)
            $nightToCharge = $newCheckOutDate->copy()->subDay();
            $this->ensureNightForDate($stay, $nightToCharge);

            // 🔐 REGLA HOTELERA: Continuar estadía = habitación queda pendiente por aseo
            // Toda extensión de estadía ensucia la habitación aunque el huésped continúe
            // Esto permite que el personal de limpieza inspeccione y prepare la habitación
            $room->update([
                'last_cleaned_at' => null // Marcar como pendiente de limpieza
            ]);

            // Refrescar vista
            $this->loadRooms();

            \Log::info('Continue stay executed', [
                'room_id' => $roomId,
                'stay_id' => $stay->id,
                'reservation_id' => $reservation->id,
                'old_checkout_date' => $checkoutDate->toDateString(),
                'new_checkout_date' => $newCheckOutDate->toDateString()
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "La estadía ha sido continuada hasta el {$newCheckOutDate->format('d/m/Y')}."
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in continueStay', [
                'room_id' => $roomId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error al continuar la estadía: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Marca una habitación como limpia actualizando last_cleaned_at.
     * Solo permitido cuando operational_status === 'pending_cleaning'.
     */
    public function markRoomAsClean($roomId)
    {
        try {
            $room = Room::find($roomId);
            if (!$room) {
                $this->dispatch('notify', type: 'error', message: 'Habitación no encontrada.');
                return;
            }

            // Validar que esté en pending_cleaning
            $operationalStatus = $room->getOperationalStatus($this->date ?? Carbon::today());
            if ($operationalStatus !== 'pending_cleaning') {
                $this->dispatch('notify', type: 'error', message: 'La habitación no requiere limpieza.');
                return;
            }

            $room->last_cleaned_at = now();
            $room->save();

            $this->dispatch('notify', type: 'success', message: 'Habitación marcada como limpia.');
            $this->dispatch('refreshRooms');
            
            // Notificar al frontend sobre el cambio de estado
            $this->dispatch('room-marked-clean', roomId: $room->id);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Error al marcar habitación: ' . $e->getMessage());
            \Log::error('Error marking room as clean: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
        
        // Si estamos en la pestaña de historial, resetear también esa página
        if ($this->activeTab === 'history') {
            $this->resetPage('releaseHistoryPage');
        }
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedVentilationTypeFilter()
    {
        $this->resetPage();
    }

    /**
     * CRITICAL: Todos los métodos de cambio de fecha deben:
     * 1. Actualizar $date y $currentDate
     * 2. Llamar loadRooms() para re-renderizar inmediatamente
     * 3. Disparar 'room-view-changed' para resetear Alpine.js
     * Esto evita estados heredados y delays visuales.
     */
    public function goToDate($date)
    {
        $this->date = Carbon::parse($date);
        $this->currentDate = $this->date;

        // CRITICAL: Forzar actualización inmediata
        $this->loadRooms();
        $this->dispatch('room-view-changed', date: $this->date->toDateString());
    }

    public function nextDay()
    {
        $this->date = $this->date->copy()->addDay();
        $this->currentDate = $this->date;

        // 🔥 GENERAR NOCHE PARA FECHA ACTUAL si hay stay activa
        // 🔐 PROTECCIÓN: Solo generar noches para HOY, nunca para fechas futuras
        // 🔐 PROTECCIÓN EXTRA: NO generar noche si HOY es checkout o después
        try {
            $today = Carbon::today();
            
            // Protección explícita: NO generar noches futuras
            if ($this->date->isAfter($today)) {
                // Fecha futura: NO generar noches aquí
                return;
            }
            
            // Solo generar noche si la fecha nueva es HOY
            if ($this->date->equalTo($today)) {
                // Obtener todas las habitaciones con stay activa para hoy
                $endOfToday = HotelTime::endOfOperatingDay($today);
                $startOfToday = $today->copy()->startOfDay();
                
                $activeStays = \App\Models\Stay::where('check_in_at', '<=', $endOfToday)
                    ->where(function($q) use ($startOfToday) {
                        $q->whereNull('check_out_at')
                          ->where('check_out_at', '>=', $startOfToday);
                    })
                    ->where('status', 'active')
                    ->with(['reservation.reservationRooms', 'room'])
                    ->get();

                foreach ($activeStays as $stay) {
                    // 🔐 PROTECCIÓN CRÍTICA: NO generar noche si HOY es checkout o después
                    $reservationRoom = $stay->reservation->reservationRooms->first();
                    if ($reservationRoom && $reservationRoom->check_out_date) {
                        $checkout = Carbon::parse($reservationRoom->check_out_date);
                        
                        // Si HOY es >= checkout, NO generar noche (la noche del checkout NO se cobra)
                        if ($today->gte($checkout)) {
                            continue; // Saltar esta stay
                        }
                    }
                    
                    // Generar noche solo si HOY < checkout
                    $this->ensureNightForDate($stay, $today);
                }
            }
        } catch (\Exception $e) {
            // No crítico, solo log
            \Log::warning('Error generating nights in nextDay', [
                'error' => $e->getMessage()
            ]);
        }

        // CRITICAL: Forzar actualización inmediata
        $this->loadRooms();
        $this->dispatch('room-view-changed', date: $this->date->toDateString());
    }

    public function previousDay()
    {
        $this->date = $this->date->copy()->subDay();
        $this->currentDate = $this->date;

        // CRITICAL: Forzar actualización inmediata
        $this->loadRooms();
        $this->dispatch('room-view-changed', date: $this->date->toDateString());
    }

    /**
     * Cambia la fecha actual y regenera el arreglo de días del mes para los filtros.
     * 
     * CRITICAL: Fuerza recarga inmediata de habitaciones para evitar estados heredados.
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

        // CRITICAL: Forzar actualización inmediata
        $this->loadRooms();
        $this->dispatch('room-view-changed', date: $this->date->toDateString());
    }

    public function goToToday()
    {
        $this->date = now();
        $this->currentDate = $this->date;
        
        // CRITICAL: Forzar actualización inmediata
        $this->loadRooms();
        $this->dispatch('room-view-changed', date: $this->date->toDateString());
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

        // Obtener información de acceso: si es fecha histórica, bloquear
        $availabilityService = $room->getAvailabilityService();
        $accessInfo = $availabilityService->getAccessInfo($this->date);

        if ($accessInfo['isHistoric']) {
            $this->dispatch('notify', type: 'warning', message: 'Información histórica: datos en solo lectura. No se permite modificar.');
        }

        // 🔥 CRITICAL FIX: Check if room is actually occupied on this date
        // Don't show details if the room has been released (stay is finished)
        // We need to check for ACTIVE stays, not just any stay
        $stay = $availabilityService->getStayForDate($this->date);
        
        // Debug logging
        \Log::info('openRoomDetail - Debug', [
            'room_id' => $roomId,
            'date' => $this->date->toDateString(),
            'stay_found' => $stay ? true : false,
            'stay_status' => $stay ? $stay->status : 'null',
            'stay_id' => $stay ? $stay->id : null,
        ]);
        
        $isActuallyOccupied = $stay && in_array($stay->status, ['active', 'pending_checkout']);
        
        if (!$isActuallyOccupied) {
            // Room is not occupied (stay is finished or doesn't exist), show empty state
            \Log::info('openRoomDetail - Room not occupied, showing empty state', [
                'room_id' => $roomId,
                'reason' => $stay ? "Stay status: {$stay->status}" : "No stay found"
            ]);
            
            $this->detailData = [
                'room' => [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                ],
                'reservation' => null,
                'sales' => [],
                'total_hospedaje' => 0,
                'abono_realizado' => 0,
                'sales_total' => 0,
                'total_debt' => 0,
                'stay_history' => [],
                'deposit_history' => [],
                'refunds_history' => [],
                'total_refunds' => 0,
                'is_past_date' => $this->date->lt(now()->startOfDay()),
                'isHistoric' => $accessInfo['isHistoric'],
                'canModify' => $accessInfo['canModify'],
            ];
            $this->roomDetailModal = true;
            return;
        }

        $activeReservation = $room->getActiveReservation($this->date);
        $sales = collect();
        $payments = collect();
        $totalHospedaje = 0;
        $abonoRealizado = 0;
        $refundsTotal = 0;
        $salesTotal = 0;
        $totalDebt = 0;
        $identification = null;
        $stayHistory = [];
        $validDepositPayments = collect();
        $trueRefundPayments = collect();
        $roomShareRatio = 1.0;

        if ($activeReservation) {
            $reservationRoom = $room->reservationRooms
                ->firstWhere('reservation_id', $activeReservation->id);
            // 🔥 GENERAR NOCHES FALTANTES para todo el rango de la estadía
            try {
                $stay = $availabilityService->getStayForDate($this->date);
                if ($stay) {
                    $reservationRoom = $reservationRoom ?? $room->reservationRooms->first();
                    if ($reservationRoom) {
                        $checkIn = Carbon::parse($reservationRoom->check_in_date);
                        $checkOut = Carbon::parse($reservationRoom->check_out_date);
                        
                        // 🔐 REGLA HOTELERA: La noche del check-out NO se cobra
                        // Generar noches para todo el rango desde check-in hasta check-out (exclusivo)
                        // Ejemplo: Check-in 18, Check-out 20 → Noches: 18 y 19 (NO 20)
                        $currentDate = $checkIn->copy();
                        while ($currentDate->lt($checkOut)) {
                            $this->ensureNightForDate($stay, $currentDate);
                            $currentDate->addDay();
                        }
                    }
                }
            } catch (\Exception $e) {
                // No crítico, solo log
                \Log::warning('Error generating nights in openRoomDetail', [
                    'room_id' => $roomId,
                    'error' => $e->getMessage()
                ]);
            }
            $sales = $activeReservation->sales ?? collect();
            $payments = $activeReservation->payments ?? collect();

            // ===== SSOT FINANCIERO: Separar pagos y devoluciones =====
            // REGLA CRÍTICA: payments.amount > 0 = dinero recibido (pagos)
            // payments.amount < 0 = dinero devuelto (devoluciones)
            // NO mezclar en sum(amount) porque se cancelan incorrectamente
            $paymentBuckets = $this->splitPaymentsForRoomDetail($payments);
            $validDepositPayments = $paymentBuckets['valid_deposits'] ?? collect();
            $trueRefundPayments = $paymentBuckets['refunds'] ?? collect();

            $validDepositsTotal = (float)($validDepositPayments->sum('amount') ?? 0);
            $trueRefundsTotal = abs((float)($trueRefundPayments->sum('amount') ?? 0));
            $reservationTotalHospedaje = 0.0;

            // ===== SSOT ABSOLUTO DEL HOSPEDAJE: stay_nights (NUEVO) =====
            // REGLA CRÍTICA: El total del hospedaje se calcula sumando todas las noches reales desde stay_nights
            // Esto permite rastrear cada noche individualmente y su estado de pago
            try {
                // Intentar usar stay_nights (si existe)
                $stayNights = \App\Models\StayNight::where('reservation_id', $activeReservation->id)
                    ->where('room_id', $room->id)
                    ->orderBy('date')
                    ->get();

                if ($stayNights->isNotEmpty()) {
                    // ✅ NUEVO SSOT: Calcular desde stay_nights
                    $totalHospedaje = (float)$stayNights->sum('price');
                    
                    // ✅ NUEVO SSOT: Leer stay_history desde stay_nights
                    $stayHistory = $stayNights->map(function($night) {
                        return [
                            'date' => $night->date->format('Y-m-d'),
                            'price' => (float)$night->price,
                            'is_paid' => (bool)$night->is_paid,
                        ];
                    })->toArray();
                } else {
                    // FALLBACK: Si no hay stay_nights aún, usar total_amount (compatibilidad)
                    $totalHospedaje = 0;
                    
                    $reservationRoom = $reservationRoom ?? $room->reservationRooms->first();
                    if ($reservationRoom) {
                        $checkIn = Carbon::parse($reservationRoom->check_in_date);
                        $checkOut = Carbon::parse($reservationRoom->check_out_date);
                        $nights = max(1, $checkIn->diffInDays($checkOut));
                        $pricePerNight = (float)($reservationRoom->price_per_night ?? 0);
                        if ($pricePerNight <= 0) {
                            $subtotal = (float)($reservationRoom->subtotal ?? 0);
                            if ($subtotal > 0 && $nights > 0) {
                                $pricePerNight = round($subtotal / $nights, 2);
                            }
                        }
                        $totalHospedaje = round($pricePerNight * $nights, 2);
                        
                        // Calcular stay_history desde fechas (fallback)
                        for ($i = 0; $i < $nights; $i++) {
                            $currentDate = $checkIn->copy()->addDays($i);
                            $stayHistory[] = [
                                'date' => $currentDate->format('Y-m-d'),
                                'price' => $pricePerNight,
                                'is_paid' => false, // Por defecto pendiente si no hay stay_nights
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                // Si falla (tabla no existe aún), usar fallback
                \Log::warning('Error reading stay_nights, using fallback', [
                    'reservation_id' => $activeReservation->id,
                    'error' => $e->getMessage()
                ]);
                
                if ($reservationRoom && !empty($reservationRoom->check_in_date) && !empty($reservationRoom->check_out_date)) {
                    $checkIn = Carbon::parse($reservationRoom->check_in_date);
                    $checkOut = Carbon::parse($reservationRoom->check_out_date);
                    $nights = max(1, $checkIn->diffInDays($checkOut));
                    $pricePerNight = (float)($reservationRoom->price_per_night ?? 0);
                    if ($pricePerNight <= 0) {
                        $subtotal = (float)($reservationRoom->subtotal ?? 0);
                        if ($subtotal > 0 && $nights > 0) {
                            $pricePerNight = round($subtotal / $nights, 2);
                        }
                    }
                    $totalHospedaje = round($pricePerNight * $nights, 2);
                } else {
                    $totalHospedaje = (float)($activeReservation->total_amount ?? 0);
                }
                $stayHistory = [];
            }

            // ===== VALIDACIÓN: Si totalHospedaje sigue siendo 0, algo está mal =====
            try {
                $reservationTotalHospedaje = (float)\App\Models\StayNight::where('reservation_id', $activeReservation->id)
                    ->sum('price');
            } catch (\Exception $e) {
                $reservationTotalHospedaje = 0.0;
            }

            if ($reservationTotalHospedaje <= 0) {
                $reservationTotalHospedaje = (float)($activeReservation->total_amount ?? 0);
            }

            $roomShareRatio = $reservationTotalHospedaje > 0
                ? max(0.0, min(1.0, $totalHospedaje / $reservationTotalHospedaje))
                : 1.0;

            $abonoRealizado = round($validDepositsTotal * $roomShareRatio, 2);
            $refundsTotal = round($trueRefundsTotal * $roomShareRatio, 2);

            if ($totalHospedaje == 0) {
                \Log::warning('openRoomDetail: totalHospedaje is 0', [
                    'reservation_id' => $activeReservation->id,
                    'reservation_total_amount' => $activeReservation->total_amount,
                    'room_id' => $room->id,
                ]);
            }

            // ===== CALCULAR CONSUMOS =====
            $salesTotal = (float)($sales->sum('total') ?? 0);
            $salesDebt = (float)($sales->where('is_paid', false)->sum('total') ?? 0);

            // ===== CALCULAR DEUDA TOTAL (CORRECTA CON PAGOS Y DEVOLUCIONES SEPARADOS) =====
            // Fórmula: deuda = (hospedaje - abonos_reales) + devoluciones + consumos_pendientes
            // Si hay devoluciones, se suman porque representan dinero que se devolvió
            $totalDebt = ($totalHospedaje - $abonoRealizado) + $refundsTotal + $salesDebt;

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
            'deposit_history' => $validDepositPayments->map(function($payment) use ($roomShareRatio) {
                return [
                    'id' => $payment->id,
                    'amount' => round(((float)($payment->amount ?? 0)) * $roomShareRatio, 2),
                    'payment_method' => $payment->paymentMethod->name ?? 'N/A',
                    'notes' => $payment->reference ?? null,
                    'created_at' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i') : null,
                ];
            })->filter(fn($row) => (float)($row['amount'] ?? 0) > 0)->values()->toArray(),
            'refunds_history' => $trueRefundPayments->map(function($payment) use ($roomShareRatio) {
                // Cargar createdBy si no está cargado
                if (!$payment->relationLoaded('createdBy')) {
                    $payment->load('createdBy');
                }
                
                return [
                    'id' => $payment->id,
                    'amount' => round(abs((float)($payment->amount ?? 0)) * $roomShareRatio, 2), // Valor absoluto para mostrar positivo en UI
                    'payment_method' => $payment->paymentMethod->name ?? 'N/A',
                    'bank_name' => $payment->bank_name ?? null,
                    'reference' => $payment->reference ?? null,
                    'created_by' => $payment->createdBy->name ?? 'N/A',
                    'created_at' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i') : null,
                ];
            })->filter(fn($row) => (float)($row['amount'] ?? 0) > 0)->values()->toArray(),
            'total_refunds' => $refundsTotal ?? 0, // Total de devoluciones para mostrar en el header del historial
            'is_past_date' => $this->date->lt(now()->startOfDay()), // Usar HotelTime sería mejor pero mantenemos consistencia con now() para validación de fecha actual
            'isHistoric' => $accessInfo['isHistoric'],
            'canModify' => $accessInfo['canModify'],
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
        // Validar que tenemos los datos necesarios
        if (!$this->newDeposit || !isset($this->newDeposit['amount']) || !isset($this->newDeposit['payment_method'])) {
            $this->dispatch('notify', type: 'error', message: 'Por favor complete todos los campos requeridos.');
            return;
        }

        // Validar que tenemos una reserva en el modal
        if (!$this->detailData || !isset($this->detailData['reservation']['id'])) {
            $this->dispatch('notify', type: 'error', message: 'No se encontró la reserva.');
            return;
        }

        $reservationId = $this->detailData['reservation']['id'];
        $amount = (float)($this->newDeposit['amount'] ?? 0);
        $paymentMethod = $this->newDeposit['payment_method'] ?? 'efectivo';
        $notes = $this->newDeposit['notes'] ?? null;

        // Si es transferencia, necesitamos bank_name y reference, pero por ahora los dejamos como null
        // ya que el formulario no los tiene
        $bankName = null;
        $reference = null;

        // Llamar a registerPayment con las notas
        $success = $this->registerPayment($reservationId, $amount, $paymentMethod, $bankName, $reference, $notes);

        // Limpiar el formulario y cerrar el panel solo si el pago fue exitoso
        if ($success) {
            $this->newDeposit = null;
            $this->showAddDeposit = false;
        }
    }


    /**
     * Registra un pago en la tabla payments (Single Source of Truth).
     * 
     * @param int $reservationId ID de la reserva
     * @param float $amount Monto del pago
     * @param string $paymentMethod Método de pago ('efectivo' o 'transferencia')
     * @param string|null $bankName Nombre del banco (solo si es transferencia)
     * @param string|null $reference Referencia de pago (solo si es transferencia)
     */
    /**
     * Obtiene el contexto financiero de una reserva para mostrar en el modal de pago
     */
    public function getFinancialContext($reservationId)
    {
        try {
            $reservation = Reservation::with(['payments', 'sales'])->find($reservationId);
            if (!$reservation) {
                return null;
            }

            $paymentsTotal = (float)($reservation->payments()->sum('amount') ?? 0);
            $salesDebt = (float)($reservation->sales?->where('is_paid', false)->sum('total') ?? 0);
            
            // ✅ NUEVO SSOT: Calcular desde stay_nights si existe
            try {
                $totalAmount = (float)\App\Models\StayNight::where('reservation_id', $reservationId)
                    ->sum('price');
                
                // Si no hay noches, usar fallback
                if ($totalAmount <= 0) {
                    $totalAmount = (float)($reservation->total_amount ?? 0);
                }
            } catch (\Exception $e) {
                // Si falla (tabla no existe), usar fallback
                $totalAmount = (float)($reservation->total_amount ?? 0);
            }
            
            $balanceDue = $totalAmount - $paymentsTotal + $salesDebt;

            return [
                'totalAmount' => $totalAmount,
                'paymentsTotal' => $paymentsTotal,
                'balanceDue' => max(0, $balanceDue),
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting financial context', [
                'reservation_id' => $reservationId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    #[On('register-payment')]
    public function handleRegisterPayment($reservationId, $amount, $paymentMethod, $bankName = null, $reference = null, $nightDate = null)
    {
        $this->registerPayment($reservationId, $amount, $paymentMethod, $bankName, $reference, null, $nightDate);
    }

    #[On('openAssignGuests')]
    public function handleOpenAssignGuests(...$params)
    {
        $roomId = $params[0] ?? null;
        if ($roomId) {
            $this->openAssignGuests($roomId);
        }
    }

    #[On('openEditPrices')]
    public function handleOpenEditPrices(...$params)
    {
        $reservationId = $params[0] ?? null;
        if ($reservationId) {
            $this->openEditPrices($reservationId);
        }
    }

    #[On('showAllGuests')]
    public function handleShowAllGuests(...$params)
    {
        $reservationId = $params[0] ?? null;
        $roomId = $params[1] ?? null;
        if ($reservationId && $roomId) {
            $this->showAllGuests($reservationId, $roomId);
        }
    }

    /**
     * Cerrar modal de edición de precios
     */
    public function cancelEditPrices()
    {
        $this->editPricesModal = false;
        $this->editPricesForm = null;
    }

    /**
     * Mostrar todos los huéspedes de una habitación
     */
    public function showAllGuests($reservationId, $roomId)
    {
        try {
            \Log::error('🔥 showAllGuests llamado con reservationId: ' . $reservationId . ', roomId: ' . $roomId);
            
            $reservation = \App\Models\Reservation::with(['customer', 'reservationRooms'])->findOrFail($reservationId);
            $room = \App\Models\Room::findOrFail($roomId);
            
            \Log::error('🏠 Habitación encontrada:', [
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'max_capacity' => $room->max_capacity,
                'beds_count' => $room->beds_count
            ]);
            
            // Obtener el reservation room específico para esta habitación
            $reservationRoom = $reservation->reservationRooms->firstWhere('room_id', $roomId);
            
            // Preparar datos de todos los huéspedes
            $allGuests = [];
            
            // Agregar huésped principal si existe
            if ($reservation->customer) {
                $allGuests[] = [
                    'id' => $reservation->customer->id,
                    'name' => $reservation->customer->name,
                    'identification' => $reservation->customer->taxProfile->identification ?? 'Sin identificación',
                    'phone' => $reservation->customer->phone ?? 'Sin teléfono',
                    'type' => 'Principal',
                    'is_primary' => true
                ];
            }
            
            // Agregar huéspedes adicionales usando el método getGuests()
            if ($reservationRoom) {
                try {
                    $additionalGuests = $reservationRoom->getGuests();
                    \Log::error('👥 Huéspedes adicionales encontrados: ' . $additionalGuests->count());
                    
                    foreach ($additionalGuests as $guest) {
                        $allGuests[] = [
                            'id' => $guest->id,
                            'name' => $guest->name,
                            'identification' => $guest->taxProfile->identification ?? 'Sin identificación',
                            'phone' => $guest->phone ?? 'Sin teléfono',
                            'type' => 'Adicional',
                            'is_primary' => false
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error cargando huéspedes adicionales:', [
                        'reservation_room_id' => $reservationRoom->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            \Log::error('👥 Huéspedes encontrados:', $allGuests);
            
            // Preparar datos para el modal
            $this->allGuestsForm = [
                'reservation_id' => $reservationId,
                'room_id' => $roomId,
                'max_capacity' => $room->max_capacity,
                'guests' => $allGuests
            ];
            
            \Log::error('💾 allGuestsForm con capacidad:', [
                'max_capacity' => $this->allGuestsForm['max_capacity'],
                'guests_count' => count($this->allGuestsForm['guests'])
            ]);
            
            $this->allGuestsModal = true;
            
        } catch (\Exception $e) {
            \Log::error('❌ Error en showAllGuests: ' . $e->getMessage(), [
                'reservation_id' => $reservationId,
                'room_id' => $roomId,
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('notify', type: 'error', message: 'Error al cargar los huéspedes: ' . $e->getMessage());
        }
    }

    /**
     * Agregar un nuevo huésped a la habitación
     */
    public function addGuestToRoom($data)
    {
        try {
            \Log::error('🔥 addGuestToRoom llamado con datos:', $data);
            
            $reservationId = $data['reservation_id'];
            $roomId = $data['room_id'];
            $guestName = $data['name'];
            $guestIdentification = $data['identification'] ?? null;
            $guestPhone = $data['phone'] ?? null;
            
            // Validar que no se exceda la capacidad
            $room = \App\Models\Room::findOrFail($roomId);
            $reservation = \App\Models\Reservation::with(['customer', 'reservationRooms'])->findOrFail($reservationId);
            $reservationRoom = $reservation->reservationRooms->firstWhere('room_id', $roomId);
            
            // Contar huéspedes actuales
            $currentGuestCount = 1; // Huésped principal
            if ($reservationRoom) {
                try {
                    $additionalGuests = $reservationRoom->getGuests();
                    $currentGuestCount += $additionalGuests->count();
                } catch (\Exception $e) {
                    \Log::warning('Error contando huéspedes actuales:', [
                        'reservation_room_id' => $reservationRoom->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            if ($currentGuestCount >= $room->max_capacity) {
                $this->dispatch('notify', type: 'error', message: 'Capacidad máxima de la habitación alcanzada');
                return;
            }
            
            // Crear nuevo cliente (huésped)
            $customer = new \App\Models\Customer();
            $customer->name = $guestName;
            $customer->phone = $guestPhone;
            $customer->created_at = now();
            $customer->updated_at = now();
            $customer->save();
            
            // Crear tax profile si hay identificación
            if ($guestIdentification) {
                $taxProfile = new \App\Models\CustomerTaxProfile();
                $taxProfile->customer_id = $customer->id;
                $taxProfile->identification = $guestIdentification;
                $taxProfile->identification_type_id = 1; // ID por defecto
                $taxProfile->created_at = now();
                $taxProfile->updated_at = now();
                $taxProfile->save();
                
                $customer->taxProfile()->associate($taxProfile);
                $customer->save();
            }
            
            // Crear reservation guest
            $reservationGuest = new \App\Models\ReservationGuest();
            $reservationGuest->reservation_id = $reservationId;
            $reservationGuest->guest_id = $customer->id;
            $reservationGuest->created_at = now();
            $reservationGuest->updated_at = now();
            $reservationGuest->save();
            
            // Asignar a la habitación
            if ($reservationRoom) {
                $reservationRoomGuest = new \App\Models\ReservationRoomGuest();
                $reservationRoomGuest->reservation_room_id = $reservationRoom->id;
                $reservationRoomGuest->reservation_guest_id = $reservationGuest->id;
                $reservationRoomGuest->created_at = now();
                $reservationRoomGuest->updated_at = now();
                $reservationRoomGuest->save();
            }
            
            \Log::error('✅ Huésped agregado correctamente:', [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'reservation_id' => $reservationId,
                'room_id' => $roomId
            ]);
            
            // Actualizar el modal con los nuevos datos
            $this->showAllGuests($reservationId, $roomId);
            
            $this->dispatch('notify', type: 'success', message: 'Huésped agregado correctamente');
            
        } catch (\Exception $e) {
            \Log::error('❌ Error en addGuestToRoom: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('notify', type: 'error', message: 'Error al agregar huésped: ' . $e->getMessage());
        }
    }

    /**
     * Guardar cambios en los precios de las noches
     */
    public function updatePrices()
    {
        try {
            \Log::error('🔥 updatePrices llamado con datos:', $this->editPricesForm);
            
            if (!$this->editPricesForm || !isset($this->editPricesForm['nights'])) {
                throw new \Exception('No hay datos de precios para actualizar');
            }

            DB::beginTransaction();
            
            $totalAmount = 0;
            
            // Actualizar cada noche
            foreach ($this->editPricesForm['nights'] as $nightData) {
                $stayNight = \App\Models\StayNight::find($nightData['id']);
                if ($stayNight) {
                    $stayNight->price = $nightData['price'];
                    $stayNight->is_paid = $nightData['is_paid'] ?? false;
                    $stayNight->updated_at = now();
                    $stayNight->save();
                    
                    $totalAmount += $nightData['price'];
                    
                    \Log::error('🌙 Noche actualizada:', [
                        'id' => $stayNight->id,
                        'price' => $stayNight->price,
                        'is_paid' => $stayNight->is_paid
                    ]);
                }
            }
            
            // Actualizar el total_amount de la reservation
            $reservation = \App\Models\Reservation::find($this->editPricesForm['id']);
            if ($reservation) {
                $reservation->total_amount = $totalAmount;
                $reservation->balance_due = $totalAmount - $reservation->payments()->sum('amount');
                $reservation->save();
                
                \Log::error('💰 Reservation actualizada:', [
                    'id' => $reservation->id,
                    'total_amount' => $reservation->total_amount,
                    'balance_due' => $reservation->balance_due
                ]);
            }
            
            DB::commit();
            
            $this->editPricesModal = false;
            $this->editPricesForm = null;
            
            $this->dispatch('notify', type: 'success', message: 'Precios actualizados correctamente');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('❌ Error en updatePrices: ' . $e->getMessage(), [
                'editPricesForm' => $this->editPricesForm,
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('notify', type: 'error', message: 'Error al actualizar precios: ' . $e->getMessage());
        }
    }

    public function registerPayment($reservationId, $amount, $paymentMethod, $bankName = null, $reference = null, $notes = null, $nightDate = null)
    {
        \Log::info('registerPayment called', [
            'reservation_id' => $reservationId,
            'amount' => $amount,
            'amount_type' => gettype($amount),
            'payment_method' => $paymentMethod,
            'payment_method_type' => gettype($paymentMethod),
            'bank_name' => $bankName,
            'reference' => $reference,
            'user_id' => auth()->id(),
        ]);
        
        try {
            // Validar y convertir reservationId
            $reservationId = (int)$reservationId;
            if ($reservationId <= 0) {
                \Log::error('Invalid reservation ID', ['reservation_id' => $reservationId]);
                $this->dispatch('notify', type: 'error', message: 'ID de reserva inválido.');
                $this->dispatch('reset-payment-modal-loading');
                return false;
            }
            
            $reservation = Reservation::find($reservationId);
            if (!$reservation) {
                \Log::error('Reservation not found', ['reservation_id' => $reservationId]);
                $this->dispatch('notify', type: 'error', message: 'Reserva no encontrada.');
                $this->dispatch('reset-payment-modal-loading');
                return false;
            }

            $this->ensureStayNightsCoverageForReservation($reservation);

            // Validar método de pago
            $paymentMethod = (string)$paymentMethod;
            if (!in_array($paymentMethod, ['efectivo', 'transferencia'])) {
                \Log::error('Invalid payment method', ['payment_method' => $paymentMethod]);
                $this->dispatch('notify', type: 'error', message: 'Método de pago inválido.');
                $this->dispatch('reset-payment-modal-loading');
                return false;
            }

            // Validar y convertir monto
            $amount = (float)$amount;
            if ($amount <= 0 || !is_numeric($amount)) {
                \Log::error('Invalid amount', ['amount' => $amount]);
                $this->dispatch('notify', type: 'error', message: 'El monto debe ser mayor a 0.');
                $this->dispatch('reset-payment-modal-loading');
                return false;
            }

            // Obtener balance antes del pago para determinar el mensaje
            $paymentsTotalBefore = (float)($reservation->payments()->sum('amount') ?? 0);
            $salesDebt = (float)($reservation->sales?->where('is_paid', false)->sum('total') ?? 0);
            
            // ✅ NUEVO SSOT: Calcular desde stay_nights si existe
            try {
                $totalAmount = (float)\App\Models\StayNight::where('reservation_id', $reservation->id)
                    ->sum('price');
                
                // Si no hay noches, usar fallback
                if ($totalAmount <= 0) {
                    $totalAmount = (float)($reservation->total_amount ?? 0);
                }
            } catch (\Exception $e) {
                // Si falla (tabla no existe), usar fallback
                $totalAmount = (float)($reservation->total_amount ?? 0);
            }
            
            $balanceDueBefore = $totalAmount - $paymentsTotalBefore + $salesDebt;

            // Validar que el monto no exceda el saldo pendiente
            if ($amount > $balanceDueBefore) {
                $this->dispatch('notify', type: 'error', message: "El monto no puede ser mayor al saldo pendiente (\${$balanceDueBefore}).");
                $this->dispatch('reset-payment-modal-loading');
                return false;
            }

            // Obtener o crear ID del método de pago
            $paymentMethodId = $this->getPaymentMethodId($paymentMethod);
            
            // Si no existe, crear el método de pago automáticamente
            if (!$paymentMethodId) {
                $methodData = match($paymentMethod) {
                    'efectivo' => ['code' => 'efectivo', 'name' => 'Efectivo'],
                    'transferencia' => ['code' => 'transferencia', 'name' => 'Transferencia'],
                    default => null
                };
                
                if ($methodData) {
                    try {
                        // Usar updateOrInsert para evitar duplicados y crear si no existe
                        DB::table('payments_methods')->updateOrInsert(
                            ['code' => $methodData['code']],
                            [
                                'name' => $methodData['name'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        
                        // Obtener el ID del método recién creado o existente
                        $paymentMethodId = DB::table('payments_methods')
                            ->where('code', $methodData['code'])
                            ->value('id');
                    } catch (\Exception $e) {
                        \Log::error('Error creating payment method', [
                            'method' => $paymentMethod,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // Fallback: buscar por nombre o código alternativo
            if (!$paymentMethodId) {
                if ($paymentMethod === 'efectivo') {
                    $paymentMethodId = DB::table('payments_methods')
                        ->where(function($query) {
                            $query->where('name', 'Efectivo')
                                  ->orWhere('code', 'cash')
                                  ->orWhere('code', 'efectivo');
                        })
                        ->value('id');
                } elseif ($paymentMethod === 'transferencia') {
                    $paymentMethodId = DB::table('payments_methods')
                        ->where(function($query) {
                            $query->where('name', 'Transferencia')
                                  ->orWhere('code', 'transferencia')
                                  ->orWhere('code', 'transfer');
                        })
                        ->value('id');
                }
            }

            if (!$paymentMethodId) {
                $this->dispatch('notify', type: 'error', message: 'Error: No se pudo obtener o crear el método de pago. Contacte al administrador.');
                \Log::error('Payment method not found after all attempts', [
                    'payment_method' => $paymentMethod,
                    'available_methods' => DB::table('payments_methods')->get()->toArray()
                ]);
                $this->dispatch('reset-payment-modal-loading');
                return false;
            }

            // Validar que el usuario esté autenticado
            $userId = auth()->id();
            if (!$userId) {
                $this->dispatch('notify', type: 'error', message: 'Error: No se pudo identificar al usuario. Por favor, recargue la página e intente nuevamente.');
                \Log::error('User not authenticated when creating payment', [
                    'reservation_id' => $reservation->id,
                ]);
                $this->dispatch('reset-payment-modal-loading');
                return false;
            }

            // Crear el pago en la tabla payments (SSOT)
            try {
                \Log::info('Attempting to create payment', [
                    'reservation_id' => $reservation->id,
                    'amount' => $amount,
                    'payment_method_id' => $paymentMethodId,
                    'payment_method' => $paymentMethod,
                    'bank_name' => $paymentMethod === 'transferencia' ? ($bankName ?: null) : null,
                    'reference' => $paymentMethod === 'transferencia' ? ($reference ?: null) : 'Pago registrado',
                    'created_by' => $userId,
                ]);
                
                $payment = Payment::create([
                    'reservation_id' => $reservation->id,
                    'amount' => $amount,
                    'payment_method_id' => $paymentMethodId,
                    'bank_name' => $paymentMethod === 'transferencia' ? ($bankName ?: null) : null,
                    'reference' => $paymentMethod === 'transferencia' ? ($reference ?: null) : 'Pago registrado',
                    'paid_at' => now(),
                    'created_by' => $userId,
                ]);
                
                \Log::info('Payment created successfully', [
                    'payment_id' => $payment->id,
                    'reservation_id' => $reservation->id,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $errorMessage = 'Error al crear el registro de pago.';
                if (str_contains($e->getMessage(), 'foreign key constraint')) {
                    $errorMessage = 'Error: El método de pago o la reserva no existe en el sistema.';
                } elseif (str_contains($e->getMessage(), 'column') && str_contains($e->getMessage(), 'cannot be null')) {
                    $errorMessage = 'Error: Faltan datos requeridos para registrar el pago.';
                }
                
                $this->dispatch('notify', type: 'error', message: $errorMessage);
                \Log::error('Error creating payment record', [
                    'reservation_id' => $reservation->id,
                    'amount' => $amount,
                    'payment_method_id' => $paymentMethodId,
                    'payment_method' => $paymentMethod,
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_info' => $e->errorInfo ?? null,
                    'sql_state' => $e->errorInfo[0] ?? null,
                    'sql_error_code' => $e->errorInfo[1] ?? null,
                    'sql_error_message' => $e->errorInfo[2] ?? null,
                ]);
                $this->dispatch('reset-payment-modal-loading');
                return false;
            } catch (\Exception $e) {
                $this->dispatch('notify', type: 'error', message: 'Error inesperado al crear el pago: ' . $e->getMessage());
                \Log::error('Unexpected error creating payment record', [
                    'reservation_id' => $reservation->id,
                    'amount' => $amount,
                    'payment_method_id' => $paymentMethodId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->dispatch('reset-payment-modal-loading');
                return false;
            }

            // Reflejar el abono/pago en stay_nights (por fecha específica o FIFO global)
            try {
                $allocation = $this->allocatePaymentToStayNights($reservation, $amount, $nightDate);
                \Log::info('Payment allocated to stay nights', [
                    'reservation_id' => $reservation->id,
                    'payment_id' => $payment->id ?? null,
                    'night_date' => $nightDate,
                    'nights_marked' => $allocation['nights_marked'] ?? 0,
                    'remaining_amount' => $allocation['remaining_amount'] ?? 0,
                ]);
            } catch (\Exception $e) {
                // No crítico: el pago ya quedó registrado
                \Log::warning('Error allocating payment to stay nights', [
                    'reservation_id' => $reservation->id,
                    'night_date' => $nightDate,
                    'error' => $e->getMessage(),
                ]);
            }

            // Recalcular balance_due de la reserva
            $paymentsTotal = (float)($reservation->payments()->sum('amount') ?? 0);
            $balanceDue = $totalAmount - $paymentsTotal + $salesDebt;

            // Actualizar estado de pago de la reserva
            $paymentStatusCode = $balanceDue <= 0 ? 'paid' : ($paymentsTotal > 0 ? 'partial' : 'pending');
            $paymentStatusId = DB::table('payment_statuses')->where('code', $paymentStatusCode)->value('id');

            $reservation->update([
                'deposit_amount' => max(0, $paymentsTotal),
                'balance_due' => max(0, $balanceDue),
                'payment_status_id' => $paymentStatusId,
            ]);

            // Mensaje específico según el tipo de pago
            if ($balanceDue <= 0) {
                $this->dispatch('notify', type: 'success', message: 'Pago registrado. Cuenta al día.');
            } else {
                $formattedBalance = number_format($balanceDue, 0, ',', '.');
                $this->dispatch('notify', type: 'success', message: "Abono registrado. Saldo pendiente: \${$formattedBalance}");
            }

            // Refrescar la relación de pagos de la reserva para que se actualice en el modal
            $reservation->refresh();
            $reservation->load('payments');
            
            $this->dispatch('refreshRooms');
            
            // Cerrar el modal de pago si está abierto
            $this->dispatch('close-payment-modal');
            $this->dispatch('payment-registered');
            
            // Recargar datos del modal si está abierto
            if ($this->roomDetailModal && $this->detailData && isset($this->detailData['reservation']['id']) && $this->detailData['reservation']['id'] == $reservationId) {
                // Obtener el room_id desde reservation_rooms
                $reservationRoom = $reservation->reservationRooms()->first();
                if ($reservationRoom && $reservationRoom->room_id) {
                    // Forzar recarga del modal con los nuevos datos de pago
                    $this->openRoomDetail($reservationRoom->room_id);
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Error al registrar pago: ' . $e->getMessage());
            // Disparar evento para resetear loading del modal y mostrar el error
            $this->dispatch('reset-payment-modal-loading');
            \Log::error('Error registering payment', [
                'reservation_id' => $reservationId,
                'amount' => $amount ?? null,
                'payment_method' => $paymentMethod ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Apply a payment amount to stay nights.
     * - If nightDate is provided, tries that specific night first.
     * - Remaining amount is applied FIFO by date/room/id.
     *
     * @return array{nights_marked:int, remaining_amount:float}
     */
    private function allocatePaymentToStayNights(Reservation $reservation, float $amount, ?string $nightDate = null): array
    {
        $remaining = round(max(0, $amount), 2);
        $nightsMarked = 0;

        if ($remaining <= 0) {
            return ['nights_marked' => 0, 'remaining_amount' => 0.0];
        }

        if (!empty($nightDate)) {
            try {
                $targetDate = Carbon::parse($nightDate)->toDateString();
            } catch (\Throwable $e) {
                $targetDate = null;
            }

            if ($targetDate) {
                $targetNight = \App\Models\StayNight::query()
                    ->where('reservation_id', $reservation->id)
                    ->whereDate('date', $targetDate)
                    ->orderBy('id')
                    ->first();

                if ($targetNight && !$targetNight->is_paid) {
                    $nightPrice = round((float) ($targetNight->price ?? 0), 2);
                    if ($nightPrice <= 0 || $remaining >= $nightPrice) {
                        $targetNight->update(['is_paid' => true]);
                        $remaining = round(max(0, $remaining - max(0, $nightPrice)), 2);
                        $nightsMarked++;
                    }
                }
            }
        }

        if ($remaining > 0) {
            $unpaidNights = \App\Models\StayNight::query()
                ->where('reservation_id', $reservation->id)
                ->where('is_paid', false)
                ->orderBy('date')
                ->orderBy('room_id')
                ->orderBy('id')
                ->get();

            foreach ($unpaidNights as $night) {
                if ($remaining <= 0) {
                    break;
                }

                $nightPrice = round((float) ($night->price ?? 0), 2);
                if ($nightPrice <= 0) {
                    $night->update(['is_paid' => true]);
                    $nightsMarked++;
                    continue;
                }

                if ($remaining < $nightPrice) {
                    break;
                }

                $night->update(['is_paid' => true]);
                $remaining = round(max(0, $remaining - $nightPrice), 2);
                $nightsMarked++;
            }
        }

        return [
            'nights_marked' => $nightsMarked,
            'remaining_amount' => $remaining,
        ];
    }

    /**
     * Ensure stay night rows exist for all configured reservation room dates.
     */
    private function ensureStayNightsCoverageForReservation(Reservation $reservation): void
    {
        $reservation->loadMissing(['reservationRooms', 'stays']);
        $staysByRoom = $reservation->stays->keyBy(static fn ($stay) => (int) ($stay->room_id ?? 0));

        foreach ($reservation->reservationRooms as $reservationRoom) {
            $roomId = (int) ($reservationRoom->room_id ?? 0);
            if ($roomId <= 0 || empty($reservationRoom->check_in_date) || empty($reservationRoom->check_out_date)) {
                continue;
            }

            /** @var \App\Models\Stay|null $stay */
            $stay = $staysByRoom->get($roomId);
            if (!$stay) {
                continue;
            }

            $from = Carbon::parse((string) $reservationRoom->check_in_date)->startOfDay();
            $to = Carbon::parse((string) $reservationRoom->check_out_date)->startOfDay();

            for ($cursor = $from->copy(); $cursor->lt($to); $cursor->addDay()) {
                $this->ensureNightForDate($stay, $cursor->copy());
            }
        }
    }

    /**
     * Registra una devolución de dinero al cliente.
     * 
     * SINGLE SOURCE OF TRUTH: Usa la tabla `payments` para registrar devoluciones.
     * Las devoluciones se registran como pagos con monto negativo.
     * 
     * REGLA FINANCIERA:
     * - Solo se puede devolver cuando hay PAGO EN EXCESO (overpaid > 0)
     * - overpaid = SUM(payments donde amount > 0) - total_amount
     * - Un pago completo (overpaid = 0) NO es un saldo a favor
     * - balance_due = 0 NO significa saldo a favor
     * 
     * @param int $reservationId ID de la reserva
     * @param float|null $amount Monto a devolver (opcional, si no se proporciona se usa todo el overpaid)
     * @param string|null $paymentMethod Método de pago ('efectivo' o 'transferencia', opcional, default: 'efectivo')
     * @param string|null $bankName Nombre del banco (solo para transferencia)
     * @param string|null $reference Referencia (solo para transferencia)
     * @return bool
     */
    public function registerCustomerRefund($reservationId, $amount = null, $paymentMethod = null, $bankName = null, $reference = null)
    {
        \Log::info('registerCustomerRefund called', [
            'reservation_id' => $reservationId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'bank_name' => $bankName,
            'reference' => $reference,
        ]);

        try {
            // Validar y convertir reservationId
            $reservationId = (int)$reservationId;
            if ($reservationId <= 0) {
                \Log::error('Invalid reservation ID in registerCustomerRefund', ['reservation_id' => $reservationId]);
                $this->dispatch('notify', type: 'error', message: 'ID de reserva inválido.');
                return false;
            }

            $reservation = Reservation::with(['payments', 'sales'])->find($reservationId);
            if (!$reservation) {
                \Log::error('Reservation not found in registerCustomerRefund', ['reservation_id' => $reservationId]);
                $this->dispatch('notify', type: 'error', message: 'Reserva no encontrada.');
                return false;
            }

            // ===== PASO 0: REGLA HOTELERA CRÍTICA - Bloquear devoluciones mientras esté ocupada =====
            // REGLA: Nunca existe "saldo a favor" mientras la habitación siga OCUPADA
            // Un saldo a favor solo puede evaluarse cuando la estadía termina (stay.status = finished)
            $activeStay = \App\Models\Stay::where('reservation_id', $reservationId)
                ->whereNull('check_out_at')
                ->whereIn('status', ['active', 'pending_checkout'])
                ->exists();

            if ($activeStay) {
                $this->dispatch('notify', type: 'error', message: 'No se puede registrar devolución mientras la habitación esté ocupada. El pago se considera adelantado para noches futuras.');
                \Log::info('Refund blocked: Active stay exists', [
                    'reservation_id' => $reservationId,
                    'reason' => 'stay_active',
                ]);
                return false;
            }

            // ===== PASO 1: Calcular totales reales (REGLA FINANCIERA CORRECTA) =====
            // Solo contar pagos POSITIVOS (dinero que el cliente pagó)
            $totalPaid = (float)($reservation->payments->where('amount', '>', 0)->sum('amount') ?? 0);
            
            // ✅ NUEVO SSOT: Calcular desde stay_nights si existe
            try {
                $totalAmount = (float)\App\Models\StayNight::where('reservation_id', $reservationId)
                    ->sum('price');
                
                // Si no hay noches, usar fallback
                if ($totalAmount <= 0) {
                    $totalAmount = (float)($reservation->total_amount ?? 0);
                }
            } catch (\Exception $e) {
                // Si falla (tabla no existe), usar fallback
                $totalAmount = (float)($reservation->total_amount ?? 0);
            }
            
            // Calcular saldo a favor (pago en exceso)
            // overpaid > 0 significa que el cliente pagó MÁS de lo que debe
            $overpaid = $totalPaid - $totalAmount;

            // ===== PASO 2: Validar que existe saldo a favor para devolver =====
            // REGLA: Solo se puede devolver cuando hay pago en exceso (overpaid > 0)
            // Un pago completo (overpaid = 0) NO es un saldo a favor
            if ($overpaid <= 0) {
                $this->dispatch('notify', type: 'error', message: 'La cuenta está correctamente pagada. No hay saldo a favor para devolver.');
                \Log::info('Refund blocked: No overpaid amount', [
                    'reservation_id' => $reservationId,
                    'total_paid' => $totalPaid,
                    'total_amount' => $totalAmount,
                    'overpaid' => $overpaid,
                ]);
                return false;
            }

            // ===== PASO 3: Determinar monto a devolver =====
            // Si no se proporciona amount, usar todo el saldo a favor
            if ($amount === null) {
                $amount = $overpaid;
            }

            // Validar monto
            $amount = (float)$amount;
            if ($amount <= 0 || !is_numeric($amount)) {
                $this->dispatch('notify', type: 'error', message: 'El monto debe ser mayor a 0.');
                return false;
            }

            // ===== PASO 4: Validar que la devolución no supere el saldo a favor =====
            // REGLA: No se puede devolver más de lo que se pagó en exceso
            if ($amount > $overpaid) {
                $formattedOverpaid = number_format($overpaid, 0, ',', '.');
                $this->dispatch('notify', type: 'error', message: "La devolución no puede superar el saldo a favor del cliente (\${$formattedOverpaid}).");
                return false;
            }

            // Método de pago por defecto: efectivo
            $paymentMethod = $paymentMethod ? (string)$paymentMethod : 'efectivo';
            if (!in_array($paymentMethod, ['efectivo', 'transferencia'])) {
                $this->dispatch('notify', type: 'error', message: 'Método de pago inválido.');
                return false;
            }

            // Obtener o crear ID del método de pago
            $paymentMethodId = $this->getPaymentMethodId($paymentMethod);
            
            // Si no existe, crear el método de pago automáticamente
            if (!$paymentMethodId) {
                $methodData = match($paymentMethod) {
                    'efectivo' => ['code' => 'efectivo', 'name' => 'Efectivo'],
                    'transferencia' => ['code' => 'transferencia', 'name' => 'Transferencia'],
                    default => null
                };
                
                if ($methodData) {
                    try {
                        DB::table('payments_methods')->updateOrInsert(
                            ['code' => $methodData['code']],
                            [
                                'name' => $methodData['name'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        
                        $paymentMethodId = DB::table('payments_methods')
                            ->where('code', $methodData['code'])
                            ->value('id');
                    } catch (\Exception $e) {
                        \Log::error('Error creating payment method in registerCustomerRefund', [
                            'method' => $paymentMethod,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            if (!$paymentMethodId) {
                $this->dispatch('notify', type: 'error', message: 'Error: No se pudo obtener o crear el método de pago.');
                \Log::error('Payment method not found in registerCustomerRefund', ['payment_method' => $paymentMethod]);
                return false;
            }

            // Validar que el usuario esté autenticado
            $userId = auth()->id();
            if (!$userId) {
                $this->dispatch('notify', type: 'error', message: 'Error: No se pudo identificar al usuario.');
                \Log::error('User not authenticated in registerCustomerRefund', ['reservation_id' => $reservation->id]);
                return false;
            }

            // Crear el pago negativo en la tabla payments (SSOT)
            try {
                \Log::info('Attempting to create refund payment', [
                    'reservation_id' => $reservation->id,
                    'amount' => -$amount, // NEGATIVO para devolución
                    'payment_method_id' => $paymentMethodId,
                    'payment_method' => $paymentMethod,
                ]);
                
                $payment = Payment::create([
                    'reservation_id' => $reservation->id,
                    'amount' => -$amount, // NEGATIVO para devolución
                    'payment_method_id' => $paymentMethodId,
                    'bank_name' => $paymentMethod === 'transferencia' ? ($bankName ?: null) : null,
                    'reference' => $paymentMethod === 'transferencia' ? ($reference ?: 'Devolución registrada') : 'Devolución en efectivo',
                    'paid_at' => now(),
                    'created_by' => $userId,
                ]);
                
                \Log::info('Refund payment created successfully', [
                    'payment_id' => $payment->id,
                    'reservation_id' => $reservation->id,
                    'amount' => -$amount,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                $errorMessage = 'Error al crear el registro de devolución.';
                if (str_contains($e->getMessage(), 'foreign key constraint')) {
                    $errorMessage = 'Error: El método de pago o la reserva no existe en el sistema.';
                } elseif (str_contains($e->getMessage(), 'column') && str_contains($e->getMessage(), 'cannot be null')) {
                    $errorMessage = 'Error: Faltan datos requeridos para registrar la devolución.';
                }
                
                $this->dispatch('notify', type: 'error', message: $errorMessage);
                \Log::error('Error creating refund payment record', [
                    'reservation_id' => $reservation->id,
                    'amount' => -$amount,
                    'error' => $e->getMessage(),
                ]);
                return false;
            } catch (\Exception $e) {
                $this->dispatch('notify', type: 'error', message: 'Error inesperado al registrar la devolución: ' . $e->getMessage());
                \Log::error('Unexpected error creating refund payment record', [
                    'reservation_id' => $reservation->id,
                    'amount' => -$amount,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return false;
            }

            // Recalcular balance_due de la reserva
            $reservation->refresh();
            $reservation->load('payments', 'sales');
            
            // CRÍTICO: Separar pagos positivos y negativos (devoluciones)
            $paymentsTotalAfter = (float)($reservation->payments->where('amount', '>', 0)->sum('amount') ?? 0);
            $refundsTotalAfter = abs((float)($reservation->payments->where('amount', '<', 0)->sum('amount') ?? 0));
            $salesDebt = (float)($reservation->sales->where('is_paid', false)->sum('total') ?? 0);
            
            // Fórmula: deuda = (hospedaje - abonos_reales) + devoluciones + consumos_pendientes
            $balanceDueAfter = ($totalAmount - $paymentsTotalAfter) + $refundsTotalAfter + $salesDebt;

            // Actualizar estado de pago de la reserva
            $paymentStatusCode = $balanceDueAfter <= 0 ? 'paid' : ($paymentsTotalAfter > 0 ? 'partial' : 'pending');
            $paymentStatusId = DB::table('payment_statuses')->where('code', $paymentStatusCode)->value('id');

            $reservation->update([
                'balance_due' => max(0, $balanceDueAfter),
                'payment_status_id' => $paymentStatusId,
            ]);

            // Mensaje de éxito
            $formattedAmount = number_format($amount, 0, ',', '.');
            $this->dispatch('notify', type: 'success', message: "Devolución de \${$formattedAmount} registrada correctamente.");

            // Emitir eventos para refrescar UI y cerrar modal
            $this->dispatch('refreshRooms');
            $this->dispatch('close-room-release-modal');

            return true;
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Error al registrar devolución: ' . $e->getMessage());
            \Log::error('Error registering customer refund', [
                'reservation_id' => $reservationId,
                'amount' => $amount ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
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
                'check_in_time' => HotelTime::checkInTime(), // Usar hora global del hotel
                'check_out_time' => HotelTime::checkOutTime(), // Usar hora global del hotel
                'client_id' => null,
                'guests_count' => 1,
                'max_capacity' => $room->max_capacity,
                'total' => $basePrice,
                'deposit' => 0,
                'payment_method' => 'efectivo',
                    'bank_name' => '', // Opcional para transferencias
                    'reference' => '', // Opcional para transferencias
            ];
            $this->additionalGuests = [];
            $this->quickRentModal = true;
            $this->dispatch('quickRentOpened');
                $this->recalculateQuickRentTotals($room);
        }
    }

    public function closeQuickRent()
    {
        $this->quickRentModal = false;
        $this->rentForm = null;
        $this->additionalGuests = null;
    }

    public function updatedRentFormCheckOutDate($value): void
    {
        $this->rentForm['check_out_date'] = $value;
        $this->recalculateQuickRentTotals();
    }

    public function updatedRentFormClientId($value): void
    {
        // 🔐 NORMALIZAR: convertir cadena vacía a NULL (requisito de BD INTEGER)
        if ($value === '' || $value === null) {
            $this->rentForm['client_id'] = null;
        } else {
            $this->rentForm['client_id'] = is_numeric($value) ? (int)$value : null;
        }
        $this->recalculateQuickRentTotals();
    }

    public function addGuestFromCustomerId($customerId)
    {
        $customer = \App\Models\Customer::find($customerId);
        
        if (!$customer) {
            $this->dispatch('notify', type: 'error', message: 'Cliente no encontrado.');
            return;
        }

        $room = null;
        if (!empty($this->rentForm['room_id'])) {
            $room = Room::with('rates')->find($this->rentForm['room_id']);
        }

        // 🔐 VALIDACIÓN CRÍTICA: Verificar capacidad ANTES de agregar huésped adicional
        if ($room) {
            $principalCount = !empty($this->rentForm['client_id']) ? 1 : 0;
            $currentAdditionalCount = is_array($this->additionalGuests) ? count($this->additionalGuests) : 0;
            $totalAfterAdd = $principalCount + $currentAdditionalCount + 1;
            $maxCapacity = (int)($room->max_capacity ?? 1);

            if ($totalAfterAdd > $maxCapacity) {
                $this->dispatch('notify', type: 'error', message: "No se puede agregar más huéspedes. La habitación tiene capacidad máxima de {$maxCapacity} persona" . ($maxCapacity > 1 ? 's' : '') . ".");
                return;
            }
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

        // Recalcular total y contador de huéspedes
        $this->recalculateQuickRentTotals($room);
    }

    public function removeGuest($index)
    {
        if (isset($this->additionalGuests[$index])) {
            unset($this->additionalGuests[$index]);
            $this->additionalGuests = array_values($this->additionalGuests);
            $this->dispatch('notify', type: 'success', message: 'Huésped removido.');
            $this->recalculateQuickRentTotals();
        }
    }

    public function submitQuickRent()
    {
        if (!$this->rentForm) {
            return;
        }

        try {
            $paymentMethod = $this->rentForm['payment_method'] ?? 'efectivo';
            $bankName = $paymentMethod === 'transferencia' ? trim($this->rentForm['bank_name'] ?? '') : null;
            $reference = $paymentMethod === 'transferencia' ? trim($this->rentForm['reference'] ?? '') : null;

            // BLOQUEO: Verificar si es fecha histórica
            if (Carbon::parse($this->rentForm['check_in_date'])->lt(Carbon::today())) {
                throw new \RuntimeException('No se pueden crear reservas en fechas históricas.');
            }

            // 🔐 NORMALIZAR client_id: convertir cadena vacía a NULL (requisito de BD INTEGER)
            $clientId = $this->rentForm['client_id'] ?? null;
            if ($clientId === '' || $clientId === null) {
                $clientId = null; // ✅ NULL para reservas sin cliente (walk-in sin asignar)
            } else {
                $clientId = is_numeric($clientId) ? (int)$clientId : null;
            }
            
            $validated = [
                'room_id' => $this->rentForm['room_id'],
                'check_in_date' => $this->rentForm['check_in_date'],
                'check_out_date' => $this->rentForm['check_out_date'],
                'client_id' => $clientId, // ✅ Normalizado: NULL o entero válido
                'guests_count' => $this->rentForm['guests_count'],
            ];

            // ===== CARGAR HABITACIÓN CON TARIFAS (OBLIGATORIO) =====
            // CRÍTICO: Usar with('rates') para asegurar que las tarifas estén cargadas
            // Usar findOrFail() para lanzar excepción automáticamente si no existe
            $room = Room::with('rates')->findOrFail($validated['room_id']);

            // 🔐 VALIDACIÓN CRÍTICA: Verificar que NO se exceda la capacidad máxima
            $guests = $this->calculateGuestCount();
            $maxCapacity = (int)($room->max_capacity ?? 1);
            
            if ($guests > $maxCapacity) {
                throw new \RuntimeException(
                    "No se puede confirmar el arrendamiento. La cantidad de huéspedes ({$guests}) excede la capacidad máxima de la habitación ({$maxCapacity} persona" . ($maxCapacity > 1 ? 's' : '') . ")."
                );
            }

            $this->rentForm['guests_count'] = $guests;
            $validated['guests_count'] = $guests;

            $checkIn = HotelTime::defaultCheckIn(Carbon::parse($validated['check_in_date']));
            $checkOut = HotelTime::defaultCheckOut(Carbon::parse($validated['check_out_date']));
            $nights = max(1, $checkIn->diffInDays($checkOut));

            // ===== CALCULAR TOTAL DEL HOSPEDAJE (SSOT FINANCIERO) =====
            // REGLA CRÍTICA: El total puede venir de DOS fuentes (en orden de prioridad):
            // 1. PRECIO MANUAL/ACORDADO desde el formulario (rentForm.total) - SSOT absoluto
            // 2. CÁLCULO AUTOMÁTICO desde tarifas (findRateForGuests) - fallback
            //
            // REGLA: El total del hospedaje se define UNA SOLA VEZ al arrendar
            // Este valor NO se recalcula después, NO depende de payments, NO depende del release
            
            // Log para debugging: verificar datos antes del cálculo
            \Log::critical('QUICK RENT RAW FORM DATA', [
                'rentForm' => $this->rentForm,
                'guests' => $guests,
                'nights' => $nights,
                'rentForm_total' => $this->rentForm['total'] ?? null,
                'rates_count' => $room->rates->count(),
                'rates' => $room->rates->map(fn($r) => [
                    'id' => $r->id,
                    'min_guests' => $r->min_guests,
                    'max_guests' => $r->max_guests,
                    'price_per_night' => $r->price_per_night,
                ])->toArray(),
                'base_price_per_night' => $room->base_price_per_night,
            ]);
            
            // ===== OPCIÓN 1: PRECIO MANUAL/ACORDADO (SSOT ABSOLUTO) =====
            // Si el formulario tiene un total definido explícitamente (manual o calculado en frontend),
            // ese valor es la VERDAD ABSOLUTA y NO se recalcula desde tarifas
            $manualTotal = isset($this->rentForm['total']) ? (float)($this->rentForm['total']) : 0;
            
            if ($manualTotal > 0) {
                // ✅ PRECIO MANUAL ES SSOT: usar directamente el valor del formulario
                $totalAmount = $manualTotal;
                // Calcular pricePerNight retroactivamente para logging (no para persistencia)
                $pricePerNight = $nights > 0 ? ($totalAmount / $nights) : $totalAmount;
                
                \Log::info('QuickRent: Using manual price from form (SSOT)', [
                    'room_id' => $room->id,
                    'manual_total' => $manualTotal,
                    'nights' => $nights,
                    'calculated_price_per_night' => $pricePerNight,
                ]);
            } else {
                // ===== OPCIÓN 2: CÁLCULO AUTOMÁTICO DESDE TARIFAS (FALLBACK) =====
                // Si NO hay precio manual, calcular desde tarifas del sistema
                $pricePerNight = $this->findRateForGuests($room, $guests);
                $totalAmount = $pricePerNight * $nights;
                
                \Log::info('QuickRent: Using calculated price from rates (fallback)', [
                    'room_id' => $room->id,
                    'price_per_night' => $pricePerNight,
                    'nights' => $nights,
                    'calculated_total' => $totalAmount,
                ]);
            }
            
            // Validar que totalAmount sea mayor que 0
            if ($totalAmount <= 0) {
                throw new \RuntimeException('El total del hospedaje debe ser mayor a 0. Verifique las tarifas de la habitación.');
            }
            
            $depositAmount = (float)($this->rentForm['deposit'] ?? 0); // Del formulario
            $balanceDue = $totalAmount - $depositAmount;

            $paymentStatusCode = $balanceDue <= 0 ? 'paid' : ($depositAmount > 0 ? 'partial' : 'pending');
            $paymentStatusId = DB::table('payment_statuses')->where('code', $paymentStatusCode)->value('id');

            $reservationCode = sprintf('RSV-%s-%s', now()->format('YmdHis'), Str::upper(Str::random(4)));

            // ===== PASO 1: Crear reserva técnica para walk-in =====
            // CRÍTICO: total_amount es el SSOT financiero del hospedaje, debe persistirse correctamente
            $reservation = Reservation::create([
                'reservation_code' => $reservationCode,
                'client_id' => $validated['client_id'],
                'status_id' => 1, // pending
                'total_guests' => $validated['guests_count'],
                'adults' => $validated['guests_count'],
                'children' => 0,
                'total_amount' => $totalAmount,        // ✅ SSOT: Total del hospedaje (NO se recalcula)
                'deposit_amount' => $depositAmount,    // Abono inicial (puede cambiar con más pagos)
                'balance_due' => $balanceDue,          // Saldo pendiente (se recalcula con payments)
                'payment_status_id' => $paymentStatusId,
                'source_id' => 1, // reception / walk_in
                'created_by' => auth()->id(),
            ]);
            
            // CRÍTICO: Refrescar reserva para asegurar que total_amount se persista correctamente
            $reservation->refresh();
            
            // Log para debugging: verificar que total_amount se guardó correctamente
            \Log::info('Quick Rent: Reservation created', [
                'reservation_id' => $reservation->id,
                'total_amount' => $reservation->total_amount,
                'price_per_night' => $pricePerNight,
                'nights' => $nights,
                'calculated_total' => $totalAmount,
                'deposit_amount' => $depositAmount,
            ]);
            
            // VALIDACIÓN CRÍTICA: Verificar que total_amount se guardó correctamente
            if ((float)($reservation->total_amount ?? 0) <= 0 || abs((float)$reservation->total_amount - $totalAmount) > 0.01) {
                \Log::error('Quick Rent: total_amount NOT persisted correctly', [
                    'reservation_id' => $reservation->id,
                    'expected_total' => $totalAmount,
                    'actual_total' => $reservation->total_amount,
                ]);
                throw new \RuntimeException("Error: El total del hospedaje no se guardó correctamente. Valor esperado: \${$totalAmount}, Valor guardado: \${$reservation->total_amount}");
            }

            // ===== REGISTRAR PAGO EN payments (SSOT FINANCIERO OBLIGATORIO) =====
            // REGLA CRÍTICA: SIEMPRE que haya un abono (depositAmount > 0), debe registrarse en payments
            // Esto es obligatorio para mantener coherencia financiera con:
            // - Room Detail Modal (usa payments como SSOT)
            // - Stay History (calcula noches pagadas desde payments)
            // - Room Release (evalúa pagos desde payments)
            // 
            // Independientemente del método de pago (efectivo o transferencia),
            // TODO abono recibido genera un registro en payments.
            
            if ($depositAmount > 0) {
                // Obtener payment_method_id según el método seleccionado
                $paymentMethodId = $this->getPaymentMethodId($paymentMethod);
                if (!$paymentMethodId) {
                    // Fallback: buscar método de pago por código o nombre
                    $paymentMethodId = DB::table('payments_methods')
                        ->where('code', strtolower($paymentMethod))
                        ->orWhere('name', ucfirst($paymentMethod))
                        ->value('id');
                }
                
                // Preparar referencia solo para transferencias
                $referencePayload = null;
                $bankNameValue = null;
                
                if ($paymentMethod === 'transferencia') {
                    if ($reference && $bankName) {
                        $referencePayload = sprintf('%s | Banco: %s', $reference, $bankName);
                        $bankNameValue = $bankName;
                    } elseif ($reference) {
                        $referencePayload = $reference;
                    } elseif ($bankName) {
                        $referencePayload = sprintf('Banco: %s', $bankName);
                        $bankNameValue = $bankName;
                    }
                } else {
                    // Para efectivo, usar referencia genérica
                    $referencePayload = 'Abono registrado en Quick Rent';
                }
                
                // Registrar pago en payments (SSOT financiero)
                Payment::create([
                    'reservation_id' => $reservation->id,
                    'amount' => $depositAmount,
                    'payment_method_id' => $paymentMethodId,
                    'bank_name' => $bankNameValue,
                    'reference' => $referencePayload,
                    'paid_at' => now(),
                    'created_by' => auth()->id(),
                ]);
                
                \Log::info('Quick Rent: Payment registered in SSOT', [
                    'reservation_id' => $reservation->id,
                    'amount' => $depositAmount,
                    'payment_method' => $paymentMethod,
                    'payment_method_id' => $paymentMethodId,
                ]);
            }

            // ===== PASO 2: Crear reservation_room =====
            $reservationRoom = ReservationRoom::create([
                'reservation_id' => $reservation->id,
                'room_id' => $validated['room_id'],
                'check_in_date' => $validated['check_in_date'],
                'check_out_date' => $validated['check_out_date'],
                'nights' => $nights,
                'price_per_night' => $pricePerNight,
            ]);

            // ===== PASO 2.5: Persistir huéspedes adicionales =====
            // SSOT: Huésped principal está en reservations.client_id
            // Huéspedes adicionales van en reservation_guests + reservation_room_guests
            if (!empty($this->additionalGuests) && is_array($this->additionalGuests)) {
                $additionalGuestIds = array_filter(
                    array_column($this->additionalGuests, 'customer_id'),
                    fn($id) => !empty($id) && is_numeric($id) && $id > 0
                );
                
                if (!empty($additionalGuestIds)) {
                    $this->assignGuestsToRoom($reservationRoom, $additionalGuestIds);
                }
            }

            // ===== PASO 3: CRÍTICO - Crear STAY activa AHORA (check-in inmediato) =====
            // Una stay activa es lo que marca que la habitación está OCUPADA
            $stay = \App\Models\Stay::create([
                'reservation_id' => $reservation->id,
                'room_id' => $validated['room_id'],
                'check_in_at' => now(), // Check-in INMEDIATO (timestamp)
                'check_out_at' => null, // Se completará al checkout
                'status' => 'active', // estados: active, pending_checkout, finished
            ]);

            // CRITICAL: Refrescar el modelo Room para invalidar cualquier caché de relaciones
            // Esto asegura que las siguientes consultas encuentren la stay recién creada
            $room = Room::find($validated['room_id']);
            if ($room) {
                // Invalidar la relación stays en memoria
                $room->unsetRelation('stays');
            }

            // ÉXITO: Habitación ahora debe aparecer como OCUPADA
            $this->dispatch('notify', type: 'success', message: 'Arriendo registrado exitosamente. Habitación ocupada.');
            $this->closeQuickRent();
            
            // CRITICAL: Forzar actualización inmediata de habitaciones para mostrar info de huésped y cuenta
            // Resetear paginación y forzar re-render completo
            $this->resetPage();
            // Disparar evento para resetear Alpine.js y forzar re-render de componentes
            $this->dispatch('room-view-changed', date: $this->date->toDateString());
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Error: ' . $e->getMessage());
        }
    }

    public function storeQuickRent()
    {
        return $this->submitQuickRent();
    }

    /**
     * Abre el modal para asignar cliente y huéspedes a una reserva activa existente.
     * 
     * CASO DE USO: Completar reserva activa sin cliente principal asignado
     * NO crea nueva reserva, solo completa la existente.
     * 
     * @param int $roomId ID de la habitación
     * @return void
     */
    public function openAssignGuests(int $roomId): void
    {
        try {
            $room = Room::findOrFail($roomId);

            // Obtener stay activa para la fecha seleccionada
            $stay = $room->getAvailabilityService()->getStayForDate($this->date);
            
            if (!$stay || !$stay->reservation) {
                $this->dispatch('notify', type: 'error', message: 'No hay reserva activa para esta habitación.');
                return;
            }

            $reservation = $stay->reservation;

            // Cargar relaciones necesarias
            $reservation->loadMissing([
                'reservationRooms' => function($q) use ($roomId) {
                    $q->where('room_id', $roomId);
                },
                'customer',
                'payments'
            ]);

            // Obtener ReservationRoom para esta habitación
            $reservationRoom = $reservation->reservationRooms->firstWhere('room_id', $roomId);
            
            // Cargar huéspedes adicionales existentes
            $existingAdditionalGuests = [];
            if ($reservationRoom) {
                try {
                    $guestsCollection = $reservationRoom->getGuests();
                    if ($guestsCollection && $guestsCollection->isNotEmpty()) {
                        $existingAdditionalGuests = $guestsCollection->map(function($guest) {
                            return [
                                'customer_id' => $guest->id,
                                'name' => $guest->name,
                                'identification' => $guest->taxProfile?->identification ?? 'N/A',
                            ];
                        })->toArray();
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error loading existing additional guests in openAssignGuests', [
                        'reservation_room_id' => $reservationRoom->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Calcular pagos totales para validar precio mínimo
            $paidAmount = (float)($reservation->payments->where('amount', '>', 0)->sum('amount') ?? 0);

            // Inicializar formulario
            $this->assignGuestsForm = [
                'reservation_id' => $reservation->id,
                'room_id' => $roomId,
                'client_id' => $reservation->client_id, // Puede ser null
                'additional_guests' => $existingAdditionalGuests,
                'override_total_amount' => false,
                'total_amount' => (float)($reservation->total_amount ?? 0), // SSOT actual
                'current_paid_amount' => $paidAmount, // Para validación
                'max_capacity' => (int)($room->max_capacity ?? 1), // 🔐 Para validación de capacidad
            ];

            $this->assignGuestsModal = true;
            $this->dispatch('assignGuestsModalOpened');
        } catch (\Exception $e) {
            \Log::error('Error opening assign guests modal', [
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('notify', type: 'error', message: 'Error al abrir el formulario: ' . $e->getMessage());
        }
    }

    /**
     * Cierra el modal de asignar huéspedes.
     */
    public function closeAssignGuests(): void
    {
        $this->assignGuestsModal = false;
        $this->assignGuestsForm = null;
    }

    /**
     * Completa una reserva activa asignando cliente principal y huéspedes adicionales.
     * 
     * REGLAS CRÍTICAS:
     * - NO crea nueva reserva (usa la existente)
     * - NO modifica stay ni fechas
     * - Cliente principal es OBLIGATORIO
     * - Precio solo se actualiza si override_total_amount = true
     * - Nuevo precio debe ser >= pagos realizados
     * 
     * @return void
     */
    public function submitAssignGuests(): void
    {
        if (!$this->assignGuestsForm) {
            $this->dispatch('notify', type: 'error', message: 'Error: Formulario no inicializado.');
            return;
        }

        try {
            $data = $this->assignGuestsForm;

            DB::transaction(function () use ($data) {
                // ===== PASO 1: Validar y cargar reserva =====
                $reservation = Reservation::lockForUpdate()->findOrFail($data['reservation_id']);

                // Validar que la reserva tiene un stay activo (no permitir modificar reservas liberadas)
                $stay = Stay::where('reservation_id', $reservation->id)
                    ->whereNull('check_out_at')
                    ->whereIn('status', ['active', 'pending_checkout'])
                    ->first();

                if (!$stay) {
                    throw new \RuntimeException('No se puede modificar una reserva que no tiene estadía activa.');
                }

                // ===== PASO 2: Validar y asignar cliente principal (OBLIGATORIO) =====
                // 🔍 DEBUG: Log del valor recibido
                \Log::info('submitAssignGuests: Validating client_id', [
                    'client_id' => $data['client_id'] ?? null,
                    'is_empty' => empty($data['client_id']),
                    'is_numeric' => isset($data['client_id']) ? is_numeric($data['client_id']) : false,
                    'assignGuestsForm' => $this->assignGuestsForm,
                ]);
                
                if (empty($data['client_id']) || !is_numeric($data['client_id']) || $data['client_id'] <= 0) {
                    throw new \RuntimeException('Debe asignar un cliente principal.');
                }

                $customerId = (int)$data['client_id'];
                
                // Verificar que el cliente existe
                $customer = \App\Models\Customer::withoutGlobalScopes()->find($customerId);
                if (!$customer) {
                    throw new \RuntimeException('El cliente seleccionado no existe.');
                }

                // Actualizar cliente principal (puede ser asignación inicial o cambio de cliente)
                // Si ya había un cliente, se actualiza; si no había, se asigna por primera vez
                $oldClientId = $reservation->client_id;
                $reservation->update([
                    'client_id' => $customerId,
                ]);
                
                // 🔄 CRÍTICO: Refrescar la reserva DESPUÉS de actualizar para limpiar caché de Eloquent
                // Esto asegura que las relaciones cargadas después tengan los datos correctos
                $reservation->refresh();
                
                \Log::info('AssignGuests: Client principal updated', [
                    'reservation_id' => $reservation->id,
                    'old_client_id' => $oldClientId,
                    'new_client_id' => $customerId,
                    'client_id_after_refresh' => $reservation->client_id,
                ]);

                // ===== PASO 3: VALIDACIÓN DE CAPACIDAD (CRÍTICO) =====
                // Cargar habitación para obtener max_capacity
                $room = Room::findOrFail($data['room_id']);
                $maxCapacity = (int)($room->max_capacity ?? 1);
                
                // Calcular total de huéspedes: principal (1) + adicionales
                $principalCount = 1; // Cliente principal siempre cuenta
                $additionalGuestsCount = !empty($data['additional_guests']) && is_array($data['additional_guests']) 
                    ? count($data['additional_guests']) 
                    : 0;
                $totalGuests = $principalCount + $additionalGuestsCount;
                
                // Validar que NO se exceda la capacidad máxima
                if ($totalGuests > $maxCapacity) {
                    throw new \RuntimeException(
                        "No se puede confirmar la asignación. La cantidad de huéspedes ({$totalGuests}) excede la capacidad máxima de la habitación ({$maxCapacity} persona" . ($maxCapacity > 1 ? 's' : '') . ")."
                    );
                }

                // ===== PASO 4: Asignar huéspedes adicionales =====
                $reservationRoom = $reservation->reservationRooms()
                    ->where('room_id', $data['room_id'])
                    ->first();

                if (!$reservationRoom) {
                    throw new \RuntimeException('No se encontró la relación reserva-habitación.');
                }

                // Limpiar huéspedes adicionales existentes
                // Primero eliminar de reservation_room_guests
                $existingReservationGuests = DB::table('reservation_guests')
                    ->where('reservation_room_id', $reservationRoom->id)
                    ->get();

                if ($existingReservationGuests->isNotEmpty()) {
                    $existingReservationGuestIds = $existingReservationGuests->pluck('id')->toArray();
                    
                    DB::table('reservation_room_guests')
                        ->where('reservation_room_id', $reservationRoom->id)
                        ->whereIn('reservation_guest_id', $existingReservationGuestIds)
                        ->delete();

                    // Eliminar de reservation_guests (solo los que no son principal)
                    DB::table('reservation_guests')
                        ->where('reservation_room_id', $reservationRoom->id)
                        ->where('is_primary', false)
                        ->delete();
                }

                // Asignar nuevos huéspedes adicionales (si se proporcionaron)
                if (!empty($data['additional_guests']) && is_array($data['additional_guests'])) {
                    $additionalGuestIds = array_filter(
                        array_column($data['additional_guests'], 'customer_id'),
                        fn($id) => !empty($id) && is_numeric($id) && $id > 0
                    );

                    if (!empty($additionalGuestIds)) {
                        $this->assignGuestsToRoom($reservationRoom, $additionalGuestIds);
                    }
                }

                // ===== PASO 4: OPCIONAL - Actualizar total_amount (SSOT) =====
                if (!empty($data['override_total_amount']) && $data['override_total_amount'] === true) {
                    $newTotal = (float)($data['total_amount'] ?? 0);

                    if ($newTotal <= 0) {
                        throw new \RuntimeException('El total del hospedaje debe ser mayor a 0.');
                    }

                    // Validar que el nuevo total no sea menor a lo ya pagado
                    $paidAmount = (float)($data['current_paid_amount'] ?? 0);
                    
                    if ($newTotal < $paidAmount) {
                        throw new \RuntimeException(
                            'El nuevo total del hospedaje no puede ser menor a lo ya pagado ($' . number_format($paidAmount, 0, ',', '.') . ').'
                        );
                    }

                    // Calcular nuevo balance_due
                    $salesDebt = (float)($reservation->sales?->where('is_paid', false)->sum('total') ?? 0);
                    $newBalanceDue = $newTotal - $paidAmount + $salesDebt;

                    // Actualizar total_amount y balance_due
                    $reservation->update([
                        'total_amount' => $newTotal,
                        'balance_due' => max(0, $newBalanceDue),
                    ]);

                    \Log::info('AssignGuests: Total amount updated', [
                        'reservation_id' => $reservation->id,
                        'old_total' => $reservation->getOriginal('total_amount'),
                        'new_total' => $newTotal,
                        'paid_amount' => $paidAmount,
                    ]);
                }

                // ===== PASO 6: Actualizar total_guests en la reserva =====
                $reservation->refresh();
                $reservation->loadMissing(['reservationRooms']);
                $reservationRoom = $reservation->reservationRooms->firstWhere('room_id', $data['room_id']);
                
                if ($reservationRoom) {
                    try {
                        // Calcular total de huéspedes: principal (1) + adicionales
                        $principalCount = 1; // Cliente principal siempre cuenta
                        $additionalGuestsCount = $reservationRoom->getGuests()->count() ?? 0;
                        $totalGuests = $principalCount + $additionalGuestsCount;

                        $reservation->update([
                            'total_guests' => $totalGuests,
                            'adults' => $totalGuests, // Simplificación: todos son adultos
                            'children' => 0,
                        ]);
                    } catch (\Exception $e) {
                        // No crítico, solo log
                        \Log::warning('Error updating total_guests in submitAssignGuests', [
                            'reservation_id' => $reservation->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

            $this->dispatch('notify', type: 'success', message: 'Cliente y huéspedes asignados correctamente.');
            $this->closeAssignGuests();
            
            // 🔄 CRÍTICO: Forzar refresh completo para recargar todas las relaciones desde BD
            // resetPage() re-ejecuta render() que usa getRoomsQuery() con eager loading fresco
            // Esto asegura que room-row.blade.php reciba datos frescos con customer cargado
            $this->resetPage(); // Re-ejecutar render() con datos frescos desde BD
            $this->dispatch('$refresh'); // Forzar re-render de Livewire

        } catch (\Exception $e) {
            \Log::error('Error submitting assign guests', [
                'form_data' => $this->assignGuestsForm ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('notify', type: 'error', message: 'Error al asignar huéspedes: ' . $e->getMessage());
        }
    }

    /**
     * Agrega un huésped adicional al formulario de asignación.
     * 
     * @param int $customerId ID del cliente a agregar
     * @return void
     */
    public function addAssignGuest(int $customerId): void
    {
        if (!$this->assignGuestsForm) {
            return;
        }

        try {
            $customer = \App\Models\Customer::withoutGlobalScopes()->find($customerId);
            if (!$customer) {
                $this->dispatch('notify', type: 'error', message: 'Cliente no encontrado.');
                return;
            }

            // Inicializar array si no existe
            if (!isset($this->assignGuestsForm['additional_guests']) || !is_array($this->assignGuestsForm['additional_guests'])) {
                $this->assignGuestsForm['additional_guests'] = [];
            }

            // Verificar duplicados
            foreach ($this->assignGuestsForm['additional_guests'] as $guest) {
                if (isset($guest['customer_id']) && (int)$guest['customer_id'] === $customerId) {
                    $this->dispatch('notify', type: 'warning', message: 'Este cliente ya está agregado como huésped adicional.');
                    return;
                }
            }

            // Verificar que no sea el cliente principal
            if (isset($this->assignGuestsForm['client_id']) && (int)$this->assignGuestsForm['client_id'] === $customerId) {
                $this->dispatch('notify', type: 'warning', message: 'Este cliente ya está asignado como cliente principal.');
                return;
            }

            // 🔐 VALIDACIÓN CRÍTICA: Verificar capacidad ANTES de agregar huésped adicional
            $principalCount = !empty($this->assignGuestsForm['client_id']) ? 1 : 0;
            $currentAdditionalCount = count($this->assignGuestsForm['additional_guests'] ?? []);
            $totalAfterAdd = $principalCount + $currentAdditionalCount + 1;
            $maxCapacity = (int)($this->assignGuestsForm['max_capacity'] ?? 1);

            if ($totalAfterAdd > $maxCapacity) {
                $this->dispatch('notify', type: 'error', message: "No se puede agregar más huéspedes. La habitación tiene capacidad máxima de {$maxCapacity} persona" . ($maxCapacity > 1 ? 's' : '') . ".");
                return;
            }

            // Agregar huésped
            $this->assignGuestsForm['additional_guests'][] = [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'identification' => $customer->taxProfile?->identification ?? 'N/A',
            ];

            $this->dispatch('notify', type: 'success', message: 'Huésped adicional agregado.');
        } catch (\Exception $e) {
            \Log::error('Error adding assign guest', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('notify', type: 'error', message: 'Error al agregar huésped: ' . $e->getMessage());
        }
    }

    /**
     * Abre el modal de historial diario de liberaciones de una habitación.
     * 
     * CONCEPTO: Muestra TODAS las liberaciones que ocurrieron en un día específico
     * (por defecto HOY) desde room_release_history (auditoría inmutable).
     * 
     * DIFERENCIA CON openRoomDetail():
     * - openRoomDetail(): Estado operativo actual (stays/reservations activas)
     * - openRoomDailyHistory(): Historial histórico cerrado (room_release_history)
     * 
     * @param int $roomId ID de la habitación
     * @return void
     */
    public function openRoomDailyHistory(int $roomId): void
    {
        try {
            $room = Room::findOrFail($roomId);
            $date = $this->date->toDateString(); // Fecha seleccionada (HOY por defecto)

            // Obtener TODAS las liberaciones de esta habitación en el día seleccionado
            // 🔧 QUERY DEFENSIVA: Usa release_date como principal, created_at como fallback
            // Esto garantiza que registros con release_date NULL o mal guardado no se pierdan
            $releases = RoomReleaseHistory::where('room_id', $roomId)
                ->where(function ($q) use ($date) {
                    // Prioridad 1: release_date (SSOT principal) - si existe y coincide
                    $q->where(function($subQ) use ($date) {
                        $subQ->whereNotNull('release_date')
                             ->whereDate('release_date', $date);
                    })
                    // Prioridad 2: created_at (fallback para registros con release_date NULL)
                    ->orWhere(function($subQ) use ($date) {
                        $subQ->whereNull('release_date')
                             ->whereDate('created_at', $date);
                    });
                })
                ->with('releasedBy')
                ->orderBy('created_at', 'desc') // Más recientes primero (última liberación arriba)
                ->get();
            
            // 🔍 DEBUG: Log de la query para verificar qué se encontró
            \Log::info('Room daily history query executed', [
                'room_id' => $roomId,
                'date_filter' => $date,
                'releases_found' => $releases->count(),
                'releases_debug' => $releases->map(function($r) {
                    return [
                        'id' => $r->id,
                        'release_date' => $r->release_date?->toDateString(),
                        'created_at' => $r->created_at->toDateString(),
                        'customer' => $r->customer_name,
                    ];
                })->toArray(),
            ]);

            // Preparar datos para el modal
            $this->roomDailyHistoryData = [
                'room' => [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                ],
                'date' => $date,
                'date_formatted' => $this->date->format('d/m/Y'),
                'total_releases' => $releases->count(),
                'releases' => $releases->map(function ($release) {
                    // Determinar estado de la cuenta
                    $isPaid = (float)$release->pending_amount <= 0.01; // Tolerancia para floats
                    $hasConsumptions = (float)$release->consumptions_total > 0;
                    
                    return [
                        'id' => $release->id,
                        'released_at' => $release->created_at->format('H:i'),
                        'released_at_full' => $release->created_at->format('d/m/Y H:i'),
                        // ✅ SIEMPRE MOSTRAR - nunca ocultar por falta de cliente
                        'customer_name' => $release->customer_name ?: 'Sin huésped asignado', // ✅ Fallback semántico
                        'customer_identification' => $release->customer_identification ?: 'N/A',
                        'guests_count' => $release->guests_count ?? 0,

                        // Datos financieros
                        'total_amount' => (float)$release->total_amount,
                        'deposit' => (float)$release->deposit,
                        'consumptions_total' => (float)$release->consumptions_total,
                        'pending_amount' => (float)$release->pending_amount,
                        'is_paid' => $isPaid,
                        'has_consumptions' => $hasConsumptions,

                        // Snapshot (JSON deserializado)
                        'guests_data' => $release->guests_data ?? [],
                        'sales_data' => $release->sales_data ?? [],
                        'deposits_data' => $release->deposits_data ?? [],

                        // Operación
                        'released_by' => $release->releasedBy?->name ?? 'Sistema',
                        'target_status' => $release->target_status,
                        'check_in_date' => $release->check_in_date?->format('d/m/Y'),
                        'check_out_date' => $release->check_out_date?->format('d/m/Y'),
                    ];
                })->toArray(),
            ];

            $this->roomDailyHistoryModal = true;
        } catch (\Exception $e) {
            \Log::error('Error opening room daily history', [
                'room_id' => $roomId,
                'date' => $this->date->toDateString(),
                'error' => $e->getMessage()
            ]);
            $this->dispatch('notify', type: 'error', message: 'Error al cargar historial: ' . $e->getMessage());
        }
    }

    /**
     * Cierra el modal de historial diario.
     */
    public function closeRoomDailyHistory(): void
    {
        $this->roomDailyHistoryModal = false;
        $this->roomDailyHistoryData = null;
    }

    /**
     * Elimina un huésped adicional del formulario de asignación.
     * 
     * @param int $index Índice del huésped en el array
     * @return void
     */
    public function removeAssignGuest(int $index): void
    {
        if (!$this->assignGuestsForm || !isset($this->assignGuestsForm['additional_guests'][$index])) {
            return;
        }

        unset($this->assignGuestsForm['additional_guests'][$index]);
        $this->assignGuestsForm['additional_guests'] = array_values($this->assignGuestsForm['additional_guests']);
        $this->dispatch('notify', type: 'success', message: 'Huésped removido.');
    }

    /**
     * Assign guests to a specific reservation room.
     * 
     * SINGLE SOURCE OF TRUTH:
     * - Huésped principal: reservations.client_id
     * - Huéspedes adicionales: reservation_guests + reservation_room_guests
     * 
     * Esta lógica es IDÉNTICA a ReservationController::assignGuestsToRoom()
     * para mantener consistencia arquitectónica.
     * 
     * @param ReservationRoom $reservationRoom
     * @param array $assignedGuestIds Array de customer IDs
     * @return void
     */
    private function assignGuestsToRoom(ReservationRoom $reservationRoom, array $assignedGuestIds): void
    {
        if (empty($assignedGuestIds)) {
            return;
        }

        // Filter valid guest IDs
        $validGuestIds = array_filter($assignedGuestIds, function ($id): bool {
            return !empty($id) && is_numeric($id) && $id > 0;
        });

        if (empty($validGuestIds)) {
            return;
        }

        // Verify guests exist in database
        $validGuestIds = \App\Models\Customer::withoutGlobalScopes()
            ->whereIn('id', $validGuestIds)
            ->pluck('id')
            ->toArray();

        if (empty($validGuestIds)) {
            return;
        }

        try {
            // Estructura de BD:
            // reservation_guests: id, reservation_room_id, guest_id, is_primary
            // reservation_room_guests: id, reservation_room_id, reservation_guest_id
            
            foreach ($validGuestIds as $guestId) {
                // Verificar si ya existe el registro en reservation_guests
                $existingReservationGuest = DB::table('reservation_guests')
                    ->where('reservation_room_id', $reservationRoom->id)
                    ->where('guest_id', $guestId)
                    ->first();
                
                if ($existingReservationGuest) {
                    // Ya existe, verificar si está en reservation_room_guests
                    $existingRoomGuest = DB::table('reservation_room_guests')
                        ->where('reservation_room_id', $reservationRoom->id)
                        ->where('reservation_guest_id', $existingReservationGuest->id)
                        ->first();
                    
                    if (!$existingRoomGuest) {
                        // Crear solo el registro en reservation_room_guests
                        DB::table('reservation_room_guests')->insert([
                            'reservation_room_id' => $reservationRoom->id,
                            'reservation_guest_id' => $existingReservationGuest->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    // Crear registro en reservation_guests
                    $reservationGuestId = DB::table('reservation_guests')->insertGetId([
                        'reservation_room_id' => $reservationRoom->id,
                        'guest_id' => $guestId,
                        'is_primary' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    // Crear registro en reservation_room_guests
                    DB::table('reservation_room_guests')->insert([
                        'reservation_room_id' => $reservationRoom->id,
                        'reservation_guest_id' => $reservationGuestId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error assigning guests to room in Quick Rent', [
                'reservation_room_id' => $reservationRoom->id,
                'guest_ids' => $validGuestIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
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

    public function viewReleaseHistoryDetail($historyId)
    {
        $history = RoomReleaseHistory::with(['room', 'customer', 'releasedBy'])->find($historyId);
        if ($history) {
            // Convertir el objeto a array para compatibilidad con Livewire
            // Incluir también el nombre del usuario que liberó
            $historyArray = $history->toArray();
            $historyArray['released_by_name'] = $history->releasedBy?->name ?? 'N/A';
            $this->releaseHistoryDetail = $historyArray;
            $this->releaseHistoryDetailModal = true;
        } else {
            $this->dispatch('notify', type: 'error', message: 'Registro de historial no encontrado.');
        }
    }
    
    public function openReleaseHistoryDetail($roomId)
    {
        $room = Room::find($roomId);
        if ($room) {
            $this->releaseHistoryDetail = [
                'room' => $room,
                'history' => collect([]),
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
        $this->detailData = null;
        $this->dispatch('close-room-release-modal');
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

            // ✅ NUEVO SSOT: Total del hospedaje desde stay_nights si existe
            try {
                $totalHospedaje = (float)\App\Models\StayNight::where('reservation_id', $activeReservation->id)
                    ->sum('price');
                
                // Si no hay noches, usar fallback
                if ($totalHospedaje <= 0) {
                    $totalHospedaje = (float)($activeReservation->total_amount ?? 0);
                }
            } catch (\Exception $e) {
                // Si falla (tabla no existe), usar fallback
                $totalHospedaje = (float)($activeReservation->total_amount ?? 0);
            }

            // Usar suma real de pagos positivos (SSOT financiero), no deposit_amount que puede estar desactualizado
            $totalPaidPositive = (float)($payments->where('amount', '>', 0)->sum('amount') ?? 0);
            $abonoRealizado = $totalPaidPositive > 0 ? $totalPaidPositive : (float)($activeReservation->deposit_amount ?? 0);
            
            // Devoluciones (solo negativos, valor absoluto)
            $refundsTotal = abs((float)($payments->where('amount', '<', 0)->sum('amount') ?? 0));
            
            $salesTotal = (float)($sales->sum('total') ?? 0);
            $salesDebt = (float)($sales->where('is_paid', false)->sum('total') ?? 0);
            
            // ===== REGLA HOTELERA CRÍTICA: Calcular deuda solo si NO hay stay activa =====
            // REGLA: Mientras la habitación esté OCUPADA, pagos > total_hospedaje es PAGO ADELANTADO, NO saldo a favor
            // Solo se evalúa saldo a favor cuando stay.status = finished (checkout completado)
            $hasActiveStay = \App\Models\Stay::where('reservation_id', $activeReservation->id)
                ->whereNull('check_out_at')
                ->whereIn('status', ['active', 'pending_checkout'])
                ->exists();

            if ($hasActiveStay) {
                // ===== HABITACIÓN OCUPADA: Calcular deuda normal =====
                // Fórmula: deuda = (hospedaje - abonos_reales) + devoluciones + consumos_pendientes
                // Si totalPaid > total_hospedaje, totalDebt será NEGATIVO (pago adelantado)
                // PERO NO es "saldo a favor" - es crédito para noches futuras/consumos
                $totalDebt = ($totalHospedaje - $totalPaidPositive) + $refundsTotal + $salesDebt;
                // ✅ totalDebt < 0 = Pago adelantado (válido mientras esté ocupada)
                // ✅ totalDebt > 0 = Deuda pendiente
                // ✅ totalDebt = 0 = Al día
            } else {
                // ===== HABITACIÓN LIBERADA: Evaluar saldo a favor real =====
                // Aquí sí se evalúa si hay overpaid (saldo a favor) después de cerrar la estadía
                $overpaid = $totalPaidPositive - $totalHospedaje;
                if ($overpaid > 0) {
                    // Hay saldo a favor real (habrá que devolver)
                    $totalDebt = -$overpaid + $refundsTotal + $salesDebt;  // Negativo = se le debe
                } else {
                    // No hay saldo a favor o hay deuda pendiente
                    $totalDebt = abs($overpaid) + $refundsTotal + $salesDebt;
                }
            }
            
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

            // Validar que no sea fecha histórica - usando lógica de HotelTime
            $today = Carbon::today();
            $selectedDate = $this->date ?? $today;
            
            // 🔥 PERMITIR cambios en fecha actual (hoy)
            if ($selectedDate->copy()->startOfDay()->lt($today)) {
                $this->dispatch('notify', type: 'error', message: 'No se pueden hacer cambios en fechas históricas.');
                return;
            }
            
            // 🔥 DEBUG: Log para verificar qué fecha se está usando
            \Log::info('updateCleaningStatus', [
                'room_id' => $roomId,
                'status' => $status,
                'selectedDate' => $selectedDate->format('Y-m-d'),
                'today' => $today->format('Y-m-d'),
                'isPast' => $selectedDate->copy()->startOfDay()->lt($today)
            ]);

            // Validar que el estado sea válido
            if (!in_array($status, ['limpia', 'pendiente'])) {
                $this->dispatch('notify', type: 'error', message: 'Estado de limpieza inválido.');
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
            
            // Refrescar habitaciones para actualizar la vista
            $this->loadRooms();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Error al actualizar estado de limpieza: ' . $e->getMessage());
            \Log::error('Error updating cleaning status', [
                'room_id' => $roomId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
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
     * Libera la habitación (checkout).
     * 
     * Flujo:
     * 1. Si hay deuda pendiente:
     *    - Valida que el usuario haya confirmado (en frontend)
     *    - Registra un pago para saldarlo
     * 2. Cierra el stay usando getStayForDate(today)
     * 3. Actualiza el estado de la reserva
     * 
     * REGLA: Solo libera si el balance queda en 0
     * 
     * @param int $roomId
     * @param string|null $status Ej: 'libre'
     */
    public function releaseRoom($roomId, $status = null, $paymentMethod = null, $bankName = null, $reference = null)
    {
        $started = false;
        try {
            $this->isReleasingRoom = true;
            $room = Room::find($roomId);
            if (!$room) {
                $this->dispatch('notify', type: 'error', message: 'Habitación no encontrada.');
                $this->isReleasingRoom = false;
                return;
            }

            $this->dispatch('room-release-start', roomId: $roomId);
            $started = true;

            $availabilityService = $room->getAvailabilityService();
            $today = Carbon::today();
            
            // BLOQUEO: No se puede liberar ocupaciones históricas
            if ($availabilityService->isHistoricDate($today)) {
                $this->dispatch('notify', type: 'error', message: 'No se pueden hacer cambios en fechas históricas.');
                if ($started) {
                    $this->dispatch('room-release-finished', roomId: $roomId);
                }
                return;
            }

            // ===== PASO 1: Obtener el stay que intersecta HOY =====
            $activeStay = $availabilityService->getStayForDate($today);

            if (!$activeStay) {
                $this->dispatch('notify', type: 'info', message: 'No hay ocupación activa para liberar hoy.');
                if ($started) {
                    $this->dispatch('room-release-finished', roomId: $roomId);
                }
                $this->closeRoomReleaseConfirmation();
                return;
            }

            // ===== PASO 2: Obtener reserva y calcular deuda REAL desde SSOT =====
            $reservation = $activeStay->reservation;
            if (!$reservation) {
                $this->dispatch('notify', type: 'error', message: 'La ocupación no tiene reserva asociada.');
                if ($started) {
                    $this->dispatch('room-release-finished', roomId: $roomId);
                }
                return;
            }

            // 🔁 RECALCULAR TODA LA DEUDA REAL DESDE SSOT
            $reservation->load(['payments', 'sales']);
            
            // ✅ NUEVO SSOT: Total del hospedaje desde stay_nights
            try {
                $totalHospedaje = (float)\App\Models\StayNight::where('reservation_id', $reservation->id)
                    ->sum('price');
                
                // Si no hay noches aún, usar fallback
                if ($totalHospedaje <= 0) {
                    $totalHospedaje = (float)($reservation->total_amount ?? 0);
                }
            } catch (\Exception $e) {
                // Si falla (tabla no existe), usar fallback
                $totalHospedaje = (float)($reservation->total_amount ?? 0);
            }
            
            // SOLO pagos positivos (SSOT financiero)
            $totalPaid = (float)($reservation->payments
                ->where('amount', '>', 0)
                ->sum('amount') ?? 0);
            
            // SOLO devoluciones (valores negativos en payments)
            $totalRefunds = abs((float)($reservation->payments
                ->where('amount', '<', 0)
                ->sum('amount') ?? 0));
            
            // Consumos NO pagados
            $totalSalesDebt = (float)($reservation->sales
                ->where('is_paid', false)
                ->sum('total') ?? 0);
            
            // 🔴 DEUDA REAL TOTAL
            $realDebt = ($totalHospedaje - $totalPaid) + $totalRefunds + $totalSalesDebt;

            // ===== PASO 3: Si hay deuda, pagarla COMPLETA =====
            if ($realDebt > 0) {
                // Requiere datos de pago desde frontend
                if (!$paymentMethod) {
                    $this->dispatch('notify', type: 'error', message: 'Debe seleccionar un método de pago.');
                    if ($started) {
                        $this->dispatch('room-release-finished', roomId: $roomId);
                    }
                    return;
                }

                $paymentMethodId = $this->getPaymentMethodId($paymentMethod) ?? DB::table('payments_methods')
                    ->where('name', 'Efectivo')
                    ->orWhere('code', 'cash')
                    ->value('id');

                // ✅ Pagar TODO lo pendiente
                Payment::create([
                    'reservation_id' => $reservation->id,
                    'amount' => $realDebt,  // ✅ TODO lo que faltaba
                    'payment_method_id' => $paymentMethodId,
                    'bank_name' => $paymentMethod === 'transferencia' ? ($bankName ?: null) : null,
                    'reference' => $paymentMethod === 'transferencia' 
                        ? ($reference ?: null) 
                        : 'Pago total en liberación',
                    'paid_at' => now(),
                    'created_by' => auth()->id(),
                ]);

                // ===== PASO 3.5: Marcar consumos como pagados =====
                if ($totalSalesDebt > 0) {
                    $reservation->sales()
                        ->where('is_paid', false)
                        ->update(['is_paid' => true]);
                }
            }

            // ===== PASO 4: REVALIDAR que balance sea 0 (OBLIGATORIO) =====
            $reservation->refresh()->load(['payments', 'sales']);
            
            // Recalcular desde BD después de pagos y marcar consumos
            $finalPaid = (float)($reservation->payments
                ->where('amount', '>', 0)
                ->sum('amount') ?? 0);
            
            $finalSalesDebt = (float)($reservation->sales
                ->where('is_paid', false)
                ->sum('total') ?? 0);
            
            $finalRefunds = abs((float)($reservation->payments
                ->where('amount', '<', 0)
                ->sum('amount') ?? 0));
            
            $finalBalance = ($totalHospedaje - $finalPaid) + $finalRefunds + $finalSalesDebt;
            
            // 🔒 VALIDACIÓN DEFENSIVA: No liberar si balance != 0
            if (abs($finalBalance) > 0.01) { // Tolerancia para floats
                $this->dispatch('notify', type: 'error', message: "Error crítico: No se puede liberar con saldo pendiente. Balance: \${$finalBalance}");
                \Log::error('Release Room: Attempted to release with non-zero balance', [
                    'room_id' => $roomId,
                    'reservation_id' => $reservation->id,
                    'final_balance' => $finalBalance,
                    'total_hospedaje' => $totalHospedaje,
                    'final_paid' => $finalPaid,
                    'final_sales_debt' => $finalSalesDebt,
                    'final_refunds' => $finalRefunds,
                ]);
                if ($started) {
                    $this->dispatch('room-release-finished', roomId: $roomId);
                }
                return;
            }

            // ===== PASO 5: Marcar TODAS las noches como pagadas =====
            // 🔥 CRÍTICO: Al liberar, todas las noches hasta HOY quedan pagadas
            // 🔐 PROTECCIÓN: Solo marcar noches hasta hoy (evitar pagar noches futuras accidentalmente)
            try {
                \App\Models\StayNight::where('reservation_id', $reservation->id)
                    ->where('date', '<=', now()->toDateString()) // Solo noches hasta hoy
                    ->where('is_paid', false)
                    ->update(['is_paid' => true]);
            } catch (\Exception $e) {
                // No crítico, solo log (si la tabla no existe aún, continuar)
                \Log::warning('Error marking nights as paid in releaseRoom', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage()
                ]);
            }

            // ===== PASO 6: Cerrar la STAY =====
            $activeStay->update([
                'check_out_at' => now(),
                'status' => 'finished',
            ]);

            // ===== PASO 7: Actualizar estado de la reserva =====
            $reservation->balance_due = 0;
            $reservation->payment_status_id = DB::table('payment_statuses')
                ->where('code', 'paid')
                ->value('id');
            $reservation->save();

            // ===== PASO 8: Crear registro en historial de liberación =====
            try {
                // Cargar relaciones necesarias (NO cargar 'guests' porque la relación está rota)
                $reservation->loadMissing([
                    'customer.taxProfile', 
                    'sales.product', 
                    'payments.paymentMethod',
                    'reservationRooms'
                ]);
                
                // ===== CALCULAR TOTALES (SSOT FINANCIERO) =====
                // ✅ NUEVO SSOT: Calcular desde stay_nights si existe
                try {
                    $totalAmount = (float)\App\Models\StayNight::where('reservation_id', $reservation->id)
                        ->sum('price');
                    
                    // Si no hay noches aún, usar fallback
                    if ($totalAmount <= 0) {
                        $totalAmount = (float)($reservation->total_amount ?? 0);
                    }
                } catch (\Exception $e) {
                    // Si falla (tabla no existe), usar fallback
                    $totalAmount = (float)($reservation->total_amount ?? 0);
                }
                
                // VALIDACIÓN CRÍTICA: Verificar que totalAmount existe y es válido
                if ($totalAmount <= 0) {
                    \Log::error('Release Room: totalAmount is 0 or null', [
                        'reservation_id' => $reservation->id,
                        'total_amount' => $reservation->total_amount,
                        'room_id' => $room->id,
                    ]);
                    // NO lanzar excepción para no bloquear el release, pero loguear el error
                    // Usar fallback: calcular desde ReservationRoom si existe
                    $reservationRoom = $reservation->reservationRooms->where('room_id', $room->id)->first();
                    if ($reservationRoom && $reservationRoom->price_per_night > 0) {
                        $nights = $reservationRoom->nights ?? 1;
                        $totalAmount = (float)($reservationRoom->price_per_night * $nights);
                        \Log::warning('Release Room: Using fallback totalAmount from ReservationRoom', [
                            'reservation_id' => $reservation->id,
                            'fallback_total' => $totalAmount,
                        ]);
                    }
                }
                
                // 🔁 RECALCULAR TOTALES FINALES DESPUÉS DE PAGOS (SSOT)
                // Asegurar que tenemos los datos más recientes desde BD
                $reservation->refresh()->load(['payments', 'sales']);
                
                // Pagos finales (SOLO positivos)
                $finalPaidAmount = (float)($reservation->payments
                    ->where('amount', '>', 0)
                    ->sum('amount') ?? 0);
                
                // Consumos totales (todos)
                $consumptionsTotal = (float)($reservation->sales->sum('total') ?? 0);
                
                // Consumos pendientes (debe ser 0 después de marcar como pagados)
                $consumptionsPending = (float)($reservation->sales
                    ->where('is_paid', false)
                    ->sum('total') ?? 0);
                
                // 🔒 VALIDACIÓN: Consumos pendientes debe ser 0
                if ($consumptionsPending > 0.01) {
                    \Log::warning('Release Room: Some sales still unpaid after marking as paid', [
                        'reservation_id' => $reservation->id,
                        'consumptions_pending' => $consumptionsPending,
                    ]);
                }
                
                // Obtener ReservationRoom para fechas
                $reservationRoom = $reservation->reservationRooms
                    ->where('room_id', $room->id)
                    ->first();
                
                $checkInDate = $reservationRoom 
                    ? Carbon::parse($reservationRoom->check_in_date) 
                    : ($reservation->check_in_date 
                        ? Carbon::parse($reservation->check_in_date) 
                        : Carbon::parse($activeStay->check_in_at));
                $checkOutDate = $reservationRoom 
                    ? Carbon::parse($reservationRoom->check_out_date) 
                    : ($reservation->check_out_date 
                        ? Carbon::parse($reservation->check_out_date) 
                        : $today);
                
                // 🔒 REGLA ABSOLUTA: pending_amount SIEMPRE debe ser 0 al liberar
                // El snapshot refleja el estado FINAL (cerrado)
                $pendingAmount = 0;
                
                // Determinar target_status basado en el parámetro o estado de limpieza
                $targetStatus = $status ?? 'libre';
                if (!$status) {
                    // Si no se especifica, verificar estado de limpieza
                    $cleaningStatus = $room->cleaningStatus($today);
                    if ($cleaningStatus === 'pendiente') {
                        $targetStatus = 'pendiente_aseo';
                    } elseif ($cleaningStatus === 'limpia') {
                        $targetStatus = 'limpia';
                    } else {
                        $targetStatus = 'libre';
                    }
                }
                
                // Preparar datos de huéspedes
                // Obtener huéspedes desde reservation_guests usando reservation_room_id
                $guestsData = [];
                
                // Cliente principal
                if ($reservation->customer) {
                    $guestsData[] = [
                        'id' => $reservation->customer->id,
                        'name' => $reservation->customer->name,
                        'identification' => $reservation->customer->taxProfile?->identification,
                        'is_main' => true,
                    ];
                }
                
                // Obtener huéspedes adicionales desde reservation_guests usando reservation_room_id
                if ($reservationRoom) {
                    try {
                        // Verificar si la tabla tax_profiles existe
                        $hasTaxProfiles = Schema::hasTable('tax_profiles');
                        
                        if ($hasTaxProfiles) {
                            $additionalGuests = DB::table('reservation_guests')
                                ->where('reservation_room_id', $reservationRoom->id)
                                ->join('customers', 'reservation_guests.guest_id', '=', 'customers.id')
                                ->leftJoin('tax_profiles', 'customers.id', '=', 'tax_profiles.customer_id')
                                ->select('customers.id', 'customers.name', 'tax_profiles.identification')
                                ->get();
                        } else {
                            // Si no existe tax_profiles, solo obtener datos básicos
                            $additionalGuests = DB::table('reservation_guests')
                                ->where('reservation_room_id', $reservationRoom->id)
                                ->join('customers', 'reservation_guests.guest_id', '=', 'customers.id')
                                ->select('customers.id', 'customers.name')
                                ->get();
                        }
                        
                        foreach ($additionalGuests as $guest) {
                            $guestsData[] = [
                                'id' => $guest->id,
                                'name' => $guest->name,
                                'identification' => $guest->identification ?? null,
                                'is_main' => false,
                            ];
                        }
                    } catch (\Exception $e) {
                        // Si falla la consulta de huéspedes, continuar sin ellos
                        \Log::warning('Error loading additional guests for release history', [
                            'reservation_room_id' => $reservationRoom->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                // 🔥 CRÍTICO: Asegurar que release_date sea la fecha real de liberación (SSOT para historial diario)
                // NO confiar en defaults ni Carbon automático - SETEARLO EXPLÍCITAMENTE
                $releaseDate = $today->toDateString(); // Fecha actual (HOY) - SSOT para historial diario
                
                // 🔐 CUSTOMER: Puede ser NULL (walk-in sin asignar)
                // NO asumir que siempre existe customer - usar null-safe operator
                $customer = $reservation->customer; // puede ser null
                
                // Crear registro de historial (snapshot FINAL)
                $historyData = [
                    'room_id' => $room->id,
                    'reservation_id' => $reservation->id,
                    'customer_id' => $customer?->id, // ✅ puede ser null
                    'released_by' => auth()->id(),
                    'room_number' => $room->room_number,
                    // 💰 FINANCIEROS FINALES (SSOT)
                    'total_amount' => $totalAmount,
                    'deposit' => $finalPaidAmount,  // ✅ Pagos finales después de pago automático
                    'consumptions_total' => $consumptionsTotal,
                    'pending_amount' => 0,  // 🔒 SIEMPRE 0 al liberar (cuenta cerrada)
                    'guests_count' => $reservation->total_guests ?? count($guestsData) ?: 1,
                    'check_in_date' => $checkInDate->toDateString(),
                    'check_out_date' => $checkOutDate->toDateString(),
                    // 🔥 CRÍTICO: release_date DEBE ser la fecha real de liberación (SSOT para historial diario)
                    'release_date' => $releaseDate,  // ✅ Seteado explícitamente con fecha actual
                    'target_status' => $targetStatus,
                    // 🔐 DATOS DENORMALIZADOS (NO obligatorios) - siempre con placeholder semántico si no hay cliente
                    'customer_name' => $customer?->name ?? 'Sin huésped asignado', // ✅ Nunca NULL, siempre placeholder
                    'customer_identification' => $customer?->taxProfile?->identification ?? null,
                    'customer_phone' => $customer?->phone ?? null,
                    'customer_email' => $customer?->email ?? null,
                    'reservation_data' => [
                        'id' => $reservation->id,
                        'reservation_code' => $reservation->reservation_code,
                        'client_id' => $reservation->client_id,
                        'status_id' => $reservation->status_id,
                        'total_guests' => $reservation->total_guests,
                        'adults' => $reservation->adults,
                        'children' => $reservation->children,
                        'total_amount' => (float)($reservation->total_amount ?? 0),
                        'deposit_amount' => (float)($reservation->deposit_amount ?? 0),
                        'balance_due' => (float)($reservation->balance_due ?? 0),
                        'payment_status_id' => $reservation->payment_status_id,
                        'source_id' => $reservation->source_id,
                        'created_by' => $reservation->created_by,
                        'notes' => $reservation->notes,
                        'created_at' => $reservation->created_at?->toDateTimeString(),
                        'updated_at' => $reservation->updated_at?->toDateTimeString(),
                    ],
                    'sales_data' => $reservation->sales->map(function($sale) {
                        return [
                            'id' => $sale->id,
                            'product_id' => $sale->product_id,
                            'product_name' => $sale->product->name ?? 'N/A',
                            'quantity' => $sale->quantity,
                            'unit_price' => (float)($sale->unit_price ?? 0),
                            'total' => (float)($sale->total ?? 0),
                            'is_paid' => (bool)($sale->is_paid ?? false),
                        ];
                    })->toArray(),
                    'deposits_data' => $reservation->payments->map(function($payment) {
                        return [
                            'id' => $payment->id,
                            'amount' => (float)($payment->amount ?? 0),
                            'payment_method' => $payment->paymentMethod->name ?? 'N/A',
                            'paid_at' => $payment->paid_at?->toDateString(),
                        ];
                    })->toArray(),
                    'guests_data' => $guestsData,
                ];
                
                // 🔍 VALIDACIÓN PRE-CREACIÓN: Verificar que release_date no sea NULL
                if (empty($historyData['release_date']) || $historyData['release_date'] === null) {
                    \Log::error('CRITICAL: release_date is NULL before creating RoomReleaseHistory', [
                        'room_id' => $room->id,
                        'reservation_id' => $reservation->id,
                        'today' => $today->toDateString(),
                        'releaseDate' => $releaseDate,
                    ]);
                    // Forzar fecha actual como fallback absoluto
                    $historyData['release_date'] = now()->toDateString();
                }
                
                // 🔍 DEBUG: Verificar datos antes de crear
                \Log::info('Creating room release history', [
                    'room_id' => $room->id,
                    'reservation_id' => $reservation->id,
                    'release_date_BEFORE' => $historyData['release_date'] ?? 'NULL',
                    'today' => $today->toDateString(),
                    'releaseDate_var' => $releaseDate ?? 'NULL',
                ]);
                
                $releaseHistory = RoomReleaseHistory::create($historyData);
                
                // 🔍 DEBUG: Verificar datos después de crear
                $releaseHistory->refresh();
                \Log::info('Room release history created successfully', [
                    'room_id' => $room->id,
                    'reservation_id' => $reservation->id,
                    'history_id' => $releaseHistory->id,
                    'release_date_SAVED' => $releaseHistory->release_date?->toDateString(), // ✅ Verificar que se guardó correctamente
                    'created_at' => $releaseHistory->created_at->toDateString(),
                    'release_date_IN_DB' => DB::table('room_release_history')->where('id', $releaseHistory->id)->value('release_date'),
                    'target_status' => $targetStatus,
                ]);
            } catch (\Exception $e) {
                // No fallar la liberación si falla el historial, solo loguear
                \Log::error('Error creating room release history', [
                    'room_id' => $room->id,
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $this->dispatch('notify', type: 'success', message: 'Habitación liberada correctamente.');
            if ($started) {
                $this->dispatch('room-release-finished', roomId: $roomId);
            }
            $this->isReleasingRoom = false;
            $this->closeRoomReleaseConfirmation();
            $this->dispatch('refreshRooms');
        } catch (\Exception $e) {
            if ($started) {
                $this->dispatch('room-release-finished', roomId: $roomId);
            }
            $this->isReleasingRoom = false;
            $this->dispatch('notify', type: 'error', message: 'Error al liberar habitación: ' . $e->getMessage());
            \Log::error('Error releasing room: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * Abrir modal para editar precios de una reservation
     */
    public function openEditPrices($reservationId)
    {
        try {
            \Log::error('🔥 openEditPrices llamado con reservationId: ' . $reservationId);
            
            $reservation = \App\Models\Reservation::with(['stayNights'])->findOrFail($reservationId);
            
            \Log::error('📋 Reservation encontrada:', [
                'id' => $reservation->id,
                'total_amount' => $reservation->total_amount,
                'stay_nights_count' => $reservation->stayNights->count()
            ]);
            
            // Preparar datos del formulario
            $this->editPricesForm = [
                'id' => $reservation->id,
                'total_amount' => (float)$reservation->total_amount,
                'nights' => []
            ];
            
            // Cargar noches existentes
            $stayNights = $reservation->stayNights;
            \Log::error('🌙 StayNights cargados: ' . $stayNights->count());
            
            // Si no hay stay_nights, intentar crearlos automáticamente
            if ($stayNights->isEmpty() && $reservation->check_in_date && $reservation->check_out_date) {
                \Log::error('🔥 Creando stay_nights automáticamente para reservation ' . $reservation->id);
                
                $checkIn = \Carbon\Carbon::parse($reservation->check_in_date);
                $checkOut = \Carbon\Carbon::parse($reservation->check_out_date);
                $nights = $checkIn->diffInDays($checkOut);
                
                for ($i = 0; $i < $nights; $i++) {
                    $nightDate = $checkIn->copy()->addDays($i);
                    $nightPrice = $reservation->total_amount / $nights; // Distribuir total_amount entre las noches
                    
                    $stayNight = new \App\Models\StayNight();
                    $stayNight->reservation_id = $reservation->id;
                    $stayNight->date = $nightDate;
                    $stayNight->price = $nightPrice;
                    $stayNight->is_paid = false;
                    $stayNight->created_at = now();
                    $stayNight->updated_at = now();
                    $stayNight->save();
                    
                    $this->editPricesForm['nights'][] = [
                        'id' => $stayNight->id,
                        'date' => $nightDate->format('Y-m-d'),
                        'price' => (float)$nightPrice,
                        'is_paid' => false
                    ];
                    
                    \Log::error('🌙 Noche creada:', [
                        'id' => $stayNight->id,
                        'date' => $nightDate->format('Y-m-d'),
                        'price' => $nightPrice,
                        'is_paid' => false
                    ]);
                }
            } else {
                foreach ($stayNights as $night) {
                    \Log::error('🌙 Noche procesada:', [
                        'id' => $night->id,
                        'date' => $night->date,
                        'price' => $night->price,
                        'is_paid' => $night->is_paid
                    ]);
                    
                    $this->editPricesForm['nights'][] = [
                        'id' => $night->id,
                        'date' => $night->date instanceof \Carbon\Carbon ? $night->date->format('Y-m-d') : $night->date,
                        'price' => (float)$night->price,
                        'is_paid' => $night->is_paid
                    ];
                }
            }
            
            \Log::error('💾 editPricesForm final:', $this->editPricesForm);
            
            $this->editPricesModal = true;
            
        } catch (\Exception $e) {
            \Log::error('❌ Error en openEditPrices: ' . $e->getMessage(), [
                'reservation_id' => $reservationId,
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('notify', type: 'error', message: 'Error al cargar los datos de la reserva: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $rooms = $this->getRoomsQuery()->paginate(30);
        $dailyStats = $this->getDailyOverviewStats();
        $receptionReservationsSummary = $this->getReceptionReservationsSummary();

        // Enriquecer rooms con estados y deudas
        $rooms->getCollection()->transform(function($room) {
            $room->display_status = $room->getDisplayStatus($this->date);
            $room->current_reservation = $room->getActiveReservation($this->date);
            if ($room->current_reservation) {
                $room->current_reservation->loadMissing(['customer']);
            }

            if ($room->current_reservation) {
                // ===============================
                // SSOT: CÁLCULO CORRECTO DE NOCHE PAGA
                // ===============================
                // REGLA: Una noche está pagada si los PAGOS POSITIVOS cubren el valor de las noches consumidas
                // Se usa reservation.total_amount como SSOT (NO tarifas, NO heurísticas)
                
                $reservation = $room->current_reservation;
                
                // Obtener stay activa para usar check_in_at real (timestamp)
                $stay = $room->getAvailabilityService()->getStayForDate($this->date);
                
                // Total contractual (SSOT absoluto)
                $reservationTotalAmount = (float)($reservation->total_amount ?? 0);
                
                // Pagos reales (SOLO positivos) - SSOT financiero
                // REGLA CRÍTICA: Separar pagos y devoluciones para coherencia
                $reservation->loadMissing(['payments']);
                $paidAmount = (float)($reservation->payments
                    ->where('amount', '>', 0)
                    ->sum('amount') ?? 0);
                
                // Obtener ReservationRoom para calcular total de noches
                $reservationRoom = $room->reservationRooms?->first(function($rr) {
                    return $rr->check_in_date <= $this->date->toDateString()
                        && $rr->check_out_date >= $this->date->toDateString();
                });
                
                // Total de noches del contrato (SSOT desde ReservationRoom)
                $totalNights = $reservationRoom?->nights ?? 1;
                if ($totalNights <= 0 && $reservationRoom) {
                    $checkIn = $reservationRoom->check_in_date ? Carbon::parse($reservationRoom->check_in_date) : null;
                    $checkOut = $reservationRoom->check_out_date ? Carbon::parse($reservationRoom->check_out_date) : null;
                    if ($checkIn && $checkOut) {
                        $totalNights = max(1, $checkIn->diffInDays($checkOut));
                    }
                }
                
                // Total contractual por habitación (si existe subtotal por habitación, usarlo).
                $roomContractTotal = $reservationTotalAmount;
                if ($reservationRoom) {
                    $roomSubtotal = (float)($reservationRoom->subtotal ?? 0);
                    if ($roomSubtotal > 0) {
                        $roomContractTotal = $roomSubtotal;
                    }
                }

                // Precio por noche DERIVADO DEL TOTAL CONTRACTUAL (NO desde tarifas)
                $pricePerNight = ($roomContractTotal > 0 && $totalNights > 0)
                    ? round($roomContractTotal / $totalNights, 2)
                    : 0;
                
                // Fechas para calcular noches consumidas
                // Priorizar stay->check_in_at (timestamp real) sobre reservationRoom->check_in_date (fecha planificada)
                if ($stay && $stay->check_in_at) {
                    $checkIn = Carbon::parse($stay->check_in_at)->startOfDay(); // Mantener startOfDay para cálculo de noches
                } elseif ($reservationRoom && $reservationRoom->check_in_date) {
                    $checkIn = Carbon::parse($reservationRoom->check_in_date)->startOfDay(); // Mantener startOfDay para cálculo de noches
                } else {
                    $checkIn = null;
                }
                
                $today = $this->date->copy()->startOfDay(); // Mantener startOfDay para cálculo de noches consumidas
                
                // Noches consumidas hasta la fecha vista (inclusive)
                // REGLA: Si hoy >= check_in, al menos 1 noche está consumida
                if ($checkIn) {
                    if ($today->lt($checkIn)) {
                        $nightsConsumed = 0;
                    } else {
                        // Noches desde check_in hasta hoy (inclusive): diffInDays + 1
                        $nightsConsumed = max(1, $checkIn->diffInDays($today) + 1);
                    }
                } else {
                    // Fallback: si no hay fecha de check-in, asumir 1 noche
                    $nightsConsumed = 1;
                }
                
                // Total que debería estar pagado hasta hoy
                $expectedPaid = $pricePerNight * $nightsConsumed;
                
                // ✅ VERDAD FINAL: Noche pagada si pagos positivos >= esperado
                $room->is_night_paid = $expectedPaid > 0 && $paidAmount >= $expectedPaid;

                // Calcular total_debt usando SSOT financiero (alineado con room-payment-info y room-detail-modal)
                // REGLA CRÍTICA: Separar pagos y devoluciones para coherencia financiera
                $refundsTotal = abs((float)($reservation->payments
                    ->where('amount', '<', 0)
                    ->sum('amount') ?? 0));
                
                // Usar total contractual por habitación como SSOT.
                $totalStay = $roomContractTotal > 0 ? $roomContractTotal : ($pricePerNight * $totalNights);
                
                // Cargar sales si no están cargadas
                $reservation->loadMissing(['sales']);
                
                $sales_debt = 0;
                if ($reservation->sales) {
                    $sales_debt = (float)$reservation->sales->where('is_paid', false)->sum('total');
                }
                
                // Fórmula alineada con room-payment-info: (total - abonos) + devoluciones + consumos
                $computedDebt = ($totalStay - $paidAmount) + $refundsTotal + $sales_debt;
                
                // Mostrar deuda contractual calculada en tiempo real para evitar desalineaciones
                // con balances legacy calculados con reglas anteriores.
                $room->total_debt = $computedDebt;
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

        // Cargar historial solo cuando se necesita (en la pestaña de historial)
        $releaseHistory = null;
        if ($this->activeTab === 'history') {
            $releaseHistory = $this->getReleaseHistory();
        }
        
        return view('livewire.room-manager', [
            'daysInMonth' => $this->daysInMonth,
            'currentDate' => $this->currentDate,
            'rooms' => $rooms,
            'releaseHistory' => $releaseHistory,
            'dailyStats' => $dailyStats,
            'receptionReservationsSummary' => $receptionReservationsSummary,
        ]);
    }
}
