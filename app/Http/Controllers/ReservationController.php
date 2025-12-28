<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Customer;
use App\Models\Room;
use App\Models\DianIdentificationDocument;
use App\Models\DianLegalOrganization;
use App\Models\DianCustomerTribute;
use App\Models\DianMunicipality;
use App\Http\Requests\StoreReservationRequest;
use App\Services\AuditService;
use App\Services\ReservationReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function index(Request $request)
    {
        Carbon::setLocale('es');
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

        $rooms = Room::with([
            'reservations' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where(function ($q) use ($startOfMonth, $endOfMonth) {
                    $q->where('check_in_date', '<=', $endOfMonth)
                        ->where('check_out_date', '>=', $startOfMonth);
                });
            },
            'reservations.customer',
        ])->orderBy('room_number')->get();

        // Asegurarse de que el status se maneje como string para la vista si es necesario,
        // aunque Blade puede manejar el enum.

        $reservations = Reservation::with(['customer', 'room'])->latest()->paginate(10);

        return view('reservations.index', compact(
            'reservations',
            'rooms',
            'daysInMonth',
            'view',
            'date'
        ));
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
            $rooms = Room::where('status', '!=', \App\Enums\RoomStatus::MANTENIMIENTO)->get();

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
            // Log the incoming request data for debugging
            \Log::info('Reservation store request', [
                'all_data' => $request->all(),
                'customer_id' => $request->customer_id,
                'room_id' => $request->room_id,
                'room_ids' => $request->room_ids,
                'total_amount' => $request->total_amount,
                'deposit' => $request->deposit,
                'guests_count' => $request->guests_count,
            ]);

            $data = $request->validated();

            // Determine if using multiple rooms or single room (backward compatibility)
            $roomIds = $request->has('room_ids') && is_array($request->room_ids)
                ? $request->room_ids
                : ($request->room_id ? [$request->room_id] : []);

            if (empty($roomIds)) {
                return back()->withInput()->withErrors(['room_id' => 'Debe seleccionar al menos una habitación.']);
            }

            // Validate dates and availability
            $checkInDate = Carbon::parse($request->check_in_date);
            $checkOutDate = Carbon::parse($request->check_out_date);

            $dateValidation = $this->validateDates($checkInDate, $checkOutDate);
            if (!$dateValidation['valid']) {
                return back()->withInput()->withErrors($dateValidation['errors']);
            }

            // Validate availability for all rooms
            $availabilityErrors = $this->validateRoomsAvailability($roomIds, $checkInDate, $checkOutDate);
            if (!empty($availabilityErrors)) {
                return back()->withInput()->withErrors($availabilityErrors);
            }

            // Validate guest assignment
            $roomGuests = $request->room_guests ?? [];

            // Normalize roomGuests keys to integers (form sends them as strings)
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

            // Remove payment_method from data if not provided (it's optional)
            if (!isset($data['payment_method']) || empty($data['payment_method'])) {
                unset($data['payment_method']);
            }

            // For backward compatibility, use first room_id for the room_id field
            $data['room_id'] = $roomIds[0];

            $reservation = Reservation::create($data);

            // Attach all rooms to reservation via pivot table
            foreach ($roomIds as $roomId) {
                $reservationRoom = ReservationRoom::create([
                    'reservation_id' => $reservation->id,
                    'room_id' => $roomId,
                ]);

                // Assign guests to this specific room if provided (use normalized array)
                $roomIdInt = is_numeric($roomId) ? (int) $roomId : 0;
                $this->assignGuestsToRoom($reservationRoom, $normalizedRoomGuests[$roomIdInt] ?? []);
            }

            // Backward compatibility: Assign guests to reservation if using old format
            if ($request->has('guest_ids') && is_array($request->guest_ids) && !$request->has('room_guests')) {
                $this->assignGuestsToReservationLegacy($reservation, $request->guest_ids);
            }

            // Mark rooms as occupied if check-in is today
            if ($checkInDate->isToday()) {
                Room::whereIn('id', $roomIds)->update(['status' => \App\Enums\RoomStatus::OCUPADA]);
            }

            // Audit log for reservation creation
            $this->auditService->logReservationCreated($reservation, $request, $roomIds);

            // Dispatch Livewire event for stats update
            $this->safeLivewireDispatch('reservation-created');

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
        $customers = Customer::withoutGlobalScopes()
            ->with('taxProfile')
            ->orderBy('name')
            ->get();
        $rooms = Room::all(); // Show all rooms for edit
        return view('reservations.edit', compact('reservation', 'customers', 'rooms'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreReservationRequest $request, Reservation $reservation)
    {
        $exists = Reservation::where('room_id', $request->room_id)
            ->where('id', '!=', $reservation->id)
            ->where(function ($query) use ($request) {
                $query->where('check_in_date', '<', $request->check_out_date)
                      ->where('check_out_date', '>', $request->check_in_date);
            })
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['room_id' => 'La habitación ya está reservada para las fechas seleccionadas.']);
        }

        // Capture old values for audit
        $oldValues = [
            'room_id' => $reservation->room_id,
            'check_in_date' => $reservation->check_in_date?->format('Y-m-d'),
            'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
            'total_amount' => (float) $reservation->total_amount,
            'deposit' => (float) $reservation->deposit,
            'guests_count' => (int) $reservation->guests_count,
            'payment_method' => $reservation->payment_method,
        ];

        $reservation->update($request->validated());

        // Refresh to get updated values
        $reservation->refresh();

        // Audit log for reservation update
        $this->auditService->logReservationUpdated($reservation, $request, $oldValues);

        // Dispatch Livewire event for stats update
        $this->safeLivewireDispatch('reservation-updated');

        return redirect()->route('reservations.index')->with('success', 'Reserva actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation)
    {
        $reservation->delete();

        // Audit log for reservation cancellation
        $this->auditService->logReservationCancelled($reservation, request());

        // Dispatch Livewire event for stats update
        $this->safeLivewireDispatch('reservation-cancelled');

        return redirect()->route('reservations.index')->with('success', 'Reserva cancelada correctamente.');
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
     * Export monthly reservations report as PDF.
     */
    public function exportMonthlyReport(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));

        try {
            Carbon::createFromFormat('Y-m', (string) $month);
        } catch (\Exception $e) {
            return back()->withErrors([
                'month' => 'Formato de mes inválido. Use YYYY-MM.',
            ]);
        }

        $reportData = $this->reportService->getMonthlyReservations((string) $month);
        $pdf = Pdf::loadView('reservations.monthly-report-pdf', $reportData);

        return $pdf->download("Reporte_Reservaciones_{$month}.pdf");
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
            // If price_additional_person is set, use it; otherwise calculate from price_2_persons - price_1_person
            $defaultPrice = (float) ($room->price_per_night ?? 0);
            $price1Person = (float) ($room->price_1_person ?? $defaultPrice);
            $price2Persons = (float) ($room->price_2_persons ?? $defaultPrice);
            $priceAdditionalPerson = (float) ($room->price_additional_person ?? 0);

            // If price_additional_person is 0 or not set, calculate it from the difference
            if ($priceAdditionalPerson == 0 && $price2Persons > $price1Person) {
                $priceAdditionalPerson = $price2Persons - $price1Person;
            }

            // Ensure status is always a string value
            $statusValue = $room->status instanceof \App\Enums\RoomStatus
                ? $room->status->value
                : (string) ($room->status ?? 'libre');

            return [
                'id' => (int) $room->id,
                'number' => (string) ($room->room_number ?? ''),
                'beds' => (int) ($room->beds_count ?? 0),
                'price' => $defaultPrice, // Keep for backward compatibility
                'occupancyPrices' => $occupancyPrices, // Prices by number of guests
                'price1Person' => $price1Person, // Base price for 1 person
                'price2Persons' => $price2Persons, // Price for 2 persons (for calculation fallback)
                'priceAdditionalPerson' => $priceAdditionalPerson, // Additional price per person
                'capacity' => (int) ($room->max_capacity ?? 2),
                'status' => $statusValue,
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
        // Check in main reservations table (single room reservations)
        $existsInReservations = Reservation::where('room_id', $roomId)
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('check_in_date', '<', $checkOut)
                      ->where('check_out_date', '>', $checkIn);
            })
            ->when($excludeReservationId, function ($q) use ($excludeReservationId) {
                $q->where('id', '!=', $excludeReservationId);
            })
            ->exists();

        if ($existsInReservations) {
            return false;
        }

        // Check in reservation_rooms table (multi-room reservations)
        $existsInPivot = DB::table('reservation_rooms')
            ->join('reservations', 'reservation_rooms.reservation_id', '=', 'reservations.id')
            ->where('reservation_rooms.room_id', $roomId)
            ->whereNull('reservations.deleted_at')
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('reservations.check_in_date', '<', $checkOut)
                      ->where('reservations.check_out_date', '>', $checkIn);
            })
            ->when($excludeReservationId, function ($q) use ($excludeReservationId) {
                $q->where('reservations.id', '!=', $excludeReservationId);
            })
            ->exists();

        return !$existsInPivot;
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

        if (!empty($validGuestIds)) {
            $reservationRoom->guests()->attach($validGuestIds);
        }
    }

    /**
     * Assign guests to reservation (legacy format for single-room reservations).
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
            $reservation->guests()->attach($validGuestIds);
        } catch (\Exception $e) {
            \Log::error('Error attaching guests to reservation: ' . $e->getMessage(), [
                'reservation_id' => $reservation->id,
                'guest_ids' => $validGuestIds,
                'exception' => $e,
            ]);
        }
    }
}
