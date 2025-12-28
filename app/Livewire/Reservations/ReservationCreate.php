<?php

namespace App\Livewire\Reservations;

use Livewire\Component;
use App\Models\Room;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\CompanyTaxSetting;
use App\Models\DianMunicipality;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ReservationCreate extends Component
{
    // Form data
    public $customerId = '';
    public $roomId = '';
    public $selectedRoomIds = [];
    public $checkIn = '';
    public $checkOut = '';
    public $checkInTime = '14:00';
    public $total = 0;
    public $deposit = 0;
    public $guestsCount = 0;
    public $showMultiRoomSelector = false;
    public $roomGuests = [];
    public $notes = '';

    // UI State
    public $formStep = 1;
    public $datesCompleted = false;
    public $loading = false;
    public $isChecking = false;
    public $availability = null;
    public $availabilityMessage = '';

    // Data
    public $rooms = [];
    public $roomsData = [];
    public $customers = [];
    public $identificationDocuments = [];
    public $legalOrganizations = [];
    public $tributes = [];
    public $municipalities = [];

    // Customer search
    public $customerSearchTerm = '';
    public $showCustomerDropdown = false;

    // Guest assignment
    // NOTE: $assignedGuests removed - use getAssignedGuestsProperty() computed property instead
    public $currentRoomForGuestAssignment = null;
    public $guestModalOpen = false;
    public $guestModalTab = 'search';
    public $selectedGuestForAdd = null;
    public $guestSearchTerm = '';
    public $showGuestDropdown = false;

    // New customer modal (for main customer)
    public $newCustomerModalOpen = false;
    public $newMainCustomer = [
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
    public $creatingMainCustomer = false;
    public $newMainCustomerErrors = [];
    public $mainCustomerIdentificationMessage = '';
    public $mainCustomerIdentificationExists = false;
    public $mainCustomerRequiresDV = false;
    public $mainCustomerIsJuridicalPerson = false;

    // New customer for guest assignment
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
    public $newCustomerErrors = [];
    public $customerIdentificationMessage = '';
    public $customerIdentificationExists = false;
    public $customerRequiresDV = false;
    public $customerIsJuridicalPerson = false;

    protected $rules = [
        'customerId' => 'required|exists:customers,id',
        'checkIn' => 'required|date|after_or_equal:today',
        'checkOut' => 'required|date|after:checkIn',
        'checkInTime' => ['nullable', 'regex:/^([0-1]\d|2[0-3]):[0-5]\d$/'],
        'total' => 'required|numeric|min:0',
        'deposit' => 'required|numeric|min:0',
        'guestsCount' => 'nullable|integer|min:0',
    ];

    protected $messages = [
        'customerId.required' => 'Debe seleccionar un cliente.',
        'customerId.exists' => 'El cliente seleccionado no existe.',
        'checkIn.required' => 'La fecha de entrada es obligatoria.',
        'checkIn.after_or_equal' => 'La fecha de entrada no puede ser anterior al día actual.',
        'checkOut.required' => 'La fecha de salida es obligatoria.',
        'checkOut.after' => 'La fecha de salida debe ser posterior a la fecha de entrada.',
        'checkInTime.regex' => 'El formato de hora debe ser HH:MM (24 horas).',
        'total.required' => 'El total es obligatorio.',
        'total.min' => 'El total debe ser mayor o igual a 0.',
        'deposit.required' => 'El abono es obligatorio.',
        'deposit.min' => 'El abono debe ser mayor o igual a 0.',
        'guestsCount.min' => 'El número de personas no puede ser negativo.',
    ];

    public function mount(
        $rooms = [],
        $roomsData = [],
        $customers = [],
        $identificationDocuments = [],
        $legalOrganizations = [],
        $tributes = [],
        $municipalities = []
    ) {
        $this->rooms = is_array($rooms) ? $rooms : [];
        $this->roomsData = is_array($roomsData) ? $roomsData : [];
        $this->customers = is_array($customers) ? $customers : [];
        $this->identificationDocuments = is_array($identificationDocuments) ? $identificationDocuments : [];
        $this->legalOrganizations = is_array($legalOrganizations) ? $legalOrganizations : [];
        $this->tributes = is_array($tributes) ? $tributes : [];
        $this->municipalities = is_array($municipalities) ? $municipalities : [];
        $this->checkIn = now()->format('Y-m-d');
        $this->checkOut = now()->addDay()->format('Y-m-d');

        // Validate initial dates
        $this->validateDates();
    }

    public function updatedCheckIn($value)
    {
        $this->clearDateErrors();
        $this->resetAvailabilityState();

        if (empty($value)) {
            $this->setDatesIncomplete();
            $this->total = 0;
            // Clear room selections when dates are incomplete
            $this->roomId = '';
            $this->selectedRoomIds = [];
            $this->roomGuests = [];
            return;
        }

        $this->validateCheckInDate($value);

        if (!empty($this->checkOut)) {
            $this->validateCheckOutAgainstCheckIn();
        }

        $this->validateDates();

        // Clear room selections if they're no longer available after date change
        $this->clearUnavailableRooms();

        $this->calculateTotal();
        $this->checkAvailabilityIfReady();
    }

    public function updatedCheckOut($value)
    {
        $this->clearDateErrors();
        $this->resetAvailabilityState();

        if (empty($value)) {
            $this->setDatesIncomplete();
            $this->total = 0;
            // Clear room selections when dates are incomplete
            $this->roomId = '';
            $this->selectedRoomIds = [];
            $this->roomGuests = [];
            return;
        }

        if (!empty($this->checkIn)) {
            $this->validateCheckOutDate($value);
        }

        $this->validateDates();

        // Clear room selections if they're no longer available after date change
        $this->clearUnavailableRooms();

        $this->calculateTotal();
        $this->checkAvailabilityIfReady();
    }

    public function updatedNewMainCustomer($value, $key)
    {
        // Recalculate DV when identification changes
        if ($key === 'identification' && $this->mainCustomerRequiresDV) {
            $this->newMainCustomer['dv'] = $this->calculateVerificationDigit($value ?? '');
        }

        // Update required fields when document type changes
        if ($key === 'identificationDocumentId') {
            $this->updateMainCustomerRequiredFields();
        }
    }

    public function updatedNewCustomer($value, $key)
    {
        // Recalculate DV when identification changes
        if ($key === 'identification' && $this->customerRequiresDV) {
            $this->newCustomer['dv'] = $this->calculateVerificationDigit($value ?? '');
        }

        // Update required fields when document type changes
        if ($key === 'identificationDocumentId') {
            $this->updateCustomerRequiredFields();
        }
    }

    public function updatedCheckInTime($value)
    {
        if (!empty($value) && !preg_match('/^([0-1]\d|2[0-3]):[0-5]\d$/', $value)) {
            $this->addError('checkInTime', 'Formato de hora inválido. Use formato HH:MM (24 horas).');
            $this->checkInTime = '14:00';
        }
    }

    public function updatedCustomerId($value)
    {
        // Clear search when customer is selected
        if (!empty($value)) {
            $this->customerSearchTerm = '';
            $this->showCustomerDropdown = false;
        }
        // The selectedCustomerInfo is computed automatically via getSelectedCustomerInfoProperty()
    }

    public function updatedRoomId($value)
    {
        if (empty($value)) {
            $this->roomId = '';
            $this->total = 0;
            $this->resetAvailabilityState();
            return;
        }

        // Normalize to integer immediately
        $roomIdInt = is_numeric($value) ? (int) $value : 0;

        if ($roomIdInt <= 0) {
            $this->roomId = '';
            return;
        }

        // Store as string for Livewire compatibility, but always use int for roomGuests keys
        $this->roomId = (string) $roomIdInt;

        // Initialize empty roomGuests array with integer key
        if (!isset($this->roomGuests[$roomIdInt]) || !is_array($this->roomGuests[$roomIdInt])) {
            $this->roomGuests[$roomIdInt] = [];
        }

        $this->calculateTotal();
        $this->checkAvailabilityIfReady();
    }

    public function updatedSelectedRoomIds($value)
    {
        // Ensure we have an array (Livewire may pass null or empty string)
        if (!is_array($value)) {
            $this->selectedRoomIds = [];
            $this->calculateTotal();
            return;
        }

        // Filter and convert all values to integers for consistency
        $validIds = array_filter($value, function($id): bool {
            return !empty($id) && is_numeric($id) && $id > 0;
        });

        // Convert to integers and remove duplicates
        $this->selectedRoomIds = array_values(array_unique(array_map('intval', $validIds)));

        // Clean up roomGuests for rooms that are no longer selected
        if (is_array($this->roomGuests)) {
            foreach ($this->roomGuests as $roomId => $guests) {
                if (!in_array((int)$roomId, $this->selectedRoomIds, true)) {
                    unset($this->roomGuests[$roomId]);
                }
            }
        }

        $this->calculateTotal();
    }

    public function updatedGuestsCount($value)
    {
        // This method is kept for backward compatibility but guestsCount
        // is no longer used for price calculation in single room mode.
        // The total is now derived from roomGuests.
        $value = (int) $value;
        $this->guestsCount = $value;

        // Clear previous capacity errors
        $errorBag = $this->getErrorBag();
        $errorBag->forget('guestsCount');

        // Validate capacity only if room is selected (for UI feedback)
        if (!$this->showMultiRoomSelector && !empty($this->roomId)) {
            $selectedRoom = $this->selectedRoom;
            if ($selectedRoom && is_array($selectedRoom)) {
                $capacity = $selectedRoom['capacity'] ?? 0;

                if ($value > $capacity) {
                    $this->addError('guestsCount',
                        "Esta habitación admite máximo {$capacity} persona" . ($capacity > 1 ? 's' : '') . ".");
                }
            }
        }

        // Calculate total (will return 0 if capacity is exceeded or no guests assigned)
        $this->calculateTotal();
    }

    public function updatedShowMultiRoomSelector($value)
    {
        if ($value) {
            // Switching to multi-room mode: clear single room selection
            $this->roomId = '';
        } else {
            // Switching to single room mode: clear multi-room selections
            $this->selectedRoomIds = [];
            $this->roomGuests = [];
        }
        $this->calculateTotal();
    }

    public function toggleMultiRoomMode()
    {
        $this->showMultiRoomSelector = !$this->showMultiRoomSelector;

        if ($this->showMultiRoomSelector) {
            // Switching to multi-room mode: clear single room selection
            $this->roomId = '';
            // Initialize empty array if not already set
            if (!is_array($this->selectedRoomIds)) {
                $this->selectedRoomIds = [];
            }
        } else {
            // Switching to single room mode: clear multi-room selections
            $this->selectedRoomIds = [];
            $this->roomGuests = [];
        }

        $this->calculateTotal();
    }

    public function validateDates()
    {
        if (empty($this->checkIn) || empty($this->checkOut)) {
            $this->setDatesIncomplete();
            return;
        }

        // Sequential validation: parse first, then validate business rules
        try {
            $checkIn = Carbon::parse($this->checkIn)->startOfDay();
            $checkOut = Carbon::parse($this->checkOut)->startOfDay();
            $today = Carbon::today()->startOfDay();

            // Validation 1: Check-in must not be in the past (allows today)
            if ($checkIn->lt($today)) {
                $this->addError('checkIn', 'La fecha de entrada no puede ser anterior al día actual.');
                $this->setDatesIncomplete();
                return;
            }

            // Validation 2: Check-out must be after check-in (CRITICAL for business logic)
            if ($checkOut->lte($checkIn)) {
                $this->addError('checkOut', 'La fecha de salida debe ser posterior a la fecha de entrada.');
                $this->setDatesIncomplete();
                return;
            }

            // All date validations passed - mark as completed
            $this->datesCompleted = true;
            $this->formStep = 2;
        } catch (\Exception $e) {
            Log::error('Error validating dates: ' . $e->getMessage(), [
                'checkIn' => $this->checkIn,
                'checkOut' => $this->checkOut,
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('checkIn', 'Fecha inválida. Por favor, selecciona fechas válidas.');
            $this->setDatesIncomplete();
        }
    }

    public function calculateTotal()
    {
        // Guard clause 1: dates must be present
        if (empty($this->checkIn) || empty($this->checkOut)) {
            $this->total = 0;
            return;
        }

        // Guard clause 2: dates must be valid (no errors)
        if ($this->hasDateValidationErrors()) {
            $this->total = 0;
            return;
        }

        try {
            $checkIn = Carbon::parse($this->checkIn);
            $checkOut = Carbon::parse($this->checkOut);
            $nights = $checkIn->diffInDays($checkOut);

            // Guard clause 3: nights must be > 0
            if ($nights <= 0) {
                $this->total = 0;
                return;
            }

            if ($this->showMultiRoomSelector) {
                if (!is_array($this->selectedRoomIds) || empty($this->selectedRoomIds)) {
                    $this->total = 0;
                    return;
                }

                $total = 0;
                foreach ($this->selectedRoomIds as $roomId) {
                    $room = $this->getRoomById($roomId);
                    if (!$room || !is_array($room)) {
                        continue;
                    }

                    $guestCount = $this->getRoomGuestsCount($roomId);
                    $capacity = $room['capacity'] ?? 0;

                    // Guard clause 4: respect room capacity
                    if ($guestCount > $capacity) {
                        $this->total = 0;
                        return;
                    }

                    // Guard clause 5: only calculate price if guests are assigned
                    if ($guestCount <= 0) {
                        continue;
                    }

                    $pricePerNight = $this->calculatePriceForRoom($room, $guestCount);
                    $total += $pricePerNight * $nights;
                }
                $this->total = $total;
            } else {
                // Single room mode: use roomId
                if (empty($this->roomId)) {
                    $this->total = 0;
                    return;
                }

                $selectedRoom = $this->selectedRoom;
                if (!$selectedRoom || !is_array($selectedRoom)) {
                    $this->total = 0;
                    return;
                }

                $guestCount = $this->getRoomGuestsCount($this->roomId);
                $capacity = $selectedRoom['capacity'] ?? 0;

                // Guard clause 4: respect room capacity (business rule)
                if ($guestCount > $capacity) {
                    $this->total = 0;
                    return;
                }

                // Guard clause 5: only calculate price if guests are assigned
                if ($guestCount <= 0) {
                    $this->total = 0;
                    return;
                }

                $pricePerNight = $this->calculatePriceForRoom($selectedRoom, $guestCount);
                $this->total = $pricePerNight * $nights;
            }
        } catch (\Exception $e) {
            Log::error('Error calculating total: ' . $e->getMessage(), [
                'checkIn' => $this->checkIn,
                'checkOut' => $this->checkOut
            ]);
            $this->total = 0;
        }
    }

    public function checkAvailability()
    {
        if (!$this->canCheckAvailability()) {
            $this->resetAvailabilityState();
            return;
        }

        if (empty($this->roomId) || empty($this->checkIn) || empty($this->checkOut)) {
            $this->resetAvailabilityState();
            return;
        }

        $this->isChecking = true;
        $this->availability = null;

        try {
            $roomId = (int) $this->roomId;
            $checkIn = Carbon::parse($this->checkIn)->startOfDay();
            $checkOut = Carbon::parse($this->checkOut)->startOfDay();

            // Use direct DB query instead of HTTP call to avoid timeouts
            // This method checks both reservations table and reservation_rooms pivot table
            $isAvailable = $this->isRoomAvailableForDates($roomId, $checkIn, $checkOut);

            $this->availability = $isAvailable;
            $this->availabilityMessage = $isAvailable
                ? 'HABITACIÓN DISPONIBLE'
                : 'NO DISPONIBLE PARA ESTAS FECHAS';
        } catch (\Exception $e) {
            Log::error('Error checking availability: ' . $e->getMessage(), [
                'roomId' => $this->roomId,
                'checkIn' => $this->checkIn,
                'checkOut' => $this->checkOut,
                'trace' => $e->getTraceAsString()
            ]);
            $this->availability = false;
            $this->availabilityMessage = 'Error al verificar disponibilidad';
        } finally {
            $this->isChecking = false;
        }
    }

    public function getNightsProperty(): int
    {
        if (empty($this->checkIn) || empty($this->checkOut)) {
            return 0;
        }

        try {
            $checkIn = Carbon::parse($this->checkIn);
            $checkOut = Carbon::parse($this->checkOut);
            $diff = $checkOut->diffInDays($checkIn);

            return $diff > 0 ? (int) $diff : 0;
        } catch (\Exception $e) {
            Log::error('Error calculating nights: ' . $e->getMessage());
            return 0;
        }
    }

    public function getBalanceProperty(): int
    {
        try {
            // Force cast to numeric values, defaulting to 0 if null or invalid
            $total = is_numeric($this->total) ? (float) $this->total : 0.0;
            $deposit = is_numeric($this->deposit) ? (float) $this->deposit : 0.0;

            $balance = $total - $deposit;

            // Ensure balance is never negative and cast to int
            return (int) max(0, $balance);
        } catch (\Exception $e) {
            Log::error('Error in getBalanceProperty: ' . $e->getMessage(), [
                'total' => $this->total,
                'deposit' => $this->deposit
            ]);
            return 0;
        }
    }

    public function getCanProceedProperty(): bool
    {
        if (empty($this->checkIn) || empty($this->checkOut)) {
            return false;
        }

        try {
            $checkIn = Carbon::parse($this->checkIn)->startOfDay();
            $checkOut = Carbon::parse($this->checkOut)->startOfDay();
            $today = Carbon::today()->startOfDay();

            // Use gte() for check-in >= today and gt() for check-out > check-in
            return $checkIn->gte($today) && $checkOut->gt($checkIn);
        } catch (\Exception $e) {
            Log::error('Error in getCanProceedProperty: ' . $e->getMessage(), [
                'checkIn' => $this->checkIn,
                'checkOut' => $this->checkOut
            ]);
            return false;
        }
    }

    public function getShowGuestAssignmentPanelProperty(): bool
    {
        if ($this->showMultiRoomSelector) {
            // Multi-room mode: show panel if any room is selected
            if (!is_array($this->selectedRoomIds) || empty($this->selectedRoomIds)) {
                return false;
            }
            // Panel should show if there are selected rooms (allows viewing/removing guests)
            return true;
        }

        // Single room mode: show panel if room is selected
        // Panel should remain visible even when capacity is full (allows removing guests)
        if (empty($this->roomId)) {
            return false;
        }

        $selectedRoom = $this->selectedRoom;
        if (!$selectedRoom || !is_array($selectedRoom)) {
            return false;
        }

        // Show panel if room is selected (regardless of capacity)
        // This allows users to view and remove guests even when room is full
        return true;
    }

    public function getExceedsCapacityProperty(): bool
    {
        if ($this->showMultiRoomSelector) {
            if (!is_array($this->selectedRoomIds) || empty($this->selectedRoomIds)) {
                return false;
            }

            foreach ($this->selectedRoomIds as $roomId) {
                $room = $this->getRoomById($roomId);
                if (!$room || !is_array($room)) {
                    continue;
                }

                $assignedCount = $this->getRoomGuestsCount($roomId);
                $capacity = (int) ($room['capacity'] ?? 0);
                if ($assignedCount > $capacity) {
                    return true;
                }
            }
            return false;
        }

        // Single room mode: check if assigned guests exceed capacity
        if (empty($this->roomId)) {
            return false;
        }

        $selectedRoom = $this->selectedRoom;
        if (!$selectedRoom || !is_array($selectedRoom)) {
            return false;
        }

        $assignedCount = $this->getRoomGuestsCount($this->roomId);
        $capacity = (int) ($selectedRoom['capacity'] ?? 0);

        return $assignedCount > $capacity;
    }

    public function getIsValidProperty(): bool
    {
        try {
            // Basic required fields
            if (empty($this->customerId) || empty($this->checkIn) || empty($this->checkOut)) {
                return false;
            }

            // Dates must be valid
            if (!$this->canProceed) {
                return false;
            }

            // Room selection
            if ($this->showMultiRoomSelector) {
                if (!is_array($this->selectedRoomIds) || empty($this->selectedRoomIds)) {
                    return false;
                }
            } else {
                if (empty($this->roomId)) {
                    return false;
                }

                // Business rule: capacity must be respected
                $selectedRoom = $this->selectedRoom;
                if ($selectedRoom && is_array($selectedRoom)) {
                    $assignedCount = $this->getRoomGuestsCount($this->roomId);
                    $capacity = $selectedRoom['capacity'] ?? 0;
                    if ($assignedCount > $capacity) {
                        return false;
                    }
                    // At least one guest must be assigned
                    if ($assignedCount <= 0) {
                        return false;
                    }
                }
            }

            // Financial validation - ensure numeric types
            $total = is_numeric($this->total) ? (float) $this->total : 0.0;
            $deposit = is_numeric($this->deposit) ? (float) $this->deposit : 0.0;

            if ($total <= 0 || $deposit < 0) {
                return false;
            }

            $balance = $this->balance;
            if ($balance < 0) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error in getIsValidProperty: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get room data by ID with normalized output
     * Optimized for performance - normalizes roomId to int
     *
     * @param int|string $roomId Room ID
     * @return array|null Normalized room data or null
     */
    public function getRoomById($roomId)
    {
        // Default array to prevent null access errors in Blade
        $defaultRoom = [
            'id' => null,
            'number' => null,
            'capacity' => 0,
            'max_capacity' => 0,
            'room_number' => null,
            'beds' => 0,
            'occupancyPrices' => [],
            'price1Person' => 0,
            'priceAdditionalPerson' => 0,
        ];

        if (empty($roomId)) {
            Log::warning('getRoomById called with empty roomId', ['roomId' => $roomId]);
            return $defaultRoom;
        }

        try {
            // Normalize roomId to integer for consistent comparison
            $roomIdInt = is_numeric($roomId) ? (int) $roomId : 0;
            if ($roomIdInt <= 0) {
                Log::warning('getRoomById called with invalid roomId', ['roomId' => $roomId, 'normalized' => $roomIdInt]);
                return $defaultRoom;
            }

            // Try roomsData first (has detailed pricing info with 'number' and 'capacity')
            if (!empty($this->roomsData) && is_array($this->roomsData)) {
                foreach ($this->roomsData as $room) {
                    if (!is_array($room)) {
                        continue;
                    }
                    $roomIdValue = $room['id'] ?? null;
                    if ($roomIdValue !== null && (int)$roomIdValue === $roomIdInt) {
                        // Ensure all required fields have defaults to prevent null access errors
                        return [
                            'id' => $room['id'] ?? $roomIdInt,
                            'number' => $room['number'] ?? $room['room_number'] ?? null,
                            'capacity' => $room['capacity'] ?? $room['max_capacity'] ?? 0,
                            'max_capacity' => $room['max_capacity'] ?? $room['capacity'] ?? 0,
                            'room_number' => $room['room_number'] ?? $room['number'] ?? null,
                            'beds' => $room['beds'] ?? 0,
                            'occupancyPrices' => $room['occupancyPrices'] ?? [],
                            'price1Person' => $room['price1Person'] ?? 0,
                            'priceAdditionalPerson' => $room['priceAdditionalPerson'] ?? 0,
                        ];
                    }
                }
            }

            // Fallback to rooms array (has 'room_number' and 'max_capacity')
            // Convert to roomsData format for consistency
            if (!empty($this->rooms) && is_array($this->rooms)) {
                foreach ($this->rooms as $room) {
                    if (!is_array($room)) {
                        continue;
                    }
                    $roomIdValue = $room['id'] ?? null;
                    if ($roomIdValue !== null && (int)$roomIdValue === $roomIdInt) {
                        // Convert to roomsData format for consistency with all required fields
                        return [
                            'id' => $room['id'] ?? $roomIdInt,
                            'number' => $room['room_number'] ?? null,
                            'capacity' => $room['max_capacity'] ?? 0,
                            'max_capacity' => $room['max_capacity'] ?? 0,
                            'room_number' => $room['room_number'] ?? null,
                            'beds' => $room['beds'] ?? 0,
                            'occupancyPrices' => $room['occupancyPrices'] ?? [],
                            'price1Person' => $room['price1Person'] ?? 0,
                            'priceAdditionalPerson' => $room['priceAdditionalPerson'] ?? 0,
                        ];
                    }
                }
            }

            // Room not found - return default array instead of null
            Log::warning('getRoomById: Room not found', [
                'roomId' => $roomId,
                'roomIdInt' => $roomIdInt,
                'roomsDataCount' => is_array($this->roomsData) ? count($this->roomsData) : 0,
                'roomsCount' => is_array($this->rooms) ? count($this->rooms) : 0,
            ]);
            return $defaultRoom;
        } catch (\Exception $e) {
            Log::error('Error in getRoomById: ' . $e->getMessage(), [
                'roomId' => $roomId,
                'trace' => $e->getTraceAsString()
            ]);
            return $defaultRoom;
        }
    }

    /**
     * Get count of valid guests assigned to a room
     *
     * @param int|string $roomId Room ID
     * @return int Number of valid guests
     */
    public function getRoomGuestsCount($roomId): int
    {
        if (empty($roomId)) {
            return 0;
        }

        // Normalize to integer (roomGuests always uses int keys)
        $roomIdInt = is_numeric($roomId) ? (int) $roomId : 0;
        if ($roomIdInt <= 0) {
            return 0;
        }

        $guests = $this->roomGuests[$roomIdInt] ?? [];

        if (!is_array($guests) || empty($guests)) {
            return 0;
        }

        // Count only valid guests (with id and name)
        return count(array_filter($guests, function ($guest): bool {
            return is_array($guest)
                && !empty($guest['id'])
                && is_numeric($guest['id'])
                && isset($guest['name']);
        }));
    }

    public function calculatePriceForRoom(array $room, int $guestsCount): float
    {
        if (!is_array($room) || empty($room)) {
            return 0.0;
        }

        // Defensive: do not calculate price if no guests assigned
        if ($guestsCount <= 0) {
            return 0.0;
        }

        $occupancyPrices = $room['occupancyPrices'] ?? [];
        $capacity = $room['capacity'] ?? 2;
        $actualGuests = min($guestsCount, $capacity);
        $exceededGuests = max(0, $guestsCount - $capacity);

        // Get base price for 1 person
        $basePrice = 0;
        if (isset($occupancyPrices[1]) && $occupancyPrices[1] > 0) {
            $basePrice = (float) $occupancyPrices[1];
        } elseif (isset($room['price1Person']) && $room['price1Person'] > 0) {
            $basePrice = (float) $room['price1Person'];
        } else {
            $basePrice = (float) ($room['price'] ?? 0);
        }

        // Add price for additional persons within capacity
        $additionalPersonsWithinCapacity = max(0, $actualGuests - 1);
        if ($additionalPersonsWithinCapacity > 0 && isset($room['priceAdditionalPerson'])) {
            $additionalPrice = (float) $room['priceAdditionalPerson'];
            $basePrice += $additionalPrice * $additionalPersonsWithinCapacity;
        }

        // Add price for exceeded guests
        if ($exceededGuests > 0 && isset($room['priceAdditionalPerson'])) {
            $additionalPrice = (float) $room['priceAdditionalPerson'];
            $basePrice += $additionalPrice * $exceededGuests;
        }

        return (float) $basePrice;
    }

    public function formatCurrency(float $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    public function getPriceForGuestsProperty(): float
    {
        try {
            if (empty($this->roomId)) {
                return 0.0;
            }

            $selectedRoom = $this->selectedRoom;
            if (!$selectedRoom || !is_array($selectedRoom)) {
                return 0.0;
            }

            $guestCount = $this->getRoomGuestsCount($this->roomId);

            // Defensive: return 0 if no guests assigned
            if ($guestCount <= 0) {
                return 0.0;
            }

            return $this->calculatePriceForRoom($selectedRoom, $guestCount);
        } catch (\Exception $e) {
            Log::error('Error calculating price for guests: ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getRoomPriceForGuestsProperty(): array
    {
        if (!is_array($this->selectedRoomIds) || empty($this->selectedRoomIds)) {
            return [];
        }

        try {
            $prices = [];
            foreach ($this->selectedRoomIds as $roomId) {
                $roomIdInt = (int) $roomId;
                $room = $this->getRoomById($roomIdInt);
                if (!$room || !is_array($room)) {
                    continue;
                }
                $guestCount = $this->getRoomGuestsCount($roomIdInt);
                // Only calculate price if guests are assigned
                if ($guestCount > 0) {
                    $prices[$roomIdInt] = $this->calculatePriceForRoom($room, $guestCount);
                } else {
                    $prices[$roomIdInt] = 0;
                }
            }
            return $prices;
        } catch (\Exception $e) {
            Log::error('Error in getRoomPriceForGuestsProperty: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Pre-calculated room guests data for multi-room mode
     * Avoids calling getRoomGuests() directly in Blade
     *
     * @return array<int, array> Room ID => array of guests
     */
    public function getRoomsGuestsDataProperty(): array
    {
        if (!is_array($this->selectedRoomIds) || empty($this->selectedRoomIds)) {
            return [];
        }

        try {
            $data = [];
            foreach ($this->selectedRoomIds as $roomId) {
                $roomIdInt = (int) $roomId;
                $guests = $this->getRoomGuests($roomIdInt);
                if (!empty($guests)) {
                    $data[$roomIdInt] = $guests;
                }
            }
            return $data;
        } catch (\Exception $e) {
            Log::error('Error in getRoomsGuestsDataProperty: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Pre-calculated room guests counts for multi-room mode
     * Avoids calling getRoomGuestsCount() directly in Blade
     *
     * @return array<int, int> Room ID => guest count
     */
    public function getRoomsGuestsCountsProperty(): array
    {
        if (!is_array($this->selectedRoomIds) || empty($this->selectedRoomIds)) {
            return [];
        }

        try {
            $counts = [];
            foreach ($this->selectedRoomIds as $roomId) {
                $roomIdInt = (int) $roomId;
                $counts[$roomIdInt] = $this->getRoomGuestsCount($roomIdInt);
            }
            return $counts;
        } catch (\Exception $e) {
            Log::error('Error in getRoomsGuestsCountsProperty: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Pre-calculated room data with guests for multi-room mode
     * Combines room info, guests, and counts in one structure
     *
     * @return array<int, array> Room ID => ['room' => ..., 'guests' => ..., 'count' => ..., 'price' => ...]
     */
    public function getRoomsDataWithGuestsProperty(): array
    {
        if (!is_array($this->selectedRoomIds) || empty($this->selectedRoomIds)) {
            return [];
        }

        try {
            $data = [];

            // Calculate directly instead of accessing other computed properties to avoid recursion
            foreach ($this->selectedRoomIds as $roomId) {
                $roomIdInt = (int) $roomId;
                $room = $this->getRoomById($roomIdInt);

                if (!$room || !is_array($room)) {
                    continue;
                }

                // Get data directly instead of using computed properties
                $guests = $this->getRoomGuests($roomIdInt);
                $count = $this->getRoomGuestsCount($roomIdInt);
                $price = $count > 0 ? $this->calculatePriceForRoom($room, $count) : 0;

                $data[$roomIdInt] = [
                    'room' => $room,
                    'guests' => $guests,
                    'count' => $count,
                    'price' => $price,
                    'canAssignMore' => $this->canAssignMoreGuestsToRoom($roomIdInt),
                ];
            }
            return $data;
        } catch (\Exception $e) {
            Log::error('Error in getRoomsDataWithGuestsProperty: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    public function getSelectedRoomProperty()
    {
        if (empty($this->roomId)) {
            return null;
        }

        try {
            $room = $this->getRoomById($this->roomId);
            // getRoomById() now always returns an array, never null
            if (!is_array($room) || empty($room['id'])) {
                return null;
            }
            // Ensure all required fields exist with defaults
            return [
                'id' => $room['id'] ?? (int)$this->roomId,
                'number' => $room['number'] ?? $room['room_number'] ?? null,
                'capacity' => $room['capacity'] ?? $room['max_capacity'] ?? 0,
                'max_capacity' => $room['max_capacity'] ?? $room['capacity'] ?? 0,
                'room_number' => $room['room_number'] ?? $room['number'] ?? null,
                'beds' => $room['beds'] ?? 0,
                'occupancyPrices' => $room['occupancyPrices'] ?? [],
                'price1Person' => $room['price1Person'] ?? 0,
                'priceAdditionalPerson' => $room['priceAdditionalPerson'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting selected room: ' . $e->getMessage(), [
                'roomId' => $this->roomId,
                'roomsData' => is_array($this->roomsData) ? 'array(' . count($this->roomsData) . ')' : gettype($this->roomsData),
                'exception' => $e
            ]);
            return null;
        }
    }

    public function getSelectedCustomerInfoProperty()
    {
        if (empty($this->customerId)) {
            return null;
        }

        try {
            if (empty($this->customers) || !is_array($this->customers)) {
                return null;
            }

            $customer = collect($this->customers)->first(function ($customer) {
                $customerId = $customer['id'] ?? null;
                return (string) $customerId === (string) $this->customerId;
            });

            if (!$customer || !is_array($customer)) {
                return null;
            }

            $phone = $customer['phone'] ?? 'S/N';
            $identification = 'S/N';

            if (isset($customer['taxProfile']) && is_array($customer['taxProfile'])) {
                $identification = $customer['taxProfile']['identification'] ?? 'S/N';
            }

            return [
                'id' => $identification,
                'phone' => $phone
            ];
        } catch (\Exception $e) {
            Log::error('Error getting selected customer info: ' . $e->getMessage(), [
                'customerId' => $this->customerId,
                'exception' => $e
            ]);
            return null;
        }
    }

    public function getAutoCalculatedTotalProperty(): float
    {
        try {
            if ($this->showMultiRoomSelector) {
                if (!is_array($this->selectedRoomIds) || empty($this->selectedRoomIds)) {
                    return 0;
                }

                $nights = $this->nights;
                if ($nights <= 0) {
                    return 0;
                }

                $total = 0;
                foreach ($this->selectedRoomIds as $roomId) {
                    $guestCount = $this->getRoomGuestsCount($roomId);
                    // Only calculate price if guests are assigned
                    if ($guestCount <= 0) {
                        continue;
                    }
                    $room = $this->getRoomById($roomId);
                    if (!$room || !is_array($room)) {
                        continue;
                    }
                    $pricePerNight = $this->calculatePriceForRoom($room, $guestCount);
                    $total += $pricePerNight * $nights;
                }
                return $total;
            }

            $selectedRoom = $this->selectedRoom;
            if (!$selectedRoom || !is_array($selectedRoom)) {
                return 0;
            }

            $nights = $this->nights;
            if ($nights <= 0) {
                return 0;
            }

            $pricePerNight = $this->getPriceForGuests;
            return $pricePerNight * $nights;
        } catch (\Exception $e) {
            Log::error('Error calculating auto total: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 0;
        }
    }

    public function calculateTotalGuestsCount(): int
    {
        if (!$this->showMultiRoomSelector) {
            // Single room mode: derive from roomGuests using roomId
            if (empty($this->roomId)) {
                return 0;
            }
            return $this->getRoomGuestsCount($this->roomId);
        }

        // Multiple rooms mode: sum all room guests
        if (!is_array($this->selectedRoomIds) || empty($this->selectedRoomIds)) {
            return 0;
        }

        $total = 0;
        foreach ($this->selectedRoomIds as $roomId) {
            $total += $this->getRoomGuestsCount($roomId);
        }
        return $total;
    }

    /**
     * Toggle room selection in multi-room mode
     * Normalizes roomId to int and manages roomGuests cleanup
     *
     * @param int|string $roomId Room ID
     * @return void
     */
    public function toggleSelectedRoomIds($roomId): void
    {
        // Normalize to integer
        $roomIdInt = is_numeric($roomId) ? (int) $roomId : 0;
        if ($roomIdInt <= 0) {
            return;
        }

        // Ensure selectedRoomIds is an array
        if (!is_array($this->selectedRoomIds)) {
            $this->selectedRoomIds = [];
        }

        // Normalize all IDs to integers
        $currentIds = array_map('intval', $this->selectedRoomIds);
        $index = array_search($roomIdInt, $currentIds, true);

        if ($index !== false) {
            // Remove if already selected
            unset($currentIds[$index]);
            $this->selectedRoomIds = array_values($currentIds);

            // Clean up roomGuests for removed room (using integer key)
            if (isset($this->roomGuests[$roomIdInt])) {
                unset($this->roomGuests[$roomIdInt]);
            }
        } else {
            // Add if not selected
            $currentIds[] = $roomIdInt;
            $this->selectedRoomIds = array_values(array_unique($currentIds));

            // Initialize empty roomGuests array for newly selected room
            if (!isset($this->roomGuests[$roomIdInt]) || !is_array($this->roomGuests[$roomIdInt])) {
                $this->roomGuests[$roomIdInt] = [];
            }
        }

        // Force reactivity
        $this->selectedRoomIds = $this->selectedRoomIds;
        $this->roomGuests = $this->roomGuests;

        $this->calculateTotal();
    }

    public function removeRoom(int $roomId): void
    {
        $this->selectedRoomIds = array_values(array_filter($this->selectedRoomIds, function ($id) use ($roomId): bool {
            return (int) $id !== $roomId;
        }));
        if (isset($this->roomGuests[$roomId])) {
            unset($this->roomGuests[$roomId]);
        }
        $this->calculateTotal();
    }

    public function openGuestModal(?int $roomId = null): void
    {
        // In single room mode, if no roomId is provided, use the current roomId
        if ($roomId === null && !$this->showMultiRoomSelector && !empty($this->roomId)) {
            $roomId = is_numeric($this->roomId) ? (int) $this->roomId : 0;
            if ($roomId <= 0) {
                Log::warning('openGuestModal: Invalid roomId in single room mode', ['roomId' => $this->roomId]);
                return;
            }
        }

        $this->currentRoomForGuestAssignment = $roomId;
        $this->guestModalOpen = true;
        $this->guestModalTab = 'search';
        $this->selectedGuestForAdd = null;
        $this->guestSearchTerm = '';
        $this->showGuestDropdown = false;

        // Temporary logging for debugging
        $room = $this->getRoomById($roomId);
        Log::info('openGuestModal: Modal opened', [
            'roomId' => $roomId,
            'currentRoomForGuestAssignment' => $this->currentRoomForGuestAssignment,
            'roomFound' => !empty($room) && is_array($room),
            'roomData' => $room,
        ]);
    }

    public function closeGuestModal(): void
    {
        $this->guestModalOpen = false;
        $this->currentRoomForGuestAssignment = null;
        $this->guestModalTab = 'search';
        $this->guestSearchTerm = '';
        $this->showGuestDropdown = false;
    }

    public function openNewCustomerModal(): void
    {
        $this->newCustomerModalOpen = true;
    }

    public function closeNewCustomerModal(): void
    {
        $this->newCustomerModalOpen = false;
    }

    public function setGuestModalTab(string $tab): void
    {
        $this->guestModalTab = $tab;
    }

    public function clearCustomerSelection(): void
    {
        $this->customerId = '';
        $this->customerSearchTerm = '';
        $this->showCustomerDropdown = false;
    }

    public function restoreSuggestedTotal(): void
    {
        $this->total = $this->autoCalculatedTotal;
    }

    public function closeCustomerDropdown(): void
    {
        $this->showCustomerDropdown = false;
    }

    public function closeGuestDropdown(): void
    {
        $this->showGuestDropdown = false;
    }

    public function openGuestSearchDropdown(): void
    {
        $this->showGuestDropdown = true;
    }

    public function getFilteredGuestsProperty(): array
    {
        $allCustomers = $this->customers ?? [];

        // If no search term, return first 5 customers
        if (empty($this->guestSearchTerm)) {
            return array_slice($allCustomers, 0, 5);
        }

        $searchTerm = mb_strtolower(trim($this->guestSearchTerm));
        $filtered = [];

        foreach ($allCustomers as $customer) {
            $name = mb_strtolower($customer['name'] ?? '');
            $identification = $customer['taxProfile']['identification'] ?? '';
            $phone = mb_strtolower($customer['phone'] ?? '');

            // Search in name, identification, or phone
            if (str_contains($name, $searchTerm) ||
                str_contains($identification, $searchTerm) ||
                str_contains($phone, $searchTerm)) {
                $filtered[] = $customer;
            }

            // Limit to 20 results for performance
            if (count($filtered) >= 20) {
                break;
            }
        }

        return $filtered;
    }

    /**
     * Select a customer from the list and assign as guest
     *
     * @param int|string $customerId Customer ID
     * @return void
     */
    public function selectGuestForAssignment($customerId): void
    {
        if (empty($customerId)) {
            return;
        }

        // Find customer in the list
        $customer = collect($this->customers)->first(function ($customer) use ($customerId) {
            return (string)($customer['id'] ?? '') === (string)$customerId;
        });

        if (!$customer || !is_array($customer)) {
            $this->addError('guestAssignment', 'Cliente no encontrado.');
            return;
        }

        // Prepare normalized guest data
        $guestData = [
            'id' => $customer['id'] ?? null,
            'name' => $customer['name'] ?? '',
            'identification' => $customer['taxProfile']['identification'] ?? 'S/N',
            'phone' => $customer['phone'] ?? 'S/N',
            'email' => $customer['email'] ?? null,
        ];

        // Add guest (this will validate, assign, and close modal)
        $this->addGuest($guestData);

        // Clear search state
        $this->guestSearchTerm = '';
        $this->showGuestDropdown = false;
    }

    public function updatedGuestSearchTerm($value)
    {
        // Keep dropdown open when typing (debounced in view)
        if ($this->guestModalOpen) {
            $this->showGuestDropdown = true;
        }
    }

    /**
     * Add a guest to a room with validation and immediate UI update
     *
     * @param array $guestData Guest data with id, name, identification, phone
     * @return void
     */
    public function addGuest(array $guestData): void
    {
        // Validate guest data structure
        if (empty($guestData) || !is_array($guestData)) {
            $this->addError('guestAssignment', 'Datos del huésped inválidos.');
            return;
        }

        $guestId = $guestData['id'] ?? null;
        if (empty($guestId) || !is_numeric($guestId)) {
            $this->addError('guestAssignment', 'El ID del cliente es inválido.');
            return;
        }

        $guestId = (int) $guestId;

        // Determine target room ID
        $targetRoomId = $this->currentRoomForGuestAssignment;

        // In single room mode, use current roomId if no specific room is set
        if ($targetRoomId === null && !$this->showMultiRoomSelector && !empty($this->roomId)) {
            $targetRoomId = is_numeric($this->roomId) ? (int) $this->roomId : 0;
            if ($targetRoomId <= 0) {
                $this->addError('guestAssignment', 'No se ha seleccionado una habitación válida.');
                return;
            }
        }

        // Validate room selection
        if ($targetRoomId === null) {
            $this->addError('guestAssignment', 'No se ha seleccionado una habitación.');
            return;
        }

        // Normalize to integer (roomGuests always uses int keys)
        $targetRoomId = (int) $targetRoomId;
        $room = $this->getRoomById($targetRoomId);

        if (!$room || !is_array($room)) {
            $this->addError('guestAssignment', 'La habitación seleccionada no es válida.');
            return;
        }

        // Initialize room guests array if needed
        if (!isset($this->roomGuests[$targetRoomId]) || !is_array($this->roomGuests[$targetRoomId])) {
            $this->roomGuests[$targetRoomId] = [];
        }

        // Check for duplicates - validate guest is not already assigned
        $existingGuestIds = array_column($this->roomGuests[$targetRoomId], 'id');
        $existingGuestIds = array_map('intval', $existingGuestIds);
        if (in_array($guestId, $existingGuestIds, true)) {
            $this->addError('guestAssignment', 'Este cliente ya está asignado a esta habitación.');
            return;
        }

        // Validate capacity - ensure room has available space
        $currentCount = $this->getRoomGuestsCount($targetRoomId);
        $capacity = (int)($room['capacity'] ?? $room['max_capacity'] ?? 0);

        if ($capacity <= 0) {
            $this->addError('guestAssignment', 'La habitación no tiene capacidad definida.');
            return;
        }

        if ($currentCount >= $capacity) {
            $this->addError('guestAssignment', "No se pueden asignar más huéspedes. La habitación ha alcanzado su capacidad máxima de {$capacity} personas.");
            return;
        }

        // Ensure guest data has required fields
        $guestName = trim($guestData['name'] ?? '');
        if (empty($guestName)) {
            $this->addError('guestAssignment', 'El nombre del huésped es requerido.');
            return;
        }

        $normalizedGuestData = [
            'id' => $guestId,
            'name' => $guestName,
            'identification' => $guestData['identification'] ?? 'S/N',
            'phone' => $guestData['phone'] ?? 'S/N',
            'email' => $guestData['email'] ?? null,
        ];

        // Add guest to room (using integer key)
        $this->roomGuests[$targetRoomId][] = $normalizedGuestData;

        // Re-index array to maintain sequential indices
        $this->roomGuests[$targetRoomId] = array_values($this->roomGuests[$targetRoomId]);

        // Force Livewire reactivity by reassigning the property
        // This ensures computed properties are recalculated
        $this->roomGuests = $this->roomGuests;

        // Close modal and reset state
        $this->guestModalOpen = false;
        $this->currentRoomForGuestAssignment = null;
        $this->selectedGuestForAdd = null;
        $this->guestSearchTerm = '';
        $this->showGuestDropdown = false;

        // Recalculate totals (will use updated roomGuests)
        $this->calculateTotal();
    }

    public function confirmAddGuest(): void
    {
        if (empty($this->selectedGuestForAdd) || !is_array($this->selectedGuestForAdd)) {
            return;
        }

        $this->addGuest($this->selectedGuestForAdd);
    }

    /**
     * Remove a guest from a room by index
     *
     * @param int|null $roomId Room ID (null for single room mode)
     * @param int $index Guest index in the array
     * @return void
     */
    public function removeGuest(?int $roomId, int $index): void
    {
        // Determine target room ID
        if ($roomId !== null) {
            // Multiple rooms mode: remove from specific room
            $targetRoomId = (int) $roomId;
        } else {
            // Single room mode: use current roomId (normalize to int)
            if (empty($this->roomId)) {
                return;
            }
            $targetRoomId = is_numeric($this->roomId) ? (int) $this->roomId : 0;
            if ($targetRoomId <= 0) {
                return;
            }
        }

        // Validate index and remove guest
        if (!isset($this->roomGuests[$targetRoomId]) || !is_array($this->roomGuests[$targetRoomId])) {
            return;
        }

        if (isset($this->roomGuests[$targetRoomId][$index])) {
            unset($this->roomGuests[$targetRoomId][$index]);
            // Re-index array to maintain sequential indices
            $this->roomGuests[$targetRoomId] = array_values($this->roomGuests[$targetRoomId]);

            // If array is empty, ensure it's still an array (not null)
            if (empty($this->roomGuests[$targetRoomId])) {
                $this->roomGuests[$targetRoomId] = [];
            }

            // Force Livewire reactivity
            $this->roomGuests = $this->roomGuests;

            // Recalculate totals
            $this->calculateTotal();
        }
    }

    /**
     * Check if more guests can be assigned to a room
     *
     * @param int $roomId Room ID
     * @return bool True if room has available capacity
     */
    public function canAssignMoreGuestsToRoom(int $roomId): bool
    {
        $roomId = (int) $roomId;
        $room = $this->getRoomById($roomId);

        if (!$room || !is_array($room)) {
            return false;
        }

        $currentCount = $this->getRoomGuestsCount($roomId);
        $capacity = (int)($room['capacity'] ?? $room['max_capacity'] ?? 0);

        return $currentCount < $capacity;
    }

    /**
     * Get guests assigned to a specific room
     *
     * @param int $roomId Room ID
     * @return array Array of guest data
     */
    public function getRoomGuests(int $roomId): array
    {
        $roomId = (int) $roomId;
        $guests = $this->roomGuests[$roomId] ?? [];

        if (!is_array($guests)) {
            return [];
        }

        // Filter and validate guest data
        return array_values(array_filter($guests, function ($guest): bool {
            return is_array($guest)
                && !empty($guest['id'])
                && is_numeric($guest['id'])
                && isset($guest['name']);
        }));
    }

    /**
     * Get assigned guests for single room mode (computed property)
     * This property is used by the Blade view to display guests
     *
     * @return array Array of guest data with id, name, identification, phone
     */
    public function getAssignedGuestsProperty(): array
    {
        // Only for single room mode
        if ($this->showMultiRoomSelector) {
            return [];
        }

        // Validate roomId exists
        if (empty($this->roomId)) {
            return [];
        }

        // Normalize roomId to integer (roomGuests always uses int keys)
        $roomIdInt = is_numeric($this->roomId) ? (int) $this->roomId : 0;
        if ($roomIdInt <= 0) {
            return [];
        }

        // Read directly from roomGuests using integer key
        $guests = $this->roomGuests[$roomIdInt] ?? [];

        if (!is_array($guests) || empty($guests)) {
            return [];
        }

        // Filter and validate guest data
        $validGuests = [];
        foreach ($guests as $guest) {
            if (!is_array($guest)) {
                continue;
            }

            $guestId = $guest['id'] ?? null;
            $guestName = $guest['name'] ?? '';

            // Validate required fields
            if (empty($guestId) || !is_numeric($guestId) || empty($guestName)) {
                continue;
            }

            $validGuests[] = [
                'id' => (int) $guestId,
                'name' => (string) $guestName,
                'identification' => $guest['identification'] ?? 'S/N',
                'phone' => $guest['phone'] ?? 'S/N',
                'email' => $guest['email'] ?? null,
            ];
        }

        return array_values($validGuests);
    }

    public function getAvailableSlotsProperty(): int
    {
        try {
            if (empty($this->roomId)) {
                return 0;
            }

            $selectedRoom = $this->selectedRoom;
            if (!$selectedRoom || !is_array($selectedRoom)) {
                return 0;
            }

            $assignedCount = $this->getRoomGuestsCount($this->roomId);
            $capacity = $selectedRoom['capacity'] ?? $selectedRoom['max_capacity'] ?? 0;

            return max(0, (int)$capacity - $assignedCount);
        } catch (\Exception $e) {
            Log::error('Error in getAvailableSlotsProperty: ' . $e->getMessage());
            return 0;
        }
    }

    public function getCanAssignMoreGuestsProperty(): bool
    {
        try {
            if (empty($this->roomId)) {
                return false;
            }

            $selectedRoom = $this->selectedRoom;
            if (!$selectedRoom || !is_array($selectedRoom)) {
                return false;
            }

            $assignedCount = $this->getRoomGuestsCount($this->roomId);
            $capacity = $selectedRoom['capacity'] ?? $selectedRoom['max_capacity'] ?? 0;

            return $assignedCount < (int)$capacity;
        } catch (\Exception $e) {
            Log::error('Error in getCanAssignMoreGuestsProperty: ' . $e->getMessage());
            return false;
        }
    }

    private function clearDateErrors(): void
    {
        $errorBag = $this->getErrorBag();
        $errorBag->forget('checkIn');
        $errorBag->forget('checkOut');
    }

    private function resetAvailabilityState(): void
    {
        $this->availability = null;
        $this->availabilityMessage = '';
        $this->isChecking = false;
    }

    private function setDatesIncomplete(): void
    {
        $this->datesCompleted = false;
        $this->formStep = 1;
    }

    private function validateCheckInDate(string $value): void
    {
        try {
            $checkIn = Carbon::parse($value)->startOfDay();
            $today = Carbon::today()->startOfDay();

            if ($checkIn->lt($today)) {
                $this->addError('checkIn', 'La fecha de entrada no puede ser anterior al día actual.');
            }
        } catch (\Exception $e) {
            Log::error('Error parsing check-in date: ' . $e->getMessage(), ['value' => $value]);
            if (strlen($value) > 0) {
                $this->addError('checkIn', 'Fecha inválida. Por favor, selecciona una fecha válida.');
            }
        }
    }

    private function validateCheckOutDate(string $value): void
    {
        try {
            if (empty($this->checkIn)) {
                return;
            }

            $checkIn = Carbon::parse($this->checkIn)->startOfDay();
            $checkOut = Carbon::parse($value)->startOfDay();

            if ($checkOut->lte($checkIn)) {
                $this->addError('checkOut', 'La fecha de salida debe ser posterior a la fecha de entrada.');
            }
        } catch (\Exception $e) {
            Log::error('Error parsing check-out date: ' . $e->getMessage(), [
                'value' => $value,
                'checkIn' => $this->checkIn
            ]);
            if (strlen($value) > 0) {
                $this->addError('checkOut', 'Fecha inválida. Por favor, selecciona una fecha válida.');
            }
        }
    }

    private function validateCheckOutAgainstCheckIn(): void
    {
        if (empty($this->checkOut) || empty($this->checkIn)) {
            return;
        }

        try {
            $checkIn = Carbon::parse($this->checkIn)->startOfDay();
            $checkOut = Carbon::parse($this->checkOut)->startOfDay();

            if ($checkOut->lte($checkIn)) {
                $this->addError('checkOut', 'La fecha de salida debe ser posterior a la fecha de entrada.');
            }
        } catch (\Exception $e) {
            Log::error('Error validating check-out against check-in: ' . $e->getMessage(), [
                'checkIn' => $this->checkIn,
                'checkOut' => $this->checkOut
            ]);
        }
    }

    private function hasDateValidationErrors(): bool
    {
        $errors = $this->getErrorBag();
        return $errors->has('checkIn') || $errors->has('checkOut');
    }

    private function canCheckAvailability(): bool
    {
        if ($this->showMultiRoomSelector) {
            return false;
        }

        if (empty($this->roomId) || empty($this->checkIn) || empty($this->checkOut)) {
            return false;
        }

        if (!$this->datesCompleted) {
            return false;
        }

        if ($this->hasDateValidationErrors()) {
            return false;
        }

        return true;
    }

    private function checkAvailabilityIfReady(): void
    {
        // Guard clause 1: dates must be present
        if (empty($this->checkIn) || empty($this->checkOut)) {
            $this->resetAvailabilityState();
            return;
        }

        // Guard clause 2: dates must be valid (no parsing or business rule errors)
        if ($this->hasDateValidationErrors()) {
            $this->resetAvailabilityState();
            return;
        }

        // Guard clause 3: dates must be marked as completed (passed all validations)
        if (!$this->datesCompleted) {
            $this->resetAvailabilityState();
            return;
        }

        // Guard clause 4: verify checkOut > checkIn before checking availability
        try {
            $checkIn = Carbon::parse($this->checkIn)->startOfDay();
            $checkOut = Carbon::parse($this->checkOut)->startOfDay();

            if ($checkOut->lte($checkIn)) {
                $this->resetAvailabilityState();
                return;
            }
        } catch (\Exception $e) {
            $this->resetAvailabilityState();
            return;
        }

        // All guard clauses passed - check availability
        if ($this->canCheckAvailability()) {
            $this->checkAvailability();
        } else {
            $this->resetAvailabilityState();
        }
    }

    public function getAvailableRoomsProperty(): array
    {
        // If dates are not set or not valid, return empty array (no rooms available)
        if (empty($this->checkIn) || empty($this->checkOut)) {
            return [];
        }

        // If dates have validation errors, return empty array
        if ($this->hasDateValidationErrors()) {
            return [];
        }

        // Only filter if dates are completed and valid
        if (!$this->datesCompleted) {
            return [];
        }

        try {
            $checkIn = Carbon::parse($this->checkIn)->startOfDay();
            $checkOut = Carbon::parse($this->checkOut)->startOfDay();

            // Validate date range
            if ($checkOut->lte($checkIn)) {
                return [];
            }

            $availableRooms = [];
            $allRooms = $this->rooms ?? [];

            foreach ($allRooms as $room) {
                if (!is_array($room) || empty($room['id'])) {
                    continue;
                }

                $roomId = (int) $room['id'];

                // Check if room is available using the same logic as ReservationController
                if ($this->isRoomAvailableForDates($roomId, $checkIn, $checkOut)) {
                    $availableRooms[] = $room;
                }
            }

            return $availableRooms;
        } catch (\Exception $e) {
            Log::error('Error filtering available rooms: ' . $e->getMessage(), [
                'checkIn' => $this->checkIn,
                'checkOut' => $this->checkOut,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    private function clearUnavailableRooms(): void
    {
        if (!$this->datesCompleted || $this->hasDateValidationErrors()) {
            // If dates are not valid, clear all room selections
            $this->roomId = '';
            $this->selectedRoomIds = [];
            $this->roomGuests = [];
            $this->calculateTotal();
            return;
        }

        if (empty($this->checkIn) || empty($this->checkOut)) {
            // If dates are empty, clear all room selections
            $this->roomId = '';
            $this->selectedRoomIds = [];
            $this->roomGuests = [];
            $this->calculateTotal();
            return;
        }

        try {
            $checkIn = Carbon::parse($this->checkIn)->startOfDay();
            $checkOut = Carbon::parse($this->checkOut)->startOfDay();

            if ($checkOut->lte($checkIn)) {
                // Invalid date range, clear all selections
                $this->roomId = '';
                $this->selectedRoomIds = [];
                $this->roomGuests = [];
                $this->calculateTotal();
                return;
            }

            // Check availability directly instead of using computed property to avoid recursion
            // Clear single room selection if not available
            if (!empty($this->roomId)) {
                $roomId = (int) $this->roomId;
                if (!$this->isRoomAvailableForDates($roomId, $checkIn, $checkOut)) {
                    $this->roomId = '';
                    if (isset($this->roomGuests[$roomId])) {
                        unset($this->roomGuests[$roomId]);
                    }
                    $this->calculateTotal();
                }
            }

            // Clear multiple room selections if not available
            if (is_array($this->selectedRoomIds) && !empty($this->selectedRoomIds)) {
                $validRoomIds = [];
                foreach ($this->selectedRoomIds as $roomId) {
                    $roomIdInt = (int) $roomId;
                    if ($this->isRoomAvailableForDates($roomIdInt, $checkIn, $checkOut)) {
                        $validRoomIds[] = $roomIdInt;
                    } else {
                        // Remove guests for unavailable room
                        if (isset($this->roomGuests[$roomIdInt])) {
                            unset($this->roomGuests[$roomIdInt]);
                        }
                    }
                }
                $this->selectedRoomIds = $validRoomIds;
                $this->calculateTotal();
            }
        } catch (\Exception $e) {
            Log::error('Error clearing unavailable rooms: ' . $e->getMessage(), [
                'checkIn' => $this->checkIn,
                'checkOut' => $this->checkOut,
                'trace' => $e->getTraceAsString()
            ]);
            // On error, clear selections to prevent inconsistent state
            $this->roomId = '';
            $this->selectedRoomIds = [];
            $this->roomGuests = [];
            $this->total = 0;
        }
    }

    private function isRoomAvailableForDates(int $roomId, Carbon $checkIn, Carbon $checkOut): bool
    {
        // Check in main reservations table (single room reservations)
        $existsInReservations = Reservation::where('room_id', $roomId)
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('check_in_date', '<', $checkOut)
                      ->where('check_out_date', '>', $checkIn);
            })
            ->exists();

        if ($existsInReservations) {
            return false;
        }

        // Check in reservation_rooms table (multi-room reservations)
        $existsInPivot = DB::table('reservation_rooms')
            ->join('reservations', 'reservation_rooms.reservation_id', '=', 'reservations.id')
            ->where('reservation_rooms.room_id', $roomId)
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('reservations.check_in_date', '<', $checkOut)
                      ->where('reservations.check_out_date', '>', $checkIn);
            })
            ->exists();

        return !$existsInPivot;
    }

    public function updateMainCustomerRequiredFields(): void
    {
        $documentId = $this->newMainCustomer['identificationDocumentId'] ?? '';

        if (empty($documentId)) {
            $this->mainCustomerRequiresDV = false;
            $this->mainCustomerIsJuridicalPerson = false;
            $this->newMainCustomer['dv'] = '';
            return;
        }

        // Find document in identificationDocuments array
        $document = null;
        if (is_array($this->identificationDocuments)) {
            foreach ($this->identificationDocuments as $doc) {
                if (isset($doc['id']) && (string)$doc['id'] === (string)$documentId) {
                    $document = $doc;
                    break;
                }
            }
        }

        if ($document) {
            $this->mainCustomerRequiresDV = (bool)($document['requires_dv'] ?? false);
            $this->mainCustomerIsJuridicalPerson = in_array($document['code'] ?? '', ['NI', 'NIT'], true);

            // Calculate DV if required
            if ($this->mainCustomerRequiresDV && !empty($this->newMainCustomer['identification'])) {
                $this->newMainCustomer['dv'] = $this->calculateVerificationDigit($this->newMainCustomer['identification']);
            } else {
                $this->newMainCustomer['dv'] = '';
            }
        } else {
            $this->mainCustomerRequiresDV = false;
            $this->mainCustomerIsJuridicalPerson = false;
            $this->newMainCustomer['dv'] = '';
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

    public function checkMainCustomerIdentification(): void
    {
        $identification = $this->newMainCustomer['identification'] ?? '';

        if (empty($identification)) {
            $this->mainCustomerIdentificationMessage = '';
            $this->mainCustomerIdentificationExists = false;
            return;
        }

        // Check if identification already exists
        $exists = Customer::withoutGlobalScopes()
            ->whereHas('taxProfile', function ($query) use ($identification) {
                $query->where('identification', $identification);
            })
            ->exists();

        if ($exists) {
            $this->mainCustomerIdentificationExists = true;
            $this->mainCustomerIdentificationMessage = 'Esta identificación ya está registrada.';
        } else {
            $this->mainCustomerIdentificationExists = false;
            $this->mainCustomerIdentificationMessage = 'Identificación disponible.';
        }

        // Recalculate DV if required
        if ($this->mainCustomerRequiresDV && !empty($identification)) {
            $this->newMainCustomer['dv'] = $this->calculateVerificationDigit($identification);
        }
    }

    public function createMainCustomer(): void
    {
        $requiresElectronicInvoice = $this->newMainCustomer['requiresElectronicInvoice'] ?? false;

        $rules = [
            'newMainCustomer.name' => 'required|string|max:255',
            'newMainCustomer.identification' => 'required|string|max:10',
            'newMainCustomer.phone' => 'required|string|max:20',
            'newMainCustomer.email' => 'nullable|email|max:255',
            'newMainCustomer.address' => 'nullable|string|max:500',
        ];

        $messages = [
            'newMainCustomer.name.required' => 'El nombre es obligatorio.',
            'newMainCustomer.name.max' => 'El nombre no puede exceder 255 caracteres.',
            'newMainCustomer.identification.required' => 'La identificación es obligatoria.',
            'newMainCustomer.identification.max' => 'La identificación no puede exceder 10 dígitos.',
            'newMainCustomer.phone.required' => 'El teléfono es obligatorio.',
            'newMainCustomer.phone.max' => 'El teléfono no puede exceder 20 caracteres. Por favor, ingrese un número válido.',
            'newMainCustomer.email.email' => 'El email debe tener un formato válido (ejemplo: correo@dominio.com).',
            'newMainCustomer.email.max' => 'El email no puede exceder 255 caracteres.',
            'newMainCustomer.address.max' => 'La dirección no puede exceder 500 caracteres.',
        ];

        // Add DIAN validation if electronic invoice is required
        if ($requiresElectronicInvoice) {
            $rules['newMainCustomer.identificationDocumentId'] = 'required|exists:dian_identification_documents,id';
            $rules['newMainCustomer.municipalityId'] = 'required|exists:dian_municipalities,factus_id';

            $messages['newMainCustomer.identificationDocumentId.required'] = 'El tipo de documento es obligatorio para facturación electrónica.';
            $messages['newMainCustomer.identificationDocumentId.exists'] = 'El tipo de documento seleccionado no es válido. Por favor, seleccione una opción de la lista.';
            $messages['newMainCustomer.municipalityId.required'] = 'El municipio es obligatorio para facturación electrónica.';
            $messages['newMainCustomer.municipalityId.exists'] = 'El municipio seleccionado no es válido. Por favor, seleccione una opción de la lista.';

            // If juridical person, company is required
            if ($this->mainCustomerIsJuridicalPerson) {
                $rules['newMainCustomer.company'] = 'required|string|max:255';
                $messages['newMainCustomer.company.required'] = 'La razón social es obligatoria para personas jurídicas (NIT).';
                $messages['newMainCustomer.company.max'] = 'La razón social no puede exceder 255 caracteres.';
            }
        }

        $this->validate($rules, $messages);

        // Check if identification already exists
        $this->checkMainCustomerIdentification();
        if ($this->mainCustomerIdentificationExists) {
            $this->addError('newMainCustomer.identification', 'Esta identificación ya está registrada.');
            return;
        }

        $this->creatingMainCustomer = true;

        try {
            // Create customer
            $customer = Customer::create([
                'name' => mb_strtoupper($this->newMainCustomer['name']),
                'phone' => $this->newMainCustomer['phone'],
                'email' => $this->newMainCustomer['email'] ?? null,
                'address' => $this->newMainCustomer['address'] ?? null,
                'is_active' => true,
                'requires_electronic_invoice' => $requiresElectronicInvoice,
            ]);

            // Create tax profile
            // Use default values when electronic invoice is not required
            $municipalityId = $requiresElectronicInvoice
                ? ($this->newMainCustomer['municipalityId'] ?? null)
                : (CompanyTaxSetting::first()?->municipality_id
                    ?? DianMunicipality::first()?->factus_id
                    ?? 149); // Bogotá Factus ID as fallback

            $taxProfileData = [
                'identification' => $this->newMainCustomer['identification'],
                'dv' => $this->newMainCustomer['dv'] ?? null,
                'identification_document_id' => $requiresElectronicInvoice
                    ? ($this->newMainCustomer['identificationDocumentId'] ?? null)
                    : 3, // Default to CC (Cédula de Ciudadanía)
                'legal_organization_id' => $requiresElectronicInvoice
                    ? ($this->newMainCustomer['legalOrganizationId'] ?? null)
                    : 2, // Default to Persona Natural
                'tribute_id' => $requiresElectronicInvoice
                    ? ($this->newMainCustomer['tributeId'] ?? null)
                    : 21, // Default to No responsable de IVA
                'municipality_id' => $municipalityId,
                'company' => $requiresElectronicInvoice && $this->mainCustomerIsJuridicalPerson
                    ? ($this->newMainCustomer['company'] ?? null)
                    : null,
                'trade_name' => $requiresElectronicInvoice
                    ? ($this->newMainCustomer['tradeName'] ?? null)
                    : null,
            ];

            $customer->taxProfile()->create($taxProfileData);

            // Add customer to the list
            $this->customers[] = [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone ?? 'S/N',
                'email' => $customer->email ?? null,
                'taxProfile' => $customer->taxProfile ? [
                    'identification' => $customer->taxProfile->identification ?? 'S/N',
                    'dv' => $customer->taxProfile->dv ?? null,
                ] : null,
            ];

            // Select the newly created customer
            $this->customerId = (string) $customer->id;

            // Reset form and close modal
            $this->newMainCustomer = [
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
            $this->newMainCustomerErrors = [];
            $this->mainCustomerIdentificationMessage = '';
            $this->mainCustomerIdentificationExists = false;
            $this->newCustomerModalOpen = false;

            // Show success message
            session()->flash('message', 'Cliente creado exitosamente.');
        } catch (ValidationException $e) {
            // Re-throw validation exceptions to show specific field errors
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error creating customer: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'data' => $this->newMainCustomer
            ]);

            // Show more specific error messages
            $errorMessage = 'Error al crear el cliente.';
            if (str_contains($e->getMessage(), 'SQLSTATE')) {
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $errorMessage = 'Ya existe un cliente con esta identificación.';
                    $this->addError('newMainCustomer.identification', $errorMessage);
                } else {
                    $errorMessage = 'Error en la base de datos. Por favor verifique los datos e intente nuevamente.';
                    $this->addError('newMainCustomer.name', $errorMessage);
                }
            } else {
                $this->addError('newMainCustomer.name', $errorMessage . ' Por favor intente nuevamente.');
            }
        } finally {
            $this->creatingMainCustomer = false;
        }
    }

    public function getFilteredCustomersProperty(): array
    {
        $allCustomers = $this->customers ?? [];

        // If no search term, return first 5 customers
        if (empty($this->customerSearchTerm)) {
            return array_slice($allCustomers, 0, 5);
        }

        $searchTerm = mb_strtolower(trim($this->customerSearchTerm));
        $filtered = [];

        foreach ($allCustomers as $customer) {
            $name = mb_strtolower($customer['name'] ?? '');
            $identification = $customer['taxProfile']['identification'] ?? '';
            $phone = mb_strtolower($customer['phone'] ?? '');

            // Search in name, identification, or phone
            if (str_contains($name, $searchTerm) ||
                str_contains($identification, $searchTerm) ||
                str_contains($phone, $searchTerm)) {
                $filtered[] = $customer;
            }

            // Limit to 20 results for performance
            if (count($filtered) >= 20) {
                break;
            }
        }

        return $filtered;
    }

    public function updatedCustomerSearchTerm($value)
    {
        // Keep dropdown open when typing (debounced in view)
        if ($this->datesCompleted) {
            $this->showCustomerDropdown = true;
        }
    }

    public function openCustomerDropdown()
    {
        if ($this->datesCompleted) {
            // Always show dropdown when clicked, even if search term is empty
            // This will display the default 5 customers
            $this->showCustomerDropdown = true;
        }
    }

    public function selectCustomer($customerId)
    {
        $this->customerId = (string) $customerId;
        $this->customerSearchTerm = '';
        $this->showCustomerDropdown = false;
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

        // Find document in identificationDocuments array
        $document = null;
        if (is_array($this->identificationDocuments)) {
            foreach ($this->identificationDocuments as $doc) {
                if (isset($doc['id']) && (string)$doc['id'] === (string)$documentId) {
                    $document = $doc;
                    break;
                }
            }
        }

        if ($document) {
            $this->customerRequiresDV = (bool)($document['requires_dv'] ?? false);
            $this->customerIsJuridicalPerson = in_array($document['code'] ?? '', ['NI', 'NIT'], true);

            // Calculate DV if required
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

    public function checkCustomerIdentification(): void
    {
        $identification = $this->newCustomer['identification'] ?? '';

        if (empty($identification)) {
            $this->customerIdentificationMessage = '';
            $this->customerIdentificationExists = false;
            return;
        }

        // Check if identification already exists
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

        // Recalculate DV if required
        if ($this->customerRequiresDV && !empty($identification)) {
            $this->newCustomer['dv'] = $this->calculateVerificationDigit($identification);
        }
    }

    public function createAndAddGuest(): void
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
            'newCustomer.phone.max' => 'El teléfono no puede exceder 20 caracteres. Por favor, ingrese un número válido.',
            'newCustomer.email.email' => 'El email debe tener un formato válido (ejemplo: correo@dominio.com).',
            'newCustomer.email.max' => 'El email no puede exceder 255 caracteres.',
            'newCustomer.address.max' => 'La dirección no puede exceder 500 caracteres.',
        ];

        // Add DIAN validation if electronic invoice is required
        if ($requiresElectronicInvoice) {
            $rules['newCustomer.identificationDocumentId'] = 'required|exists:dian_identification_documents,id';
            $rules['newCustomer.municipalityId'] = 'required|exists:dian_municipalities,factus_id';

            $messages['newCustomer.identificationDocumentId.required'] = 'El tipo de documento es obligatorio para facturación electrónica.';
            $messages['newCustomer.identificationDocumentId.exists'] = 'El tipo de documento seleccionado no es válido. Por favor, seleccione una opción de la lista.';
            $messages['newCustomer.municipalityId.required'] = 'El municipio es obligatorio para facturación electrónica.';
            $messages['newCustomer.municipalityId.exists'] = 'El municipio seleccionado no es válido. Por favor, seleccione una opción de la lista.';

            // If juridical person, company is required
            if ($this->customerIsJuridicalPerson) {
                $rules['newCustomer.company'] = 'required|string|max:255';
                $messages['newCustomer.company.required'] = 'La razón social es obligatoria para personas jurídicas (NIT).';
                $messages['newCustomer.company.max'] = 'La razón social no puede exceder 255 caracteres.';
            }
        }

        $this->validate($rules, $messages);

        // Check if identification already exists
        $this->checkCustomerIdentification();
        if ($this->customerIdentificationExists) {
            $this->addError('newCustomer.identification', 'Esta identificación ya está registrada.');
            return;
        }

        $this->creatingCustomer = true;

        try {
            // Create customer
            $customer = Customer::create([
                'name' => mb_strtoupper($this->newCustomer['name']),
                'phone' => $this->newCustomer['phone'],
                'email' => $this->newCustomer['email'] ?? null,
                'address' => $this->newCustomer['address'] ?? null,
                'is_active' => true,
                'requires_electronic_invoice' => $requiresElectronicInvoice,
            ]);

            // Create tax profile
            // Use default values when electronic invoice is not required
            $municipalityId = $requiresElectronicInvoice
                ? ($this->newCustomer['municipalityId'] ?? null)
                : (CompanyTaxSetting::first()?->municipality_id
                    ?? DianMunicipality::first()?->factus_id
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
                'company' => $requiresElectronicInvoice && $this->customerIsJuridicalPerson
                    ? ($this->newCustomer['company'] ?? null)
                    : null,
                'trade_name' => $requiresElectronicInvoice
                    ? ($this->newCustomer['tradeName'] ?? null)
                    : null,
            ];

            $customer->taxProfile()->create($taxProfileData);

            // Prepare guest data
            $guestData = [
                'id' => $customer->id,
                'name' => $customer->name,
                'identification' => $customer->taxProfile?->identification ?? 'S/N',
                'phone' => $customer->phone ?? 'S/N',
                'email' => $customer->email ?? null,
            ];

            // Add guest to room
            $this->addGuest($guestData);

            // Reset form
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
            $this->newCustomerErrors = [];
            $this->customerIdentificationMessage = '';
            $this->customerIdentificationExists = false;
            $this->customerRequiresDV = false;
            $this->customerIsJuridicalPerson = false;
        } catch (\Exception $e) {
            Log::error('Error creating guest customer: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $this->newCustomer
            ]);
            $this->addError('newCustomer.name', 'Error al crear el cliente. Por favor intente nuevamente.');
        } finally {
            $this->creatingCustomer = false;
        }
    }

    public function render()
    {
        return view('livewire.reservations.reservation-create');
    }
}
