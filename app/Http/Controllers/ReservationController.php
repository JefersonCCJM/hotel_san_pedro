<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomDailyStatus;
use App\Models\RoomReleaseHistory;
use App\Models\DianIdentificationDocument;
use App\Models\DianLegalOrganization;
use App\Models\DianCustomerTribute;
use App\Models\DianMunicipality;
use App\Http\Requests\StoreReservationRequest;
use App\Services\AuditService;
use App\Services\ReservationReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Throwable;

class ReservationController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private ReservationReportService $reportService
    ) {}
    /**
     * Livewire browser-events are not safe to dispatch from Controllers in all Livewire versions.
     * In some installations, LivewireManager::dispatch() does not exist and will throw a fatal Error,
     * preventing redirects (even though the DB transaction already happened).
     */
    private function safeLivewireDispatch(string $event): void
    {
        try {
            if (class_exists(\Livewire\Livewire::class)) {
                // This will work on Livewire versions that support it, otherwise may throw.
                \Livewire\Livewire::dispatch($event);
            }
        } catch (Throwable $e) {
            // Never break controller redirects because of a UI-only event.
            \Log::warning("Livewire dispatch skipped in controller for event: {$event}", [
                'exception' => $e,
            ]);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $date = null)
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();
        $view = $request->get('view', 'calendar');
        $dateStr = $request->get('month', now()->format('Y-m'));
        $date = Carbon::createFromFormat('Y-m', $dateStr);

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $daysInMonth = [];
        $tempDate = $startOfMonth->copy();
        while ($tempDate <= $endOfMonth) {
            $daysInMonth[] = $tempDate->copy();
            $tempDate->addDay();
        }

        $today = Carbon::today()->startOfDay();
        $hasPastDates = $startOfMonth->lt($today);
        
        // Load snapshots for past dates (immutable historical data)
        // ✅ CORRECCIÓN CRÍTICA: Cargar TODAS reservationRooms y stays
        $roomsQuery = Room::with([
            'reservationRooms' => function ($query) {
                // 🔥 Cargar TODAS las reservationRooms con sus relaciones
                $query->with(['reservation.customer']);
            },
            'stays' => function ($query) {
                // 🔥 Cargar TODOS los stays con sus relaciones (no solo activos)
                $query->with(['reservation.customer']);
            },
        ]);
        
        // Load daily statuses for past dates if viewing past months
        if ($hasPastDates) {
            $roomsQuery->with([
                'dailyStatuses' => function ($query) use ($startOfMonth, $endOfMonth, $today) {
                    // Only load snapshots for dates that are in the past (before today)
                    $query->whereDate('date', '>=', $startOfMonth->toDateString())
                          ->whereDate('date', '<=', $endOfMonth->toDateString())
                          ->whereDate('date', '<', $today->toDateString());
                }
            ]);
        }
        
        $rooms = $roomsQuery->orderBy('room_number')->get();

        // Asegurarse de que el status se maneje como string para la vista si es necesario,
        // aunque Blade puede manejar el enum.

        $reservations = Reservation::with(['customer', 'room'])->latest()->paginate(10);

        // Prepare data for create reservation modal
        try {
            $customers = Customer::withoutGlobalScopes()
                ->with('taxProfile')
                ->orderBy('name')
                ->get();

            $roomsForModal = Room::active()->get();
            $roomsData = $this->prepareRoomsData($roomsForModal);
            $dianCatalogs = $this->getDianCatalogs();

            // Prepare customers as simple array for Livewire
            $customersArray = $customers->map(function ($customer) {
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
            })->toArray();

            // Prepare rooms as simple array for Livewire
            $roomsArray = $roomsForModal->map(function ($room) {
                return [
                    'id' => (int) $room->id,
                    'room_number' => (string) ($room->room_number ?? ''),
                    'beds_count' => (int) ($room->beds_count ?? 0),
                    'max_capacity' => (int) ($room->max_capacity ?? 0),
                ];
            })->toArray();

            // Prepare DIAN catalogs as arrays
            $dianCatalogsArray = [
                'identificationDocuments' => $dianCatalogs['identificationDocuments']->toArray(),
                'legalOrganizations' => $dianCatalogs['legalOrganizations']->toArray(),
                'tributes' => $dianCatalogs['tributes']->toArray(),
                'municipalities' => $dianCatalogs['municipalities']->toArray(),
            ];

            return view('reservations.index', array_merge(
                compact(
                    'reservations',
                    'rooms',
                    'daysInMonth',
                    'view',
                    'date'
                ),
                [
                    'modalCustomers' => $customersArray,
                    'modalRooms' => $roomsArray,
                    'modalRoomsData' => $roomsData,
                    'modalIdentificationDocuments' => $dianCatalogsArray['identificationDocuments'],
                    'modalLegalOrganizations' => $dianCatalogsArray['legalOrganizations'],
                    'modalTributes' => $dianCatalogsArray['tributes'],
                    'modalMunicipalities' => $dianCatalogsArray['municipalities'],
                ]
            ));
        } catch (\Exception $e) {
            \Log::error('Error preparing modal data in ReservationController::index(): ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            // Return view with empty arrays for modal data
            return view('reservations.index', array_merge(
                compact(
                    'reservations',
                    'rooms',
                    'daysInMonth',
                    'view',
                    'date'
                ),
                [
                    'modalCustomers' => [],
                    'modalRooms' => [],
                    'modalRoomsData' => [],
                    'modalIdentificationDocuments' => [],
                    'modalLegalOrganizations' => [],
                    'modalTributes' => [],
                    'modalMunicipalities' => [],
                ]
            ));
        }
    }

    /**
     * Show the form for creating a new resource.
     * Always returns arrays, never null values.
     */
    public function create()
    {
        try {
            // Fetch customers with error handling
            $customers = Customer::withoutGlobalScopes()
                ->with('taxProfile')
                ->orderBy('name')
                ->get();

            // Fetch rooms with error handling
            $rooms = Room::active()->get();

            // Prepare rooms data for frontend (always returns array)
            $roomsData = $this->prepareRoomsData($rooms);

            // Get DIAN catalogs for customer creation modal (always returns valid collections)
            $dianCatalogs = $this->getDianCatalogs();

            // Prepare customers as simple array for Livewire
            // Ensure structure matches what Livewire expects
            $customersArray = $customers->map(function ($customer) {
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
            })->toArray();

// Ensure customersArray is always an array
            if (!is_array($customersArray)) {
                $customersArray = [];
            }

            // Prepare rooms as simple array for Livewire
            // Ensure structure matches what Livewire expects
            $roomsArray = $rooms->map(function ($room) {
                return [
                    'id' => (int) $room->id,
                    'room_number' => (string) ($room->room_number ?? ''),
                    'beds_count' => (int) ($room->beds_count ?? 0),
                    'max_capacity' => (int) ($room->max_capacity ?? 0),
                ];
            })->toArray();

            // Ensure roomsArray is always an array
            if (!is_array($roomsArray)) {
                $roomsArray = [];
            }

            // Ensure roomsData is always an array
            if (!is_array($roomsData)) {
                $roomsData = [];
            }

            // Prepare DIAN catalogs as arrays
            // Ensure all catalogs are arrays, never null
            $dianCatalogsArray = [
                'identificationDocuments' => $dianCatalogs['identificationDocuments']->toArray(),
                'legalOrganizations' => $dianCatalogs['legalOrganizations']->toArray(),
                'tributes' => $dianCatalogs['tributes']->toArray(),
                'municipalities' => $dianCatalogs['municipalities']->toArray(),
            ];

            // Ensure all catalog arrays are valid
            foreach ($dianCatalogsArray as $key => $value) {
                if (!is_array($value)) {
                    $dianCatalogsArray[$key] = [];
                }
            }

            return view('reservations.create', array_merge(
                [
                    'customers' => $customersArray,
                    'rooms' => $roomsArray,
                    'roomsData' => $roomsData,
                ],
                $dianCatalogsArray
            ));
        } catch (\Exception $e) {
            \Log::error('Error in ReservationController::create(): ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            // Return view with empty arrays to prevent 500 errors
            return view('reservations.create', [
    'customers' => [],
                'rooms' => [],
                'roomsData' => [],
                'identificationDocuments' => [],
                'legalOrganizations' => [],
                'tributes' => [],
                'municipalities' => [],
            ])->withErrors(['error' => 'Error al cargar los datos. Por favor, recarga la página.']);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReservationRequest $request)
    {
        try {
            // 🔥 DEBUG: Log the incoming request data for debugging
            \Log::error('=== INICIO CREACIÓN RESERVA ===');
            \Log::error('Reservation store request', [
                'all_data' => $request->all(),
                'client_id' => $request->customerId,
                'room_id' => $request->room_id,
                'room_ids' => $request->room_ids,
                'total_amount' => $request->total_amount,
                'deposit' => $request->deposit,
                'guests_count' => $request->guests_count,
                'check_in_date' => $request->check_in_date,
                'check_out_date' => $request->check_out_date,
            ]);

            $data = $request->validated();
            \Log::error('Datos validados:', $data);

            // Determine if using multiple rooms or single room (backward compatibility)
            $roomIds = $request->has('room_ids') && is_array($request->room_ids)
                ? $request->room_ids
                : ($request->room_id ? [$request->room_id] : []);

            \Log::error('Room IDs determinados:', $roomIds);

            if (empty($roomIds)) {
                \Log::error('ERROR: No hay habitaciones seleccionadas');
                return back()->withInput()->withErrors(['room_id' => 'Debe seleccionar al menos una habitación.']);
            }

            // Validate dates and availability
            $checkInDate = Carbon::parse($request->check_in_date);
            $checkOutDate = Carbon::parse($request->check_out_date);

            \Log::error('Fechas procesadas:', [
                'check_in' => $checkInDate->format('Y-m-d H:i'),
                'check_out' => $checkOutDate->format('Y-m-d H:i'),
                'is_today_checkin' => $checkInDate->isToday(),
            ]);

            $dateValidation = $this->validateDates($checkInDate, $checkOutDate);
            if (!$dateValidation['valid']) {
                \Log::error('ERROR: Validación de fechas falló:', $dateValidation['errors']);
                return back()->withInput()->withErrors($dateValidation['errors']);
            }

            \Log::error('Validación de fechas OK');

            // 🔥 MVP: Validación de disponibilidad pospuesta para Fase 2
            // Validate availability for all rooms
            // $availabilityErrors = $this->validateRoomsAvailability($roomIds, $checkInDate, $checkOutDate);
            // if (!empty($availabilityErrors)) {
            //     return back()->withInput()->withErrors($availabilityErrors);
            // }

            // 🔥 MVP: Validación de asignación de huéspedes pospuesta para Fase 2
            // Validate guest assignment
            // $roomGuests = $request->room_guests ?? [];

            // Normalize roomGuests keys to integers (form sends them as strings)
            // $normalizedRoomGuests = [];
            // foreach ($roomGuests as $roomId => $assignedGuestIds) {
            //     $roomIdInt = is_numeric($roomId) ? (int) $roomId : 0;
            //     if ($roomIdInt > 0) {
            //         $normalizedRoomGuests[$roomIdInt] = $assignedGuestIds;
            //     }
            // }

            // $rooms = Room::whereIn('id', $roomIds)->get()->keyBy('id');

            // $guestValidationErrors = $this->validateGuestAssignment($normalizedRoomGuests, $rooms);
            // if (!empty($guestValidationErrors)) {
            //     return back()->withInput()->withErrors($guestValidationErrors);
            // }

            // Remove payment_method from data if not provided (it's optional)
            if (!isset($data['payment_method']) || empty($data['payment_method'])) {
                unset($data['payment_method']);
            }

            // For backward compatibility, use first room_id for the room_id field
            $data['room_id'] = $roomIds[0];

            \Log::error('Datos finales para crear reserva:', $data);

            $reservation = Reservation::create($data);
            
            \Log::error('✅ RESERVA CREADA - ID: ' . $reservation->id);
            \Log::error('Datos de reserva creada:', [
                'id' => $reservation->id,
                'client_id' => $reservation->client_id,
                'room_id' => $reservation->room_id,
                'total_amount' => $reservation->total_amount,
                'deposit' => $reservation->deposit,
            ]);

            // 🔥 MVP: Asignación de huéspedes pospuesta para Fase 2
            // Attach all rooms to reservation via pivot table
            foreach ($roomIds as $roomId) {
                \Log::error("Creando ReservationRoom para habitación {$roomId}");
                
                // ✅ CORRECCIÓN CRÍTICA: Guardar fechas en reservation_rooms
                $reservationRoom = ReservationRoom::create([
                    'reservation_id' => $reservation->id,
                    'room_id' => $roomId,
                    'check_in_date' => $checkInDate->toDateString(),
                    'check_out_date' => $checkOutDate->toDateString(),
                    'check_in_time' => $request->check_in_time ?? null,
                    'check_out_time' => $request->check_out_time ?? null,
                    'nights' => max(1, $checkInDate->diffInDays($checkOutDate)),
                    'price_per_night' => 0, // 🔥 MVP: Valor temporal para evitar NULL
                    'subtotal' => 0, // 🔥 MVP: Valor temporal para evitar NULL
                ]);

                \Log::error("✅ ReservationRoom creado - ID: {$reservationRoom->id}");
                \Log::error("Datos del ReservationRoom:", [
                    'id' => $reservationRoom->id,
                    'reservation_id' => $reservationRoom->reservation_id,
                    'room_id' => $reservationRoom->room_id,
                    'check_in_date' => $reservationRoom->check_in_date,
                    'check_out_date' => $reservationRoom->check_out_date,
                    'check_in_time' => $reservationRoom->check_in_time,
                    'nights' => $reservationRoom->nights,
                ]);

                // 🔥 MVP: Asignación de huéspedes a habitación específica pospuesta para Fase 2
                // Assign guests to this specific room if provided (use normalized array)
                // $roomIdInt = is_numeric($roomId) ? (int) $roomId : 0;
                // $this->assignGuestsToRoom($reservationRoom, $normalizedRoomGuests[$roomIdInt] ?? []);
            }

            // 🔥 MVP: NO crear Stay automáticamente - solo al check-in real
            // 🔥 AJUSTE CRÍTICO 1 y 2: Crear Stay y StayNight si check-in es HOY
            // if ($checkInDate->isToday()) {
            //     \Log::error('🔥 Check-in es HOY - Creando Stay activa');
            //     foreach ($roomIds as $roomId) {
            //         \Log::error("Creando Stay para habitación {$roomId}");
                    
            //         // Crear Stay activa (check-in inmediato)
            //         $stay = \App\Models\Stay::create([
            //             'reservation_id' => $reservation->id,
            //             'room_id' => $roomId,
            //             'check_in_at' => now(), // Check-in INMEDIATO (timestamp)
            //             'check_out_at' => null, // Se completará al checkout
            //             'status' => 'active', // Estados: active, pending_checkout, finished
            //         ]);

            //         \Log::error("✅ Stay creado - ID: {$stay->id}");
            //         \Log::error("Datos del Stay:", [
            //             'id' => $stay->id,
            //             'reservation_id' => $stay->reservation_id,
            //             'room_id' => $stay->room_id,
            //             'check_in_at' => $stay->check_in_at?->format('Y-m-d H:i:s'),
            //             'check_out_at' => $stay->check_out_at,
            //             'status' => $stay->status,
            //         ]);

            //         // 🔥 Generar la primera noche (StayNight)
            //         $this->ensureNightForDate($stay, $checkInDate);

            //         // 🔥 AJUSTE CRÍTICO 3: Estado de limpieza pendiente_aseo
            //         // Poner last_cleaned_at = null para que quede en estado pendiente_aseo
            //         Room::where('id', $roomId)->update([
            //             'last_cleaned_at' => null, // Pendiente por aseo
            //         ]);
                    
            //         \Log::error("Habitación {$roomId} marcada como pendiente de limpieza");
            //     }
            // } else {
            //     \Log::error('Check-in NO es hoy - no se crea Stay activa');
            // }

            \Log::error('🔥 MVP: Stay NO creado - la reserva solo existe en tabla reservations');
            \Log::error('🔥 El Stay se creará solo cuando el huésped haga check-in real');

            // Audit log for reservation creation
            $this->auditService->logReservationCreated($reservation, $request, $roomIds);

            \Log::error('🔥 Generando room_daily_statuses_data para el calendario');
            // 🔥 CRÍTICO: Generar room_daily_statuses_data para el calendario
            $roomDailyStatusService = app(\App\Services\RoomDailyStatusService::class);
            $roomDailyStatusService->generateForReservation($reservation);

            // Dispatch Livewire event for stats update
            $this->safeLivewireDispatch('reservation-created');

            \Log::error('=== FIN CREACIÓN RESERVA EXITOSA ===');
            \Log::error("Redirigiendo a calendario del mes: {$checkInDate->format('Y-m')}");

            // Redirect to reservations index with calendar view for the check-in month
            $month = $checkInDate->format('Y-m');
            return redirect()->route('reservations.index', ['view' => 'calendar', 'month' => $month])
                ->with('success', 'Reserva registrada exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to let Laravel handle them properly
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error creating reservation: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withInput()->withErrors(['error' => 'Error al crear la reserva: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation)
    {
        return view('reservations.show', compact('reservation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reservation $reservation)
    {
        try {
            $reservation->load([
                'customer.taxProfile',
                'rooms',
                'reservationRooms.room',
                'reservationRooms.guests.taxProfile',
                'guests.taxProfile',
            ]);

            $customers = Customer::withoutGlobalScopes()
                ->with('taxProfile')
                ->orderBy('name')
                ->get();

            $rooms = Room::active()->get();

            $roomsData = $this->prepareRoomsData($rooms);

            $customersArray = $customers->map(function ($customer) {
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
            })->toArray();

            if (!is_array($customersArray)) {
                $customersArray = [];
            }

            $roomsArray = $rooms->map(function ($room) {
                return [
                    'id' => (int) $room->id,
                    'room_number' => (string) ($room->room_number ?? ''),
                    'beds_count' => (int) ($room->beds_count ?? 0),
                    'max_capacity' => (int) ($room->max_capacity ?? 0),
                ];
            })->toArray();

            if (!is_array($roomsArray)) {
                $roomsArray = [];
            }

            if (!is_array($roomsData)) {
                $roomsData = [];
            }

            $dianCatalogs = $this->getDianCatalogs();
            $dianCatalogsArray = [
                'identificationDocuments' => $dianCatalogs['identificationDocuments']->toArray(),
                'legalOrganizations' => $dianCatalogs['legalOrganizations']->toArray(),
                'tributes' => $dianCatalogs['tributes']->toArray(),
                'municipalities' => $dianCatalogs['municipalities']->toArray(),
            ];

            foreach ($dianCatalogsArray as $key => $value) {
                if (!is_array($value)) {
                    $dianCatalogsArray[$key] = [];
                }
            }

            return view('reservations.edit', array_merge(
                [
                    'reservation' => $reservation,
                    'customers' => $customersArray,
                    'rooms' => $roomsArray,
                    'roomsData' => $roomsData,
                ],
                $dianCatalogsArray
            ));
        } catch (\Exception $e) {
            \Log::error('Error in ReservationController::edit(): ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'reservation_id' => $reservation->id,
            ]);

            return back()->withErrors(['error' => 'Error al cargar los datos. Por favor, recarga la página.']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreReservationRequest $request, Reservation $reservation)
    {
        try {
            $data = $request->validated();

            $roomIds = $request->has('room_ids') && is_array($request->room_ids)
                ? $request->room_ids
                : ($request->room_id ? [$request->room_id] : []);

            if (empty($roomIds)) {
                return back()->withInput()->withErrors(['room_id' => 'Debe seleccionar al menos una habitación.']);
            }

            $checkInDate = Carbon::parse($request->check_in_date);
            $checkOutDate = Carbon::parse($request->check_out_date);

            $dateValidation = $this->validateDates($checkInDate, $checkOutDate);
            if (!$dateValidation['valid']) {
                return back()->withInput()->withErrors($dateValidation['errors']);
            }

            $availabilityErrors = $this->validateRoomsAvailabilityForUpdate(
                $roomIds,
                $checkInDate,
                $checkOutDate,
                (int) $reservation->id
            );
            if (!empty($availabilityErrors)) {
                return back()->withInput()->withErrors($availabilityErrors);
            }

            $roomGuests = $request->room_guests ?? [];
            $normalizedRoomGuests = [];
            foreach ($roomGuests as $roomId => $assignedGuestIds) {
                $roomIdInt = is_numeric($roomId) ? (int) $roomId : 0;
                if ($roomIdInt > 0) {
                    $normalizedRoomGuests[$roomIdInt] = $assignedGuestIds;
                }
            }

            $rooms = Room::whereIn('id', $roomIds)->get()->keyBy('id');
            $guestValidationErrors = $this->validateGuestAssignment($normalizedRoomGuests, $rooms);
            if (!empty($guestValidationErrors)) {
                return back()->withInput()->withErrors($guestValidationErrors);
            }

            if (!isset($data['payment_method']) || empty($data['payment_method'])) {
                unset($data['payment_method']);
            }

            $oldValues = [
                'room_id' => $reservation->room_id,
                'check_in_date' => $reservation->check_in_date?->format('Y-m-d'),
                'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
                'total_amount' => (float) $reservation->total_amount,
                'deposit' => (float) $reservation->deposit,
                'guests_count' => (int) $reservation->guests_count,
                'payment_method' => $reservation->payment_method,
            ];

            DB::beginTransaction();

            // Backward compatibility: use first room id as the reservation room_id.
            $data['room_id'] = $roomIds[0];
            $reservation->update($data);

            // Rebuild room assignments and room guests.
            $reservation->reservationRooms()->delete();

            foreach ($roomIds as $roomId) {
                $reservationRoom = ReservationRoom::create([
                    'reservation_id' => $reservation->id,
                    'room_id' => $roomId,
                ]);

                $roomIdInt = is_numeric($roomId) ? (int) $roomId : 0;
                $this->assignGuestsToRoom($reservationRoom, $normalizedRoomGuests[$roomIdInt] ?? []);
            }

            // Legacy guests (single room): sync to avoid duplicate rows.
            if ($request->has('guest_ids') && is_array($request->guest_ids) && !$request->has('room_guests')) {
                $this->syncGuestsToReservationLegacy($reservation, $request->guest_ids);
            }

            if ($checkInDate->isToday()) {
                Room::whereIn('id', $roomIds)->update(['status' => \App\Enums\RoomStatus::OCUPADA]);
            }

            $reservation->refresh();

            $this->auditService->logReservationUpdated($reservation, $request, $oldValues);
            $this->safeLivewireDispatch('reservation-updated');

            DB::commit();

            return redirect()->route('reservations.index')->with('success', 'Reserva actualizada correctamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating reservation: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'reservation_id' => $reservation->id,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors(['error' => 'Error al actualizar la reserva: ' . $e->getMessage()]);
        }
    }

    private function validateRoomsAvailabilityForUpdate(
        array $roomIds,
        Carbon $checkIn,
        Carbon $checkOut,
        int $excludeReservationId
    ): array {
        $errors = [];

        foreach ($roomIds as $roomId) {
            $roomIdInt = is_numeric($roomId) ? (int) $roomId : 0;
            if ($roomIdInt <= 0) {
                continue;
            }

            if (!$this->isRoomAvailable($roomIdInt, $checkIn, $checkOut, $excludeReservationId)) {
                $room = Room::find($roomIdInt);
                $roomNumber = $room ? $room->room_number : $roomIdInt;
                $errors['room_ids'][] = "La habitación {$roomNumber} ya está reservada para las fechas seleccionadas.";
            }
        }

        return $errors;
    }

    private function syncGuestsToReservationLegacy(Reservation $reservation, array $guestIds): void
    {
        $validGuestIds = array_filter($guestIds, function ($id): bool {
            return !empty($id) && is_numeric($id) && $id > 0;
        });

        if (empty($validGuestIds)) {
            $reservation->guests()->sync([]);
            return;
        }

        $validGuestIds = Customer::withoutGlobalScopes()
            ->whereIn('id', $validGuestIds)
            ->pluck('id')
            ->toArray();

        $reservation->guests()->sync($validGuestIds);
    }

    /**
     * Get release data for a reservation (for showing release confirmation modal).
     */
    public function getReleaseData(Reservation $reservation)
    {
        $today = Carbon::today()->startOfDay();
        $date = $today; // Always use today for cancellation
        
        $reservation->load(['customer.taxProfile', 'sales.product', 'reservationDeposits', 'guests.taxProfile', 'rooms']);
        
        // Get first room (for multi-room reservations, use first room)
        $room = $reservation->rooms->first() ?? $reservation->room;
        
        if (!$room) {
            return response()->json([
                'room_id' => null,
                'room_number' => 'N/A',
                'reservation' => null,
                'identification' => 'N/A',
                'total_hospedaje' => 0,
                'abono_realizado' => 0,
                'sales_total' => 0,
                'consumos_pendientes' => 0,
                'total_debt' => 0,
                'total_refunds' => 0,
                'refund_registered' => false,
                'sales' => [],
                'deposit_history' => [],
                'refunds_history' => [],
            ]);
        }
        
        $total_hospedaje_completo = (float) $reservation->total_amount;
        $abono = (float) $reservation->deposit;
        $consumos_pagados = (float) $reservation->sales->where('is_paid', true)->sum('total');
        $consumos_pendientes = (float) $reservation->sales->where('is_paid', false)->sum('total');
        
        $checkIn = Carbon::parse($reservation->check_in_date);
        $checkOut = Carbon::parse($reservation->check_out_date);
        $daysTotal = max(1, $checkIn->diffInDays($checkOut));
        $dailyPrice = $daysTotal > 0 ? ($total_hospedaje_completo / $daysTotal) : $total_hospedaje_completo;
        
        // Calculate consumed lodging up to yesterday (since we're cancelling today)
        $yesterday = $today->copy()->subDay();
        $daysConsumed = $checkIn->diffInDays($yesterday) + 1;
        $daysConsumed = max(1, min($daysTotal, $daysConsumed));
        $total_hospedaje = $dailyPrice * $daysConsumed;
        
        // Get refunds history
        $refundsHistory = \App\Models\AuditLog::where('event', 'customer_refund_registered')
            ->whereRaw('JSON_EXTRACT(metadata, "$.reservation_id") = ?', [$reservation->id])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'amount' => (float) ($log->metadata['refund_amount'] ?? 0),
                    'created_at' => $log->created_at->format('d/m/Y H:i'),
                    'created_by' => $log->user->name ?? 'N/A',
                ];
            })
            ->toArray();
        
        $totalRefunds = collect($refundsHistory)->sum('amount');
        
        // Calculate debt (consumed lodging - deposit + pending consumptions)
        $total_debt = ($total_hospedaje - $abono) + $consumos_pendientes;
        
        return response()->json([
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'reservation' => [
                'id' => $reservation->id,
                'customer' => [
                    'name' => $reservation->customer->name,
                ],
            ],
            'identification' => $reservation->customer->taxProfile?->identification ?? 'N/A',
            'total_hospedaje' => $total_hospedaje,
            'abono_realizado' => $abono,
            'sales_total' => $consumos_pagados + $consumos_pendientes,
            'consumos_pendientes' => $consumos_pendientes,
            'total_debt' => $total_debt,
            'refunds_history' => $refundsHistory,
            'total_refunds' => $totalRefunds,
            'sales' => $reservation->sales->map(function($sale) {
                return [
                    'id' => $sale->id,
                    'product' => [
                        'name' => $sale->product->name ?? 'N/A',
                    ],
                    'quantity' => $sale->quantity,
                    'total' => (float) $sale->total,
                    'is_paid' => $sale->is_paid,
                    'payment_method' => $sale->payment_method,
                ];
            })->toArray(),
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
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * If reservation has started (check_in_date <= today), creates snapshot of previous day
     * and modifies check_out_date to previous day instead of deleting.
     */
    public function destroy(Reservation $reservation)
    {
        $today = Carbon::today()->startOfDay();
        $checkInDate = Carbon::parse($reservation->check_in_date)->startOfDay();
        
        // If reservation has started, use release logic instead of deleting
        if ($checkInDate->lte($today)) {
            return $this->releaseActiveReservation($reservation);
        }
        
        // Reservation hasn't started yet - delete it normally
        $reservation->delete();

        // Audit log for reservation cancellation
        $this->auditService->logReservationCancelled($reservation, request());

        // Dispatch Livewire event for stats update
        $this->safeLivewireDispatch('reservation-cancelled');

        return redirect()->route('reservations.index')->with('success', 'Reserva cancelada correctamente.');
    }

    /**
     * Release an active reservation by creating snapshot of previous day and modifying check_out_date.
     */
    private function releaseActiveReservation(Reservation $reservation)
    {
        DB::transaction(function() use ($reservation) {
            $today = Carbon::today()->startOfDay();
            $yesterday = $today->copy()->subDay();
            
            // Load necessary relations
            $reservation->load([
                'customer.taxProfile',
                'sales.product',
                'reservationDeposits',
                'guests.taxProfile',
                'rooms'
            ]);
            
            // Get rooms for this reservation (support multi-room reservations)
            $rooms = $reservation->rooms;
            if ($rooms->isEmpty() && $reservation->room) {
                $rooms = collect([$reservation->room]);
            }
            
            if ($rooms->isEmpty()) {
                throw new \Exception('No se encontraron habitaciones asociadas a la reserva.');
            }
            
            // Store original check_out_date before modifying
            $originalCheckOutDate = Carbon::parse($reservation->check_out_date)->startOfDay();
            
            // Create snapshot of yesterday's state for all rooms (using ORIGINAL check_out_date)
            foreach ($rooms as $room) {
                $this->createRoomSnapshot($room, $yesterday, $reservation, $originalCheckOutDate);
            }
            
            // Use first room for release history (will contain all reservation info)
            $room = $rooms->first();
            
            // Calculate financial information up to yesterday
            $start = Carbon::parse($reservation->check_in_date)->startOfDay();
            $end = $originalCheckOutDate;
            
            $consumptionsTotal = (float) $reservation->sales->sum('total');
            $consumptionsPending = (float) $reservation->sales->where('is_paid', false)->sum('total');
            $depositsTotal = (float) $reservation->deposit;
            $totalAmount = (float) $reservation->total_amount;
            
            // Calculate consumed lodging up to yesterday
            $daysTotal = max(1, $start->diffInDays($end));
            $dailyPrice = $daysTotal > 0 ? ($totalAmount / $daysTotal) : $totalAmount;
            $daysConsumed = $start->diffInDays($yesterday) + 1;
            $daysConsumed = max(1, min($daysTotal, $daysConsumed));
            $totalHospedajeConsumido = $dailyPrice * $daysConsumed;
            
            $pendingAmount = ($totalHospedajeConsumido - $depositsTotal) + $consumptionsPending;
            
            // Create release history
            RoomReleaseHistory::create([
                'room_id' => $room->id,
                'reservation_id' => $reservation->id,
                'client_id' => $reservation->client_id,
                'released_by' => Auth::id(),
                'room_number' => $room->room_number,
                'total_amount' => $totalAmount,
                'deposit' => $depositsTotal,
                'consumptions_total' => $consumptionsTotal,
                'pending_amount' => $pendingAmount,
                'guests_count' => $reservation->guests_count,
                'check_in_date' => $reservation->check_in_date,
                'check_out_date' => $reservation->check_out_date,
                'release_date' => $yesterday->toDateString(),
                'target_status' => 'libre',
                'customer_name' => $reservation->customer->name,
                'customer_identification' => $reservation->customer->taxProfile?->identification,
                'customer_phone' => $reservation->customer->phone,
                'customer_email' => $reservation->customer->email,
                'reservation_data' => $reservation->toArray(),
                'sales_data' => $reservation->sales->map(function($sale) {
                    return [
                        'id' => $sale->id,
                        'product_id' => $sale->product_id,
                        'product_name' => $sale->product->name ?? 'N/A',
                        'quantity' => $sale->quantity,
                        'unit_price' => (float) $sale->unit_price,
                        'total' => (float) $sale->total,
                        'payment_method' => $sale->payment_method,
                        'is_paid' => $sale->is_paid,
                        'created_at' => $sale->created_at?->toDateTimeString(),
                    ];
                })->toArray(),
                'deposits_data' => $reservation->reservationDeposits->map(function($deposit) {
                    return [
                        'id' => $deposit->id,
                        'amount' => (float) $deposit->amount,
                        'payment_method' => $deposit->payment_method,
                        'notes' => $deposit->notes,
                        'created_at' => $deposit->created_at?->toDateTimeString(),
                    ];
                })->toArray(),
                'guests_data' => $reservation->guests->map(function($guest) {
                    return [
                        'id' => $guest->id,
                        'name' => $guest->name,
                        'identification' => $guest->taxProfile?->identification,
                        'phone' => $guest->phone,
                        'email' => $guest->email,
                    ];
                })->toArray(),
            ]);
            
            // Modify reservation to end yesterday instead of deleting
            $reservation->update(['check_out_date' => $yesterday->toDateString()]);
            
            // Audit log
            $this->auditService->logReservationCancelled($reservation, request());
        });
        
        // Dispatch Livewire event for stats update
        $this->safeLivewireDispatch('reservation-cancelled');
        
        return redirect()->route('reservations.index')
            ->with('success', 'Reserva finalizada. La habitación quedó libre desde hoy. La información histórica se ha preservado.');
    }

    /**
     * Create a snapshot of room status for a specific date.
     * Creates snapshot BEFORE modifying reservation, so getDisplayStatus will use original reservation state.
     */
    private function createRoomSnapshot(Room $room, Carbon $date, Reservation $reservation, Carbon $originalCheckOutDate = null): void
    {
        // Use original check_out_date if provided, otherwise use reservation's current check_out_date
        $checkOutForSnapshot = $originalCheckOutDate ?? Carbon::parse($reservation->check_out_date);
        
        // getDisplayStatus queries DB directly, so it will use current reservation state
        // Since we create snapshot BEFORE modifying reservation, it captures correct state
        $displayStatus = $room->getDisplayStatus($date);
        $cleaning = $room->cleaningStatus($date);
        
        // Prepare guests data
        $guestsData = null;
        if ($reservation->customer) {
            $mainGuest = [
                'id' => $reservation->customer->id,
                'name' => $reservation->customer->name,
                'identification' => $reservation->customer->taxProfile?->identification ?? null,
                'phone' => $reservation->customer->phone,
                'email' => $reservation->customer->email,
                'is_main' => true,
            ];
            
            $additionalGuests = $reservation->guests->map(function($guest) {
                return [
                    'id' => $guest->id,
                    'name' => $guest->name,
                    'identification' => $guest->taxProfile?->identification ?? null,
                    'phone' => $guest->phone,
                    'email' => $guest->email,
                    'is_main' => false,
                ];
            })->toArray();
            
            $guestsData = array_merge([$mainGuest], $additionalGuests);
        }
        
        // Snapshots are IMMUTABLE - only create if doesn't exist
        // Never update existing snapshots to preserve historical data integrity
        RoomDailyStatus::firstOrCreate(
            [
                'room_id' => $room->id,
                'date' => $date->toDateString(),
            ],
            [
                'status' => $displayStatus,
                'cleaning_status' => $cleaning['code'],
                'reservation_id' => $reservation->id,
                'guest_name' => $reservation->customer?->name ?? null,
                'guests_data' => $guestsData,
                'check_out_date' => $checkOutForSnapshot->toDateString(),
                'total_amount' => (float) $reservation->total_amount,
            ]
        );
    }

    /**
     * Download the reservation support in PDF format.
     */
    public function download(Reservation $reservation)
    {
        $reservation->load(['customer', 'room']);
        $pdf = Pdf::loadView('reservations.pdf', compact('reservation'));
        return $pdf->download("Soporte_Reserva_{$reservation->id}.pdf");
    }

    /**
     * Export reservations report as PDF with date range.
     */
    public function exportMonthlyReport(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Validar que las fechas sean válidas
        try {
            if ($startDate) {
                $startDate = Carbon::createFromFormat('Y-m-d', $startDate);
            } else {
                $startDate = now()->subMonths(3);
            }

            if ($endDate) {
                $endDate = Carbon::createFromFormat('Y-m-d', $endDate);
            } else {
                $endDate = now();
            }
        } catch (\Exception $e) {
            return back()->withErrors([
                'dates' => 'Formato de fecha inválido. Use YYYY-MM-DD.',
            ]);
        }

        // Validar que start_date no sea mayor que end_date
        if ($startDate->isAfter($endDate)) {
            return back()->withErrors([
                'dates' => 'La fecha de inicio no puede ser posterior a la fecha de fin.',
            ]);
        }

        // Validar que no excedan 2 años de antigüedad
        $twoYearsAgo = now()->subYears(2);
        if ($startDate->isBefore($twoYearsAgo)) {
            return back()->withErrors([
                'dates' => 'No se pueden exportar datos con más de 2 años de antigüedad.',
            ]);
        }

        // Validar que no sean fechas futuras exageradas (máximo 1 año en el futuro)
        $oneYearFuture = now()->addYears(1);
        if ($endDate->isAfter($oneYearFuture)) {
            return back()->withErrors([
                'dates' => 'No se pueden exportar datos con más de 1 año de anticipación.',
            ]);
        }

        // Obtener las reservaciones para el rango de fechas
        $reservations = Reservation::whereDate('check_in_date', '>=', $startDate)
            ->whereDate('check_in_date', '<=', $endDate)
            ->with(['room', 'customer', 'rooms'])
            ->orderBy('check_in_date', 'desc')
            ->get();

        $reportData = [
            'reservations' => $reservations,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalReservations' => $reservations->count(),
            'totalAmount' => $reservations->sum(fn($r) => $r->total_amount ?? 0),
        ];

        $pdf = Pdf::loadView('reservations.monthly-report-pdf', $reportData);

        return $pdf->download("Reporte_Reservaciones_{$startDate->format('Y-m-d')}_a_{$endDate->format('Y-m-d')}.pdf");
    }

    /**
     * Check if a room is available for a given date range.
     */
    public function checkAvailability(Request $request)
    {
        $checkIn = Carbon::parse($request->check_in_date);
        $checkOut = Carbon::parse($request->check_out_date);
        $excludeReservationId = $request->reservation_id ? (int) $request->reservation_id : null;

        $isAvailable = $this->isRoomAvailable(
            (int) $request->room_id,
            $checkIn,
            $checkOut,
            $excludeReservationId
        );

        return response()->json(['available' => $isAvailable]);
    }

    /**
     * Prepare rooms data for frontend consumption.
     * Similar to CustomerController::getTaxCatalogs() pattern.
     * Always returns array with all required keys, never null.
     */
    private function prepareRoomsData(Collection $rooms): array
    {
        if ($rooms->isEmpty()) {
            return [];
        }

        return $rooms->map(function (Room $room): array {
            $occupancyPrices = $room->occupancy_prices ?? [];

            // Fallback to legacy prices if occupancy_prices is empty
            if (empty($occupancyPrices) || !is_array($occupancyPrices)) {
                $defaultPrice = (float) ($room->price_per_night ?? 0);
                $occupancyPrices = [
                    1 => (float) ($room->price_1_person ?? $defaultPrice),
                    2 => (float) ($room->price_2_persons ?? $defaultPrice),
                ];
                // Calculate additional person prices
                $additionalPrice = (float) ($room->price_additional_person ?? 0);
                $maxCapacity = (int) ($room->max_capacity ?? 2);
                for ($i = 3; $i <= $maxCapacity; $i++) {
                    $occupancyPrices[$i] = $occupancyPrices[2] + ($additionalPrice * ($i - 2));
                }
            } else {
                // Ensure keys are integers (JSON may return string keys)
                $normalizedPrices = [];
                foreach ($occupancyPrices as $key => $value) {
                    $normalizedPrices[(int) $key] = (float) $value;
                }
                $occupancyPrices = $normalizedPrices;
            }

            // Calculate additional person price
            $defaultPrice = (float) ($room->price_per_night ?? 0);
            $price1Person = (float) ($room->price_1_person ?? $defaultPrice);
            $price2Persons = (float) ($room->price_2_persons ?? $defaultPrice);
            $priceAdditionalPerson = (float) ($room->price_additional_person ?? 0);

            // If price_additional_person is 0 or not set, calculate it from the difference
            if ($priceAdditionalPerson == 0 && $price2Persons > $price1Person) {
                $priceAdditionalPerson = $price2Persons - $price1Person;
            }

            return [
                'id' => (int) $room->id,
                'number' => (string) ($room->room_number ?? ''),
                'beds' => (int) ($room->beds_count ?? 0),
                'max_capacity' => (int) ($room->max_capacity ?? 2),
                'price' => $defaultPrice, // Keep for backward compatibility
                'occupancyPrices' => $occupancyPrices, // Prices by number of guests
                'price1Person' => $price1Person, // Base price for 1 person
                'price2Persons' => $price2Persons, // Price for 2 persons (for calculation fallback)
                'priceAdditionalPerson' => $priceAdditionalPerson, // Additional price per person
            ];
        })->toArray();
    }
    /**
     * Get DIAN catalogs for customer creation modal.
     * Similar to CustomerController::getTaxCatalogs() pattern.
     * Always returns valid collections, never null.
     */
    private function getDianCatalogs(): array
    {
        try {
            return [
                'identificationDocuments' => DianIdentificationDocument::query()->orderBy('id')->get() ?? collect(),
                'legalOrganizations' => DianLegalOrganization::query()->orderBy('id')->get() ?? collect(),
                'tributes' => DianCustomerTribute::query()->orderBy('id')->get() ?? collect(),
                'municipalities' => DianMunicipality::query()
                    ->orderBy('department')
                    ->orderBy('name')
                    ->get() ?? collect(),
            ];
        } catch (\Exception $e) {
            \Log::error('Error fetching DIAN catalogs: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            // Return empty collections to prevent null errors in frontend
            return [
                'identificationDocuments' => collect(),
                'legalOrganizations' => collect(),
                'tributes' => collect(),
                'municipalities' => collect(),
            ];
        }
    }

    /**
     * Validate dates for a reservation.
     * Ensures check-in is not before today and check-out is after check-in.
     */
    private function validateDates(Carbon $checkIn, Carbon $checkOut): array
    {
        $errors = [];
        $today = Carbon::today();

        // Check if check-in is before today
        if ($checkIn->isBefore($today)) {
            $errors['check_in_date'] = 'La fecha de entrada no puede ser anterior al día actual.';
        }

        // Check if check-out is before or equal to check-in
        if ($checkOut->lte($checkIn)) {
            $errors['check_out_date'] = 'La fecha de salida debe ser posterior a la fecha de entrada.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate availability for multiple rooms.
     */
    private function validateRoomsAvailability(array $roomIds, Carbon $checkIn, Carbon $checkOut): array
    {
        $errors = [];

        foreach ($roomIds as $roomId) {
            if (!$this->isRoomAvailable($roomId, $checkIn, $checkOut)) {
                $room = Room::find($roomId);
                $roomNumber = $room ? $room->room_number : $roomId;
                $errors['room_ids'][] = "La habitación {$roomNumber} ya está reservada para las fechas seleccionadas.";
            }
        }

        return $errors;
    }

    /**
     * Check if a room is available for a given date range.
     */
    private function isRoomAvailable(int $roomId, Carbon $checkIn, Carbon $checkOut, ?int $excludeReservationId = null): bool
    {
        // 🔥 AJUSTE CRÍTICO: Verificar stays activas (ocupación real)
        // Una habitación NO está disponible si tiene una stay activa que intersecta el rango solicitado
        $hasActiveStay = \App\Models\Stay::where('room_id', $roomId)
            ->where('status', 'active')
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->where('check_in_at', '<', $checkOut->endOfDay())
                  ->where(function ($q2) use ($checkIn) {
                      $q2->whereNull('check_out_at')
                         ->orWhere('check_out_at', '>', $checkIn->startOfDay());
                  });
            })
            ->exists();

        if ($hasActiveStay) {
            return false; // ❌ Habitación ocupada por stay activa
        }

        // Check in main reservations table (single room reservations) - CORREGIDO
        // 🔥 CORRECCIÓN CRÍTICA: Para hoy, evaluar si hay stay activa ANTES de verificar hora
        $checkInDate = \Carbon\Carbon::parse($checkIn);
        if ($checkInDate->isToday()) {
            // 🎯 PRIMERO: Verificar si hay stay activa
            $hasActiveStay = \App\Models\Stay::where('room_id', $roomId)
                ->where('status', 'active')
                ->where(function($query) use ($checkInDate) {
                    $query->whereNull('check_out_at')
                          ->orWhere('check_out_at', '>=', $checkInDate->copy()->startOfDay());
                })
                ->exists();
            
            if ($hasActiveStay) {
                // 🏠 Si hay stay activa, esperar hasta hora de check-out
                if (!\App\Support\HotelTime::isAfterCheckOutTime()) {
                    return false;
                }
                // Si es después de check-out, continuar con verificaciones normales
            }
            // 🏠 Si NO hay stay activa, permitir reserva inmediata (continuar normal)
        }
        
        $existsInReservations = \App\Models\ReservationRoom::where('room_id', $roomId)
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('check_in_date', '<', $checkOut)
                      ->where('check_out_date', '>', $checkIn);
            })
            ->whereHas('reservation', function ($q) use ($excludeReservationId) {
                $q->whereNull('deleted_at')
                  ->when($excludeReservationId, function ($subQ) use ($excludeReservationId) {
                      $subQ->where('id', '!=', $excludeReservationId);
                  });
            })
            ->exists();

        if ($existsInReservations) {
            return false;
        }

        // Check in reservation_rooms table (multi-room reservations) - CORREGIDO
        // Para hoy, ya fue manejado arriba, solo verificar fechas futuras
        if (!$checkInDate->isToday()) {
            $existsInPivot = DB::table('reservation_rooms')
                ->join('reservations', 'reservation_rooms.reservation_id', '=', 'reservations.id')
                ->where('reservation_rooms.room_id', $roomId)
                ->whereNull('reservations.deleted_at')
                ->where(function ($query) use ($checkIn, $checkOut) {
                    $query->where('reservation_rooms.check_in_date', '<', $checkOut)
                          ->where('reservation_rooms.check_out_date', '>', $checkIn);
                })
                ->when($excludeReservationId, function ($q) use ($excludeReservationId) {
                    $q->where('reservations.id', '!=', $excludeReservationId);
                })
                ->exists();

            if ($existsInPivot) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate guest assignment for multiple rooms.
     */
    private function validateGuestAssignment(array $roomGuests, Collection $rooms): array
    {
        $errors = [];
        $guestRoomMap = [];

        foreach ($roomGuests as $roomId => $assignedGuestIds) {
            $room = $rooms->get($roomId);

            if (!$room) {
                $errors['room_guests'][] = "La habitación con ID {$roomId} no existe.";
                continue;
            }

            // Filter valid guest IDs
            $validGuestIds = array_filter($assignedGuestIds, function ($id): bool {
                return !empty($id) && is_numeric($id) && $id > 0;
            });
            $validGuestIds = array_values(array_unique(array_map('intval', $validGuestIds)));

            $guestCount = count($validGuestIds);

            if ($guestCount > $room->max_capacity) {
                $errors['room_guests'][] = "La habitación {$room->room_number} tiene una capacidad máxima de {$room->max_capacity} personas, pero se están intentando asignar {$guestCount}.";
            }

            // Business rule: prevent assigning the same guest to multiple rooms in the same reservation.
            foreach ($validGuestIds as $guestId) {
                if (!isset($guestRoomMap[$guestId])) {
                    $guestRoomMap[$guestId] = (int) $roomId;
                    continue;
                }

                $firstRoomId = (int) $guestRoomMap[$guestId];
                if ($firstRoomId === (int) $roomId) {
                    continue;
                }

                $firstRoom = $rooms->get($firstRoomId);
                $firstRoomNumber = $firstRoom ? $firstRoom->room_number : (string) $firstRoomId;
                $errors['room_guests'][] = "Un huésped no puede estar asignado a dos habitaciones en la misma reserva (Hab. {$firstRoomNumber} y Hab. {$room->room_number}).";
            }
        }

        return $errors;
    }

    /**
     * Assign guests to a specific reservation room.
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
        $validGuestIds = Customer::withoutGlobalScopes()
            ->whereIn('id', $validGuestIds)
            ->pluck('id')
            ->toArray();

        if (empty($validGuestIds)) {
            return;
        }

        try {
            // Estructura de BD:
            // reservation_guests: id, reservation_room_id, guest_id
            // reservation_room_guests: id, reservation_room_id, reservation_guest_id
            
            foreach ($validGuestIds as $guestId) {
                // Verificar si ya existe el registro
                $existingReservationGuest = \DB::table('reservation_guests')
                    ->where('reservation_room_id', $reservationRoom->id)
                    ->where('guest_id', $guestId)
                    ->first();
                
                if ($existingReservationGuest) {
                    // Ya existe, verificar si está en reservation_room_guests
                    $existingRoomGuest = \DB::table('reservation_room_guests')
                        ->where('reservation_room_id', $reservationRoom->id)
                        ->where('reservation_guest_id', $existingReservationGuest->id)
                        ->first();
                    
                    if (!$existingRoomGuest) {
                        // Crear solo el registro en reservation_room_guests
                        \DB::table('reservation_room_guests')->insert([
                            'reservation_room_id' => $reservationRoom->id,
                            'reservation_guest_id' => $existingReservationGuest->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    // Crear registro en reservation_guests
                    $reservationGuestId = \DB::table('reservation_guests')->insertGetId([
                        'reservation_room_id' => $reservationRoom->id,
                        'guest_id' => $guestId,
                        'is_primary' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    // Crear registro en reservation_room_guests
                    \DB::table('reservation_room_guests')->insert([
                        'reservation_room_id' => $reservationRoom->id,
                        'reservation_guest_id' => $reservationGuestId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error assigning guests to room', [
                'reservation_room_id' => $reservationRoom->id,
                'guest_ids' => $validGuestIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Find rate for a specific number of guests in a room.
     * Similar to RoomManager::findRateForGuests().
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
     * Similar a RoomManager::ensureNightForDate().
     * 
     * SINGLE SOURCE OF TRUTH para el cobro por noches:
     * - Si ya existe una noche para esa fecha, no hace nada
     * - Si no existe, crea una nueva noche con precio calculado desde tarifas
     * - El precio se calcula basándose en la cantidad de huéspedes de la reserva
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

            // Cargar tarifas de la habitación si no están cargadas
            $room->loadMissing('rates');

            // Calcular cantidad de huéspedes de la reserva
            $totalGuests = (int)($reservation->total_guests ?? 1);
            if ($totalGuests <= 0) {
                $totalGuests = 1; // Mínimo 1 huésped
            }

            // Calcular precio usando findRateForGuests
            $price = $this->findRateForGuests($room, $totalGuests);

            // Si el precio es 0, usar el total_amount dividido entre noches como fallback
            if ($price <= 0) {
                $checkIn = Carbon::parse($reservation->check_in_date);
                $checkOut = Carbon::parse($reservation->check_out_date);
                $totalNights = max(1, $checkIn->diffInDays($checkOut));
                $totalAmount = (float)($reservation->total_amount ?? 0);

                if ($totalNights > 0 && $totalAmount > 0) {
                    $price = round($totalAmount / $totalNights, 2);
                }

                // Si aún es 0, usar base_price_per_night como último recurso
                if ($price <= 0) {
                    $price = (float)($room->base_price_per_night ?? 0);
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
                'guests' => $totalGuests
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
     * Assign guests to reservation (legacy format for single-room reservations).
     * NOTE: This method works with the actual database structure where guests are assigned to reservation_rooms.
     */
    private function assignGuestsToReservationLegacy(Reservation $reservation, array $guestIds): void
    {
        // Filter valid guest IDs
        $validGuestIds = array_filter($guestIds, function ($id): bool {
            return !empty($id) && is_numeric($id) && $id > 0;
        });

        if (empty($validGuestIds)) {
            return;
        }

        // Verify guests exist in database
        $validGuestIds = Customer::withoutGlobalScopes()
            ->whereIn('id', $validGuestIds)
            ->pluck('id')
            ->toArray();

        if (empty($validGuestIds)) {
            return;
        }

        try {
            // Get the first reservation room (for single-room reservations)
            $reservationRoom = $reservation->reservationRooms()->first();
            
            if (!$reservationRoom) {
                \Log::warning('No reservation room found for guest assignment', [
                    'reservation_id' => $reservation->id,
                    'guest_ids' => $validGuestIds,
                ]);
                return;
            }

            // Use transaction to ensure data integrity
            DB::transaction(function () use ($validGuestIds, $reservationRoom, $reservation) {
                foreach ($validGuestIds as $index => $guestId) {
                    // Check if guest is already assigned to this reservation room
                    $existingReservationGuestId = DB::table('reservation_guests')
                        ->where('guest_id', $guestId)
                        ->whereExists(function ($query) use ($reservationRoom) {
                            $query->select(DB::raw(1))
                                ->from('reservation_room_guests')
                                ->whereColumn('reservation_room_guests.reservation_guest_id', 'reservation_guests.id')
                                ->where('reservation_room_guests.reservation_room_id', $reservationRoom->id);
                        })
                        ->value('id');

                    if ($existingReservationGuestId) {
                        // Guest already assigned, skip to next
                        \Log::info('Guest already assigned to reservation room, skipping', [
                            'guest_id' => $guestId,
                            'reservation_room_id' => $reservationRoom->id,
                            'existing_reservation_guest_id' => $existingReservationGuestId,
                        ]);
                        continue;
                    }

                    // Create reservation_guest entry
                    $reservationGuestId = DB::table('reservation_guests')->insertGetId([
                        'guest_id' => $guestId,
                        'is_primary' => $index === 0, // First guest is primary
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Link to reservation room
                    $reservationRoomGuestId = DB::table('reservation_room_guests')->insertGetId([
                        'reservation_room_id' => $reservationRoom->id,
                        'reservation_guest_id' => $reservationGuestId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    \Log::info('Guest assigned to reservation room', [
                        'reservation_id' => $reservation->id,
                        'reservation_room_id' => $reservationRoom->id,
                        'reservation_guest_id' => $reservationGuestId,
                        'reservation_room_guest_id' => $reservationRoomGuestId,
                        'guest_id' => $guestId,
                        'is_primary' => $index === 0,
                    ]);
                }
            });

            \Log::info('Guests assigned to reservation successfully', [
                'reservation_id' => $reservation->id,
                'reservation_room_id' => $reservationRoom->id,
                'guest_ids' => $validGuestIds,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error assigning guests to reservation: ' . $e->getMessage(), [
                'reservation_id' => $reservation->id,
                'guest_ids' => $validGuestIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
