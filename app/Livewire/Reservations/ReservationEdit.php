<?php

declare(strict_types=1);

namespace App\Livewire\Reservations;

use App\Exceptions\ReservationRequiredForEditException;
use App\Models\Reservation;
use App\Services\ReservationFormService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

final class ReservationEdit extends ReservationCreate
{
    public ?Reservation $reservation = null;

    public bool $isEditInitialized = false;

    private ReservationFormService $reservationFormService;

    public function boot(ReservationFormService $reservationFormService): void
    {
        $this->reservationFormService = $reservationFormService;
    }

    public function booted(): void
    {
        if ($this->isEditInitialized) {
            return;
        }

        if ($this->reservation === null) {
            return;
        }

        $data = $this->reservationFormService->loadReservationData($this->reservation);

        $this->customerId = $data['customerId'];
        $this->checkIn = $data['checkIn'];
        $this->checkOut = $data['checkOut'];
        $this->checkInTime = $data['checkInTime'];
        $this->total = $data['total'];
        $this->deposit = $data['deposit'];
        $this->guestsCount = $data['guestsCount'];
        $this->notes = $data['notes'];

        $this->showMultiRoomSelector = $data['showMultiRoomSelector'];
        $this->roomId = $data['roomId'];
        $this->selectedRoomIds = $data['selectedRoomIds'];
        $this->roomGuests = $data['roomGuests'];

        $this->rules = [
            'customerId' => 'required|exists:customers,id',
            'checkIn' => 'required|date',
            'checkOut' => 'required|date|after:checkIn',
            'checkInTime' => ['nullable', 'regex:/^([0-1]\d|2[0-3]):[0-5]\d$/'],
            'total' => 'required|numeric|min:0',
            'deposit' => 'required|numeric|min:0',
            'guestsCount' => 'nullable|integer|min:0',
        ];

        $this->validateDates();
        $this->calculateTotal();

        $this->isEditInitialized = true;
    }

    public function getFormActionProperty(): string
    {
        $reservation = $this->requireReservation();
        return route('reservations.update', $reservation);
    }

    public function getFormMethodProperty(): string
    {
        return 'PUT';
    }

    public function getPageTitleProperty(): string
    {
        $reservation = $this->requireReservation();
        return 'Editar Reserva #' . (string) $reservation->id;
    }

    public function getSubmitButtonTextProperty(): string
    {
        return 'Actualizar Reserva';
    }

    public function getReservationDateValueProperty(): string
    {
        $reservation = $this->requireReservation();
        $reservationDate = $reservation->reservation_date;
        if ($reservationDate === null) {
            throw new ReservationRequiredForEditException();
        }

        return $reservationDate->format('Y-m-d');
    }

    public function getCanProceedProperty(): bool
    {
        if (empty($this->checkIn) || empty($this->checkOut)) {
            return false;
        }

        try {
            $checkIn = Carbon::parse($this->checkIn)->startOfDay();
            $checkOut = Carbon::parse($this->checkOut)->startOfDay();

            return $checkOut->gt($checkIn);
        } catch (\Exception $e) {
            Log::error('Error in ReservationEdit::getCanProceedProperty: ' . $e->getMessage(), [
                'reservation_id' => $this->reservation->id,
                'checkIn' => $this->checkIn,
                'checkOut' => $this->checkOut,
            ]);
            return false;
        }
    }

    public function validateDates()
    {
        if (empty($this->checkIn) || empty($this->checkOut)) {
            $this->datesCompleted = false;
            $this->formStep = 1;
            return;
        }

        if ($this->hasDateValidationErrors()) {
            $this->datesCompleted = false;
            $this->formStep = 1;
            return;
        }

        try {
            $checkIn = Carbon::parse($this->checkIn)->startOfDay();
            $checkOut = Carbon::parse($this->checkOut)->startOfDay();

            if ($checkOut->lte($checkIn)) {
                $this->addError('checkOut', 'La fecha de salida debe ser posterior a la fecha de entrada.');
                $this->datesCompleted = false;
                $this->formStep = 1;
                return;
            }

            $this->datesCompleted = true;
            $this->formStep = 2;
        } catch (\Exception $e) {
            Log::error('Error validating dates in ReservationEdit: ' . $e->getMessage(), [
                'reservation_id' => $this->reservation->id,
                'checkIn' => $this->checkIn,
                'checkOut' => $this->checkOut,
            ]);

            $this->addError('checkIn', 'Fecha inválida. Por favor, selecciona fechas válidas.');
            $this->datesCompleted = false;
            $this->formStep = 1;
        }
    }

    /**
     * Override availability checks to exclude the current reservation.
     */
    public function checkAvailability(): void
    {
        $reservation = $this->requireReservation();

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

            $isAvailable = $this->reservationFormService->isRoomAvailable(
                $roomId,
                $checkIn,
                $checkOut,
                $reservation->id
            );

            $this->availability = $isAvailable;
            $this->availabilityMessage = $isAvailable
                ? 'HABITACIÓN DISPONIBLE'
                : 'NO DISPONIBLE PARA ESTAS FECHAS';
        } catch (\Exception $e) {
            Log::error('Error checking availability in ReservationEdit: ' . $e->getMessage(), [
                'reservation_id' => $this->reservation->id,
                'roomId' => $this->roomId,
                'checkIn' => $this->checkIn,
                'checkOut' => $this->checkOut,
            ]);

            $this->availability = false;
            $this->availabilityMessage = 'Error al verificar disponibilidad';
        } finally {
            $this->isChecking = false;
        }
    }

    /**
     * Available rooms list for edit: exclude the current reservation when evaluating availability.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableRoomsProperty(): array
    {
        $reservation = $this->requireReservation();

        $availableRooms = [];
        $canCompute = !empty($this->checkIn)
            && !empty($this->checkOut)
            && !$this->hasDateValidationErrors()
            && $this->datesCompleted;

        if (!$canCompute) {
            return $availableRooms;
        }

        try {
            $checkIn = Carbon::parse($this->checkIn)->startOfDay();
            $checkOut = Carbon::parse($this->checkOut)->startOfDay();

            if ($checkOut->gt($checkIn)) {
                $allRooms = $this->rooms ?? [];

                foreach ($allRooms as $room) {
                    if (!is_array($room) || empty($room['id'])) {
                        continue;
                    }

                    $roomId = (int) $room['id'];
                    $isAvailable = $this->reservationFormService->isRoomAvailable(
                        $roomId,
                        $checkIn,
                        $checkOut,
                        $reservation->id
                    );

                    if ($isAvailable) {
                        $availableRooms[] = $room;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error filtering available rooms in ReservationEdit: ' . $e->getMessage(), [
                'reservation_id' => $reservation->id,
                'checkIn' => $this->checkIn,
                'checkOut' => $this->checkOut,
            ]);
        }

        return $availableRooms;
    }

    private function requireReservation(): Reservation
    {
        if ($this->reservation === null) {
            throw new ReservationRequiredForEditException();
        }

        return $this->reservation;
    }

    public function render()
    {
        return view('livewire.reservations.reservation-create');
    }
}


