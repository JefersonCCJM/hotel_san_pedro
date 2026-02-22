<?php

namespace App\Livewire\Reservations;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Livewire\Forms\CustomerForm;
use App\Livewire\Forms\ReservationForm;
use App\Models\Customer;
use App\Models\Room;
use App\Services\CustomerService;
use App\Services\ReservationService;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ReservationCreate extends Component
{
    use WithFileUploads;

    public ReservationForm $reservation;
    public CustomerForm $customerMain;
    public CustomerForm $customerGuest;

    public string $pageTitle = 'Nueva Reserva';
    public string $submitButtonText = 'Crear Reserva';
    public string $formAction = '';
    public string $formMethod = 'POST';
    public bool $loading = false;

    public array $rooms = [];
    public array $roomsData = [];
    public array $identificationDocuments = [];
    public array $legalOrganizations = [];
    public array $tributes = [];
    public array $municipalities = [];

    public array $customers = [];
    public bool $showCustomerDropdown = false;
    public string $customerSearchTerm = '';

    public bool $showMultiRoomSelector = false;
    public string $roomId = '';
    public string $checkInTime = '14:00';
    public $guestsDocument = null;

    private ReservationService $reservationService;
    private CustomerService $customerService;
    private RoomAvailabilityService $availabilityService;

    public function mount(
        array $rooms = [],
        array $roomsData = [],
        array $customers = [],
        array $identificationDocuments = [],
        array $legalOrganizations = [],
        array $tributes = [],
        array $municipalities = []
    ): void {
        $this->rooms = $rooms;
        $this->roomsData = $roomsData;
        $this->identificationDocuments = $identificationDocuments;
        $this->legalOrganizations = $legalOrganizations;
        $this->tributes = $tributes;
        $this->municipalities = $municipalities;

        $normalizedCustomers = $this->normalizeCustomers($customers);
        $this->customers = !empty($normalizedCustomers) ? $normalizedCustomers : [];
    }

    public function boot(): void
    {
        $this->reservationService = new ReservationService();
        $this->customerService = new CustomerService();
        $this->availabilityService = new RoomAvailabilityService();
        $this->formAction = route('reservations.store');

        $this->customerMain->mount($this->customerService);
        $this->customerGuest->mount($this->customerService);

        if (empty($this->customers)) {
            $this->refreshCustomers();
        }
    }

    #[On('customer-created')]
    #[On('customerCreated')]
    public function handleCustomerCreated(int $customerId, array $customerData = []): void
    {
        $this->refreshCustomers();

        if ($this->isCustomerLocked) {
            return;
        }

        $this->selectCustomer($customerId);
    }

    #[On('customerSelected')]
    public function handleCustomerSelected(int $customerId): void
    {
        if ($this->isCustomerLocked) {
            return;
        }

        $this->selectCustomer($customerId);
    }

    #[On('roomSelected')]
    public function handleRoomSelected(int $roomId, bool $selected = true, ?string $checkIn = null, ?string $checkOut = null): void
    {
        if ($this->areRoomsLocked) {
            return;
        }

        if ($checkIn !== null) {
            $this->reservation->checkIn = $checkIn;
        }
        if ($checkOut !== null) {
            $this->reservation->checkOut = $checkOut;
        }

        if ($selected) {
            $this->selectRoom($roomId);
            return;
        }

        $current = array_values(array_unique(array_map('intval', $this->reservation->selectedRoomIds)));
        $index = array_search((int) $roomId, $current, true);
        if ($index !== false) {
            unset($current[$index]);
        }

        $this->reservation->selectedRoomIds = array_values($current);
    }

    #[On('roomsSelectionUpdated')]
    public function handleRoomsSelectionUpdated(array $selectedRoomIds = []): void
    {
        if ($this->areRoomsLocked) {
            return;
        }

        $normalized = array_values(array_unique(array_filter(array_map('intval', $selectedRoomIds), fn (int $id) => $id > 0)));
        $this->reservation->selectedRoomIds = $normalized;

        if (!$this->showMultiRoomSelector) {
            $first = (int) ($normalized[0] ?? 0);
            $this->roomId = $first > 0 ? (string) $first : '';
        }

        $this->enforceGuestInputWithinRoomLimits();
    }

    #[On('guestsUpdated')]
    public function handleGuestsUpdated(array $roomGuests): void
    {
        $this->reservation->roomGuests = $roomGuests;
    }

    #[On('pricingUpdated')]
    public function handlePricingUpdated(int $total, int $deposit): void
    {
        $this->reservation->total = $total;
        $this->reservation->deposit = $deposit;
    }

    public function getDatesCompletedProperty(): bool
    {
        if (empty($this->reservation->checkIn) || empty($this->reservation->checkOut)) {
            return false;
        }

        try {
            return Carbon::parse($this->reservation->checkOut)
                ->gt(Carbon::parse($this->reservation->checkIn));
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getFilteredCustomersProperty(): array
    {
        $allCustomers = $this->customers ?? [];

        if (empty($this->customerSearchTerm)) {
            return array_slice($allCustomers, 0, 5);
        }

        $searchTerm = $this->normalizeSearchValue($this->customerSearchTerm);
        $filtered = [];

        foreach ($allCustomers as $customer) {
            $name = $this->normalizeSearchValue((string) ($customer['name'] ?? ''));
            $identification = $this->normalizeSearchValue((string) ($customer['taxProfile']['identification'] ?? ''));
            $phone = $this->normalizeSearchValue((string) ($customer['phone'] ?? ''));

            if (
                str_contains($name, $searchTerm)
                || str_contains($identification, $searchTerm)
                || str_contains($phone, $searchTerm)
            ) {
                $filtered[] = $customer;
            }

            if (count($filtered) >= 20) {
                break;
            }
        }

        return $filtered;
    }

    public function getSelectedCustomerInfoProperty(): ?array
    {
        $selectedCustomerId = (int) ($this->reservation->customerId ?? 0);
        if ($selectedCustomerId <= 0) {
            return null;
        }

        $customer = collect($this->customers)->first(function (array $item) use ($selectedCustomerId) {
            return (int) ($item['id'] ?? 0) === $selectedCustomerId;
        });

        if (!$customer) {
            return null;
        }

        return [
            'id' => $customer['taxProfile']['identification'] ?? 'S/N',
            'phone' => $customer['phone'] ?? 'S/N',
        ];
    }

    public function openCustomerDropdown(): void
    {
        if ($this->isCustomerLocked) {
            return;
        }

        $this->showCustomerDropdown = true;
    }

    public function closeCustomerDropdown(): void
    {
        $this->showCustomerDropdown = false;
    }

    public function updatedCustomerSearchTerm(string $value): void
    {
        if ($this->isCustomerLocked) {
            return;
        }

        $this->showCustomerDropdown = true;
    }

    public function selectCustomer(int $customerId): void
    {
        if ($this->isCustomerLocked) {
            return;
        }

        if ($customerId <= 0) {
            return;
        }

        $this->reservation->customerId = $customerId;
        $this->customerSearchTerm = '';
        $this->showCustomerDropdown = false;
    }

    public function clearCustomerSelection(): void
    {
        if ($this->isCustomerLocked) {
            return;
        }

        $this->reservation->customerId = 0;
        $this->customerSearchTerm = '';
        $this->showCustomerDropdown = false;
    }

    public function getAvailableRoomsProperty(): array
    {
        if (!$this->datesCompleted) {
            return [];
        }

        try {
            $checkIn = Carbon::parse($this->reservation->checkIn)->startOfDay();
            $checkOut = Carbon::parse($this->reservation->checkOut)->startOfDay();

            if ($checkOut->lte($checkIn)) {
                return [];
            }

            $candidateRooms = [];
            if (!empty($this->roomsData)) {
                $candidateRooms = $this->normalizeRooms($this->roomsData);
            } elseif (!empty($this->rooms)) {
                $candidateRooms = $this->normalizeRooms($this->rooms);
            }

            return $this->availabilityService->getAvailableRooms(
                $checkIn,
                $checkOut,
                $candidateRooms,
                $this->editingReservationId
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getSelectedRoomProperty(): ?array
    {
        $selectedRoomId = $this->getSelectedSingleRoomId();
        if ($selectedRoomId <= 0) {
            return null;
        }

        $sources = [
            $this->availableRooms,
            $this->normalizeRooms($this->roomsData),
            $this->normalizeRooms($this->rooms),
        ];

        foreach ($sources as $rooms) {
            foreach ($rooms as $room) {
                if ((int) ($room['id'] ?? 0) !== $selectedRoomId) {
                    continue;
                }

                return [
                    'id' => $selectedRoomId,
                    'number' => $room['number'] ?? $room['room_number'] ?? null,
                    'room_number' => $room['room_number'] ?? $room['number'] ?? null,
                    'capacity' => (int) ($room['capacity'] ?? $room['max_capacity'] ?? 0),
                    'beds' => (int) ($room['beds'] ?? $room['beds_count'] ?? 0),
                ];
            }
        }

        return null;
    }

    public function getMinCheckInDateProperty(): string
    {
        return Carbon::today()->format('Y-m-d');
    }

    public function getMinCheckOutDateProperty(): string
    {
        if (empty($this->reservation->checkIn)) {
            return '';
        }

        try {
            return Carbon::parse($this->reservation->checkIn)->addDay()->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function getIsCheckOutDisabledProperty(): bool
    {
        return empty($this->reservation->checkIn);
    }

    public function getIsCustomerLockedProperty(): bool
    {
        return false;
    }

    public function getAreRoomsLockedProperty(): bool
    {
        return false;
    }

    public function toggleMultiRoomMode(): void
    {
        if ($this->areRoomsLocked) {
            return;
        }

        $this->showMultiRoomSelector = !$this->showMultiRoomSelector;

        if ($this->showMultiRoomSelector) {
            $single = (int) $this->roomId;
            $this->reservation->selectedRoomIds = $single > 0 ? [$single] : [];
            $this->enforceGuestInputWithinRoomLimits();
            return;
        }

        $firstSelected = (int) ($this->reservation->selectedRoomIds[0] ?? 0);
        $this->roomId = $firstSelected > 0 ? (string) $firstSelected : '';
        $this->reservation->selectedRoomIds = $firstSelected > 0 ? [$firstSelected] : [];
        $this->enforceGuestInputWithinRoomLimits();
    }

    public function selectRoom(int $roomId): void
    {
        if ($this->areRoomsLocked) {
            return;
        }

        $roomId = (int) $roomId;
        if ($roomId <= 0) {
            return;
        }

        if ($this->showMultiRoomSelector) {
            $current = array_values(array_unique(array_map('intval', $this->reservation->selectedRoomIds)));
            $index = array_search($roomId, $current, true);

            if ($index !== false) {
                unset($current[$index]);
            } else {
                $current[] = $roomId;
            }

            $this->reservation->selectedRoomIds = array_values($current);
            $this->enforceGuestInputWithinRoomLimits();
            return;
        }

        $this->roomId = (string) $roomId;
        $this->reservation->selectedRoomIds = [$roomId];
        $this->enforceGuestInputWithinRoomLimits();
    }

    public function clearSelectedRooms(): void
    {
        if ($this->areRoomsLocked) {
            return;
        }

        $this->roomId = '';
        $this->reservation->selectedRoomIds = [];
        $this->reservation->roomGuests = [];
    }

    public function updatedReservationCheckIn($value): void
    {
        if ($this->areRoomsLocked) {
            return;
        }

        if (empty($value)) {
            $this->reservation->checkOut = '';
            $this->clearSelectedRooms();
            return;
        }

        try {
            $checkIn = Carbon::parse((string) $value)->startOfDay();
            if ($checkIn->lt(Carbon::today())) {
                $this->reservation->checkIn = Carbon::today()->format('Y-m-d');
                $checkIn = Carbon::today();
            }

            if (!empty($this->reservation->checkOut)) {
                $checkOut = Carbon::parse((string) $this->reservation->checkOut)->startOfDay();
                if ($checkOut->lte($checkIn)) {
                    $this->reservation->checkOut = '';
                }
            }
        } catch (\Throwable $e) {
            $this->reservation->checkIn = '';
            $this->reservation->checkOut = '';
        }

        $this->clearSelectedRooms();
    }

    public function updatedReservationCheckOut($value): void
    {
        if ($this->areRoomsLocked) {
            return;
        }

        if (empty($value)) {
            $this->clearSelectedRooms();
            return;
        }

        if (empty($this->reservation->checkIn)) {
            $this->reservation->checkOut = '';
            $this->clearSelectedRooms();
            return;
        }

        try {
            $checkIn = Carbon::parse((string) $this->reservation->checkIn)->startOfDay();
            $checkOut = Carbon::parse((string) $value)->startOfDay();

            if ($checkOut->lte($checkIn)) {
                $this->reservation->checkOut = '';
            }
        } catch (\Throwable $e) {
            $this->reservation->checkOut = '';
        }

        $this->clearSelectedRooms();
    }

    public function updatedReservationAdults($value): void
    {
        $this->enforceGuestInputWithinRoomLimits();
    }

    public function updatedReservationChildren($value): void
    {
        $this->enforceGuestInputWithinRoomLimits();
    }

    public function getAutoCalculatedTotalProperty(): float
    {
        return $this->getReservationTotalValue();
    }

    public function restoreSuggestedTotal(): void
    {
        $this->reservation->total = (int) round($this->autoCalculatedTotal);
    }

    public function getBalanceProperty(): int
    {
        $total = $this->getReservationTotalValue();
        $deposit = $this->getReservationDepositValue();

        return (int) max(0, $total - $deposit);
    }

    public function getIsReceiptReadyProperty(): bool
    {
        $total = $this->getReservationTotalValue();
        $deposit = $this->getReservationDepositValue();

        return $total > 0 && $deposit >= 0 && $deposit <= $total;
    }

    public function getStatusProperty(): string
    {
        return $this->balance <= 0 && $this->isReceiptReady ? 'Liquidado' : 'Pendiente';
    }

    public function downloadReceipt()
    {
        if (!$this->isReceiptReady) {
            return null;
        }

        $total = $this->getReservationTotalValue();
        $deposit = $this->getReservationDepositValue();
        $paymentMethod = $this->getReservationPaymentMethodValue();
        $paymentMethodLabel = $this->getReservationPaymentMethodLabel();
        $balance = max(0, $total - $deposit);
        $status = $balance <= 0 ? 'Liquidado' : 'Pendiente';
        $issuedAt = now();

        $checkInDate = null;
        $checkOutDate = null;
        $nights = 0;

        if (!empty($this->reservation->checkIn)) {
            try {
                $checkInDate = Carbon::parse((string) $this->reservation->checkIn)->startOfDay();
            } catch (\Throwable $e) {
                $checkInDate = null;
            }
        }

        if (!empty($this->reservation->checkOut)) {
            try {
                $checkOutDate = Carbon::parse((string) $this->reservation->checkOut)->startOfDay();
            } catch (\Throwable $e) {
                $checkOutDate = null;
            }
        }

        if ($checkInDate && $checkOutDate && $checkOutDate->gt($checkInDate)) {
            $nights = $checkInDate->diffInDays($checkOutDate);
        }

        $customerId = (int) ($this->reservation->customerId ?? 0);
        $selectedCustomer = collect($this->customers)->first(
            fn (array $customer): bool => (int) ($customer['id'] ?? 0) === $customerId
        );

        $customerName = (string) ($selectedCustomer['name'] ?? 'Cliente no seleccionado');
        $customerIdentification = (string) ($selectedCustomer['taxProfile']['identification'] ?? 'S/N');
        $customerPhone = (string) ($selectedCustomer['phone'] ?? 'S/N');

        $selectedRoomIds = array_values(array_unique(array_map('intval', $this->reservation->selectedRoomIds ?? [])));
        if (empty($selectedRoomIds) && (int) $this->roomId > 0) {
            $selectedRoomIds = [(int) $this->roomId];
        }

        $normalizedRooms = !empty($this->roomsData)
            ? $this->normalizeRooms($this->roomsData)
            : $this->normalizeRooms($this->rooms);

        $roomsById = [];
        foreach ($normalizedRooms as $room) {
            $id = (int) ($room['id'] ?? 0);
            if ($id > 0) {
                $roomsById[$id] = $room;
            }
        }

        $roomSummaries = [];
        foreach ($selectedRoomIds as $roomId) {
            $room = $roomsById[$roomId] ?? null;
            if ($room === null) {
                $roomSummaries[] = 'Habitacion ID ' . $roomId;
                continue;
            }

            $roomNumber = (string) ($room['room_number'] ?? $room['number'] ?? $roomId);
            $beds = (int) ($room['beds'] ?? 0);

            if ($beds > 0) {
                $roomSummaries[] = 'Habitacion ' . $roomNumber . ' (' . $beds . ' ' . ($beds === 1 ? 'Cama' : 'Camas') . ')';
            } else {
                $roomSummaries[] = 'Habitacion ' . $roomNumber;
            }
        }

        $pdf = Pdf::loadView('reservations.receipt-preview-pdf', [
            'issuedAt' => $issuedAt,
            'customerName' => $customerName,
            'customerIdentification' => $customerIdentification,
            'customerPhone' => $customerPhone,
            'checkInDate' => $checkInDate,
            'checkOutDate' => $checkOutDate,
            'checkInTime' => $this->checkInTime,
            'nights' => $nights,
            'roomSummaries' => $roomSummaries,
            'totalAmount' => $total,
            'depositAmount' => $deposit,
            'paymentMethod' => $paymentMethod,
            'paymentMethodLabel' => $paymentMethodLabel,
            'balanceDue' => $balance,
            'status' => $status,
            'notes' => (string) ($this->reservation->notes ?? ''),
        ])->setPaper('a4', 'portrait');

        $fileToken = $issuedAt->format('Ymd-His');
        $pdfContent = $pdf->output();

        return response()->streamDownload(function () use ($pdfContent): void {
            echo $pdfContent;
        }, 'Comprobante_Reserva_' . $fileToken . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function removeGuestsDocument(): void
    {
        if (is_object($this->guestsDocument) && method_exists($this->guestsDocument, 'getFilename')) {
            $this->_removeUpload('guestsDocument', $this->guestsDocument->getFilename());
        } else {
            $this->guestsDocument = null;
        }

        $this->resetValidation('guestsDocument');
    }

    public function createReservation()
    {
        $storedDocumentPath = null;

        try {
            $this->loading = true;

            $this->enforceGuestInputWithinRoomLimits();
            $this->reservation->validate();
            $this->validateGuestsDocument();

            $reservationData = [
                'customerId' => $this->reservation->customerId,
                'check_in_date' => $this->reservation->checkIn,
                'check_out_date' => $this->reservation->checkOut,
                'check_in_time' => $this->checkInTime,
                'room_ids' => $this->reservation->selectedRoomIds,
                'total_amount' => $this->getReservationTotalValue(),
                'deposit' => $this->getReservationDepositValue(),
                'payment_method' => $this->getReservationPaymentMethodValue(),
                'guests_count' => $this->getGuestsCountValue(),
                'adults' => $this->getReservationAdultsValue(),
                'children' => $this->getReservationChildrenValue(),
                'room_guests' => $this->reservation->roomGuests ?? [],
                'notes' => $this->reservation->notes,
            ];

            $checkIn = Carbon::parse($this->reservation->checkIn)->startOfDay();
            $checkOut = Carbon::parse($this->reservation->checkOut)->startOfDay();
            $selectedRoomIds = array_values(array_unique(array_map('intval', $this->reservation->selectedRoomIds)));

            $roomConflicts = $this->getSelectedRoomConflicts($selectedRoomIds, $checkIn, $checkOut);
            if (!empty($roomConflicts)) {
                $this->addError('reservation.selectedRoomIds', 'Las habitaciones seleccionadas tienen conflicto de ocupacion para las fechas indicadas.');
                $this->dispatch('notify', type: 'error', message: 'Revisa las habitaciones seleccionadas. Hay conflictos de ocupacion.');
                return;
            }

            $validationErrors = [];
            $this->validateGuestInputAgainstRoomLimits($validationErrors);
            $this->reservationService->validateReservationData($reservationData, $validationErrors);

            if (!empty($validationErrors)) {
                $normalizedValidationErrors = [];
                foreach ($validationErrors as $field => $message) {
                    $normalizedValidationErrors[$this->normalizeValidationErrorField((string) $field)] = $message;
                }
                throw ValidationException::withMessages($normalizedValidationErrors);
            }

            $documentData = $this->storeGuestsDocument();
            $storedDocumentPath = $documentData['guests_document_path'] ?? null;
            $reservationData = array_merge($reservationData, $documentData);

            $reservation = $this->reservationService->createReservation($reservationData);
            $this->dispatch('reservationCreated', reservationId: $reservation->id);
            $this->guestsDocument = null;

            $month = $checkIn->format('Y-m');
            return redirect()
                ->route('reservations.index', ['view' => 'calendar', 'month' => $month])
                ->with('success', 'Reserva registrada exitosamente.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($this->normalizeValidationErrorField((string) $field), $message);
                }
            }
            $this->dispatch('notify', type: 'error', message: 'Revisa los campos marcados en rojo.');
        } catch (\Exception $e) {
            if (!empty($storedDocumentPath)) {
                Storage::disk('public')->delete($storedDocumentPath);
            }

            \Log::error('Error creating reservation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->addError('general', 'Error al crear la reserva: ' . $e->getMessage());
            $this->dispatch('notify', type: 'error', message: 'No fue posible crear la reserva.');
        } finally {
            $this->loading = false;
        }
    }

    public function createMainCustomer(): void
    {
        try {
            $this->customerMain->validate();
            $customer = $this->customerMain->create();
            $this->handleCustomerCreated($customer->id);
            $this->customerMain->resetCustomer();
            session()->flash('message', 'Cliente creado exitosamente.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
        }
    }

    public function createAndAddGuest(): void
    {
        try {
            $this->customerGuest->validate();
            $customer = $this->customerGuest->create();

            $currentRoom = $this->reservation->selectedRoomIds[0] ?? null;
            if ($currentRoom) {
                $this->dispatch('guestAdded', customerId: $customer->id, roomId: $currentRoom);
            }

            $this->customerGuest->resetCustomer();
            session()->flash('message', 'Huesped creado y asignado exitosamente.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.reservations.reservation-create', [
            'datesCompleted' => $this->datesCompleted,
            'isCustomerLocked' => $this->isCustomerLocked,
            'areRoomsLocked' => $this->areRoomsLocked,
            'filteredCustomers' => $this->filteredCustomers,
            'selectedCustomerInfo' => $this->selectedCustomerInfo,
            'availableRooms' => $this->availableRooms,
            'selectedRoom' => $this->selectedRoom,
            'reservationTotal' => $this->getReservationTotalValue(),
            'reservationDeposit' => $this->getReservationDepositValue(),
            'balance' => $this->balance,
            'autoCalculatedTotal' => $this->autoCalculatedTotal,
            'isReceiptReady' => $this->isReceiptReady,
            'status' => $this->status,
            'reservationPaymentMethod' => $this->getReservationPaymentMethodValue(),
            'reservationPaymentMethodLabel' => $this->getReservationPaymentMethodLabel(),
            'isCreateMode' => $this->editingReservationId === null,
        ]);
    }

    public function getEditingReservationIdProperty(): ?int
    {
        return null;
    }

    public function getExistingGuestsDocumentProperty(): ?array
    {
        return null;
    }

    /**
     * @param array<int, int> $roomIds
     * @return array<int, array<string, mixed>>
     */
    protected function getSelectedRoomConflicts(array $roomIds, Carbon $checkIn, Carbon $checkOut): array
    {
        if (empty($roomIds)) {
            return [];
        }

        $conflicts = [];

        foreach ($roomIds as $roomId) {
            $roomIdInt = (int) $roomId;
            if ($roomIdInt <= 0) {
                continue;
            }

            $isAvailable = $this->availabilityService->isRoomAvailableForDates(
                $roomIdInt,
                $checkIn,
                $checkOut,
                $this->editingReservationId
            );

            if ($isAvailable) {
                continue;
            }

            $roomNumber = Room::query()->whereKey($roomIdInt)->value('room_number');
            $conflicts[] = [
                'roomId' => $roomIdInt,
                'roomNumber' => $roomNumber ?: (string) $roomIdInt,
                'reason' => 'availability_conflict',
            ];
        }

        return $conflicts;
    }

    private function getSelectedSingleRoomId(): int
    {
        if ($this->showMultiRoomSelector) {
            return (int) ($this->reservation->selectedRoomIds[0] ?? 0);
        }

        $roomId = (int) $this->roomId;
        if ($roomId > 0) {
            return $roomId;
        }

        return (int) ($this->reservation->selectedRoomIds[0] ?? 0);
    }

    private function refreshCustomers(): void
    {
        $this->customers = Customer::query()
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get([
                'id',
                'name',
                'phone',
                'identification_number',
            ])
            ->map(function (Customer $customer): array {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone ?: 'S/N',
                    'taxProfile' => [
                        'identification' => $customer->identification_number ?: 'S/N',
                    ],
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeCustomers(array $customers): array
    {
        $normalized = [];

        foreach ($customers as $customer) {
            if (!is_array($customer)) {
                continue;
            }

            $id = (int) ($customer['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $identification = (string) (
                $customer['taxProfile']['identification']
                ?? $customer['tax_profile']['identification']
                ?? $customer['identification_number']
                ?? 'S/N'
            );

            $normalized[] = [
                'id' => $id,
                'name' => (string) ($customer['name'] ?? ''),
                'phone' => (string) ($customer['phone'] ?? 'S/N'),
                'taxProfile' => [
                    'identification' => $identification !== '' ? $identification : 'S/N',
                ],
            ];
        }

        return $normalized;
    }

    private function normalizeRooms(array $rooms): array
    {
        $normalized = [];

        foreach ($rooms as $room) {
            if (!is_array($room)) {
                continue;
            }

            $id = (int) ($room['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $number = (string) ($room['room_number'] ?? $room['number'] ?? '');
            if ($number === '') {
                $number = (string) $id;
            }

            $normalized[] = [
                'id' => $id,
                'number' => $number,
                'room_number' => $number,
                'capacity' => (int) ($room['capacity'] ?? $room['max_capacity'] ?? 0),
                'max_capacity' => (int) ($room['max_capacity'] ?? $room['capacity'] ?? 0),
                'beds' => (int) ($room['beds'] ?? $room['beds_count'] ?? 0),
            ];
        }

        return $normalized;
    }

    public function getGuestCapacityRulesProperty(): array
    {
        return $this->calculateGuestCapacityRules();
    }

    /**
     * @return array<int, int>
     */
    private function getNormalizedSelectedRoomIds(): array
    {
        $selectedRoomIds = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $this->reservation->selectedRoomIds ?? []),
                    static fn (int $id): bool => $id > 0
                )
            )
        );

        if (empty($selectedRoomIds) && (int) $this->roomId > 0) {
            $selectedRoomIds = [(int) $this->roomId];
        }

        return $selectedRoomIds;
    }

    /**
     * @return array<int, array<string, int>>
     */
    private function getRoomsLookupForGuestLimits(): array
    {
        $lookup = [];
        $sources = [
            $this->availableRooms,
            $this->normalizeRooms($this->roomsData),
            $this->normalizeRooms($this->rooms),
        ];

        foreach ($sources as $rooms) {
            if (!is_array($rooms)) {
                continue;
            }

            foreach ($rooms as $room) {
                if (!is_array($room)) {
                    continue;
                }

                $roomId = (int) ($room['id'] ?? 0);
                if ($roomId <= 0 || isset($lookup[$roomId])) {
                    continue;
                }

                $lookup[$roomId] = [
                    'capacity' => (int) ($room['capacity'] ?? $room['max_capacity'] ?? 0),
                    'beds' => (int) ($room['beds'] ?? $room['beds_count'] ?? 0),
                ];
            }
        }

        return $lookup;
    }

    /**
     * @return array<string, int|bool>
     */
    private function calculateGuestCapacityRules(): array
    {
        $selectedRoomIds = $this->getNormalizedSelectedRoomIds();
        if (empty($selectedRoomIds)) {
            return [
                'has_selected_rooms' => false,
                'total_capacity' => 0,
                'children_limit_by_beds' => 0,
                'single_bed_rooms' => 0,
                'adults_max' => 0,
                'children_max' => 0,
            ];
        }

        $roomsLookup = $this->getRoomsLookupForGuestLimits();
        $totalCapacity = 0;
        $childrenLimitByBeds = 0;
        $singleBedRooms = 0;

        foreach ($selectedRoomIds as $roomId) {
            $room = $roomsLookup[$roomId] ?? null;
            if (!$room) {
                continue;
            }

            $capacity = max(0, (int) ($room['capacity'] ?? 0));
            $beds = max(0, (int) ($room['beds'] ?? 0));

            if ($capacity <= 0 && $beds > 0) {
                $capacity = $beds;
            }

            if ($capacity <= 0) {
                $capacity = 1;
            }

            $totalCapacity += $capacity;
            $childrenLimitByBeds += ($beds === 1) ? 1 : $capacity;
            if ($beds === 1) {
                $singleBedRooms++;
            }
        }

        if ($totalCapacity <= 0) {
            return [
                'has_selected_rooms' => false,
                'total_capacity' => 0,
                'children_limit_by_beds' => 0,
                'single_bed_rooms' => 0,
                'adults_max' => 0,
                'children_max' => 0,
            ];
        }

        $adultsMax = $totalCapacity;
        $childrenMax = $childrenLimitByBeds;

        return [
            'has_selected_rooms' => true,
            'total_capacity' => $totalCapacity,
            'children_limit_by_beds' => $childrenLimitByBeds,
            'single_bed_rooms' => $singleBedRooms,
            'adults_max' => $adultsMax,
            'children_max' => $childrenMax,
        ];
    }

    private function enforceGuestInputWithinRoomLimits(): void
    {
        $limits = $this->guestCapacityRules;
        if (empty($limits['has_selected_rooms'])) {
            return;
        }

        $totalCapacity = (int) ($limits['total_capacity'] ?? 0);
        $childrenLimitByBeds = (int) ($limits['children_limit_by_beds'] ?? 0);

        $rawAdults = $this->reservation->adults ?? null;
        if ($rawAdults !== null && $rawAdults !== '' && is_numeric($rawAdults)) {
            $normalizedAdults = max(0, (int) $rawAdults);
            if ($normalizedAdults > $totalCapacity) {
                $this->reservation->adults = $totalCapacity;
            }
        }

        $childrenMax = max(0, $childrenLimitByBeds);

        $rawChildren = $this->reservation->children ?? null;
        if ($rawChildren !== null && $rawChildren !== '' && is_numeric($rawChildren)) {
            $normalizedChildren = max(0, (int) $rawChildren);
            if ($normalizedChildren > $childrenMax) {
                $this->reservation->children = $childrenMax;
            }
        }
    }

    private function validateGuestInputAgainstRoomLimits(array &$validationErrors): void
    {
        $limits = $this->guestCapacityRules;
        if (empty($limits['has_selected_rooms'])) {
            return;
        }

        $totalCapacity = (int) ($limits['total_capacity'] ?? 0);
        $childrenLimitByBeds = (int) ($limits['children_limit_by_beds'] ?? 0);
        $singleBedRooms = (int) ($limits['single_bed_rooms'] ?? 0);

        $adults = $this->getReservationAdultsValue();
        $children = $this->getReservationChildrenValue();
        if ($adults > $totalCapacity) {
            $validationErrors['reservation.adults'] = 'La cantidad de adultos supera la capacidad maxima de personas de las habitaciones seleccionadas (' . $totalCapacity . ').';
        }

        if ($children > $childrenLimitByBeds) {
            $message = 'La cantidad de ninos supera el limite permitido para las habitaciones seleccionadas (' . $childrenLimitByBeds . ').';
            if ($singleBedRooms > 0) {
                $message = 'En habitaciones de 1 cama se permite maximo 1 nino por habitacion. Limite actual: ' . $childrenLimitByBeds . '.';
            }
            $validationErrors['reservation.children'] = $message;
        }
    }

    private function normalizeValidationErrorField(string $field): string
    {
        return match ($field) {
            'customerId' => 'reservation.customerId',
            'roomId', 'room_id', 'room_ids', 'selectedRoomIds' => 'reservation.selectedRoomIds',
            'dates', 'checkIn', 'check_in_date', 'checkOut', 'check_out_date' => 'reservation.checkOut',
            'total', 'total_amount' => 'reservation.total',
            'deposit', 'deposit_amount' => 'reservation.deposit',
            'payment_method', 'paymentMethod' => 'reservation.paymentMethod',
            'adults' => 'reservation.adults',
            'children' => 'reservation.children',
            default => $field,
        };
    }

    private function normalizeSearchValue(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value);

        return $value ?? '';
    }

    protected function getReservationTotalValue(): float
    {
        try {
            $total = $this->reservation->total ?? 0;
        } catch (\Throwable $e) {
            return 0.0;
        }

        return is_numeric($total) ? (float) $total : 0.0;
    }

    protected function getReservationDepositValue(): float
    {
        try {
            $deposit = $this->reservation->deposit ?? 0;
        } catch (\Throwable $e) {
            return 0.0;
        }

        return is_numeric($deposit) ? (float) $deposit : 0.0;
    }

    protected function getReservationPaymentMethodValue(): string
    {
        try {
            $paymentMethod = $this->reservation->paymentMethod ?? 'efectivo';
        } catch (\Throwable $e) {
            return 'efectivo';
        }

        if (!is_string($paymentMethod)) {
            return 'efectivo';
        }

        $normalized = strtolower(trim($paymentMethod));

        return in_array($normalized, ['efectivo', 'transferencia'], true)
            ? $normalized
            : 'efectivo';
    }

    protected function getReservationPaymentMethodLabel(): string
    {
        return $this->getReservationPaymentMethodValue() === 'transferencia'
            ? 'Transferencia'
            : 'Efectivo';
    }

    protected function getGuestsCountValue(): int
    {
        $adults = $this->getReservationAdultsValue();
        $children = $this->getReservationChildrenValue();
        $customTotal = $adults + $children;
        if ($customTotal > 0) {
            return $customTotal;
        }

        try {
            $roomGuests = $this->reservation->roomGuests ?? [];
        } catch (\Throwable $e) {
            return 1;
        }

        if (!is_array($roomGuests) || empty($roomGuests)) {
            return 1;
        }

        $count = 0;
        foreach ($roomGuests as $guestsByRoom) {
            if (!is_array($guestsByRoom)) {
                continue;
            }

            foreach ($guestsByRoom as $guest) {
                if (is_array($guest) && !empty($guest['id'])) {
                    $count++;
                }
            }
        }

        return max(1, $count);
    }

    protected function getReservationAdultsValue(): int
    {
        try {
            $adults = $this->reservation->adults ?? null;
        } catch (\Throwable $e) {
            return 0;
        }

        if ($adults === null || $adults === '') {
            return 0;
        }

        return is_numeric($adults) ? max(0, (int) $adults) : 0;
    }

    protected function getReservationChildrenValue(): int
    {
        try {
            $children = $this->reservation->children ?? null;
        } catch (\Throwable $e) {
            return 0;
        }

        if ($children === null || $children === '') {
            return 0;
        }

        return is_numeric($children) ? max(0, (int) $children) : 0;
    }

    protected function validateGuestsDocument(): void
    {
        $this->validate(
            [
                'guestsDocument' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv|max:10240',
            ],
            [
                'guestsDocument.file' => 'El documento adjunto debe ser un archivo valido.',
                'guestsDocument.mimes' => 'Formato no permitido. Usa PDF, imagen, Word o Excel.',
                'guestsDocument.max' => 'El documento no puede superar 10 MB.',
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function storeGuestsDocument(): array
    {
        if ($this->guestsDocument === null) {
            return [];
        }

        $path = $this->guestsDocument->store('reservations/guest-documents', 'public');

        return [
            'guests_document_path' => $path,
            'guests_document_original_name' => $this->guestsDocument->getClientOriginalName(),
            'guests_document_mime_type' => $this->guestsDocument->getClientMimeType(),
            'guests_document_size' => $this->guestsDocument->getSize(),
        ];
    }
}
