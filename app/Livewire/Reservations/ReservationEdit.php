<?php

declare(strict_types=1);

namespace App\Livewire\Reservations;

use App\Exceptions\ReservationRequiredForEditException;
use App\Models\Reservation;
use App\Services\ReservationFormService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class ReservationEdit extends ReservationCreate
{
    public ?Reservation $editingReservation = null;

    public bool $isEditInitialized = false;
    public bool $hasOperationalStay = false;

    private ?ReservationFormService $reservationFormService = null;

    public function booted(): void
    {
        if ($this->isEditInitialized) {
            return;
        }

        if ($this->editingReservation === null) {
            return;
        }

        if ($this->reservationFormService === null) {
            $this->reservationFormService = app(ReservationFormService::class);
        }

        $this->submitButtonText = 'Actualizar Reserva';
        $this->pageTitle = 'Editar Reserva';
        $this->hasOperationalStay = $this->editingReservation
            ->stays()
            ->whereIn('status', ['active', 'pending_checkout', 'finished'])
            ->exists();

        $data = $this->reservationFormService->loadReservationData($this->editingReservation);
        $lockedCustomerId = (int) ($this->editingReservation->client_id ?? 0);

        $this->reservation->customerId = $lockedCustomerId > 0
            ? $lockedCustomerId
            : (int) ($data['customerId'] ?? 0);
        $this->reservation->checkIn = (string) ($data['checkIn'] ?? '');
        $this->reservation->checkOut = (string) ($data['checkOut'] ?? '');
        $this->checkInTime = (string) ($data['checkInTime'] ?? '14:00');
        $this->reservation->total = (int) round((float) ($data['total'] ?? 0));
        $this->reservation->deposit = (int) round((float) ($data['deposit'] ?? 0));
        $this->reservation->paymentMethod = (string) ($data['paymentMethod'] ?? 'efectivo');
        $this->guestsCount = (int) ($data['guestsCount'] ?? 0);
        $this->reservation->adults = $data['adults'] ?? $this->reservation->adults;
        $this->reservation->children = $data['children'] ?? $this->reservation->children;
        $this->reservation->notes = (string) ($data['notes'] ?? '');

        $this->showMultiRoomSelector = (bool) ($data['showMultiRoomSelector'] ?? false);
        $this->roomId = (string) ($data['roomId'] ?? '');
        $selectedRoomIds = is_array($data['selectedRoomIds'] ?? null)
            ? array_values(array_unique(array_map('intval', $data['selectedRoomIds'])))
            : [];
        $this->reservation->selectedRoomIds = $selectedRoomIds;
        $this->reservation->roomGuests = is_array($data['roomGuests'] ?? null)
            ? $data['roomGuests']
            : [];

        $this->customerSearchTerm = '';
        $this->showCustomerDropdown = false;
        $this->isEditInitialized = true;
    }

    public function getFormActionProperty(): string
    {
        return route('reservations.update', $this->requireReservationModel());
    }

    public function getFormMethodProperty(): string
    {
        return 'PUT';
    }

    public function getPageTitleProperty(): string
    {
        $reservation = $this->requireReservationModel();
        $code = (string) ($reservation->reservation_code ?? ('RES-' . $reservation->id));

        return 'Editar Reserva ' . $code;
    }

    public function getSubmitButtonTextProperty(): string
    {
        return 'Actualizar Reserva';
    }

    public function getIsCustomerLockedProperty(): bool
    {
        return true;
    }

    public function getAreRoomsLockedProperty(): bool
    {
        return $this->hasOperationalStay;
    }

    public function getEditingReservationIdProperty(): ?int
    {
        return $this->editingReservation?->id;
    }

    public function getExistingGuestsDocumentProperty(): ?array
    {
        $reservation = $this->editingReservation;
        if ($reservation === null) {
            return null;
        }

        $path = (string) ($reservation->guests_document_path ?? '');
        if ($path === '') {
            return null;
        }

        return [
            'name' => (string) ($reservation->guests_document_original_name ?: basename($path)),
            'size' => (int) ($reservation->guests_document_size ?? 0),
            'view_url' => route('reservations.guest-document.view', $reservation),
            'download_url' => route('reservations.guest-document.download', $reservation),
            'exists' => Storage::disk('public')->exists($path),
        ];
    }

    public function getReservationDateValueProperty(): string
    {
        $reservation = $this->requireReservationModel();
        $checkInDate = $reservation->reservationRooms()
            ->whereNotNull('check_in_date')
            ->orderBy('check_in_date')
            ->value('check_in_date');

        if (!empty($checkInDate)) {
            return Carbon::parse((string) $checkInDate)->format('Y-m-d');
        }

        return $reservation->created_at?->format('Y-m-d') ?? now()->format('Y-m-d');
    }

    public function createReservation()
    {
        $storedDocumentPath = null;
        $previousDocumentPath = null;

        try {
            $this->loading = true;

            $reservationModel = $this->requireReservationModel();

            if (method_exists($reservationModel, 'trashed') && $reservationModel->trashed()) {
                $this->addError('general', 'No se puede editar una reserva cancelada.');
                return;
            }

            $hasOperationalStay = $reservationModel->stays()
                ->whereIn('status', ['active', 'pending_checkout', 'finished'])
                ->exists();
            $this->hasOperationalStay = $hasOperationalStay;

            $lockedCustomerId = (int) ($reservationModel->client_id ?? 0);
            if ($lockedCustomerId > 0) {
                $this->reservation->customerId = $lockedCustomerId;
            }

            $existingRoomIds = $reservationModel->reservationRooms()
                ->pluck('room_id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->values()
                ->all();

            if (empty($existingRoomIds) && !empty($reservationModel->room_id)) {
                $existingRoomIds = [(int) $reservationModel->room_id];
            }

            $existingCheckInDate = $reservationModel->reservationRooms()
                ->whereNotNull('check_in_date')
                ->orderBy('check_in_date')
                ->value('check_in_date');
            $existingCheckOutDate = $reservationModel->reservationRooms()
                ->whereNotNull('check_out_date')
                ->orderByDesc('check_out_date')
                ->value('check_out_date');
            $existingCheckInTime = $reservationModel->reservationRooms()
                ->whereNotNull('check_in_time')
                ->orderBy('id')
                ->value('check_in_time');

            if ($hasOperationalStay) {
                $this->validate([
                    'reservation.customerId' => 'required|integer|exists:customers,id',
                    'reservation.total' => 'required|numeric|min:1',
                    'reservation.deposit' => 'required|numeric|min:0',
                    'reservation.adults' => 'nullable|integer|min:0',
                    'reservation.children' => 'nullable|integer|min:0',
                    'reservation.notes' => 'nullable|string|max:1000',
                ]);

                if ($this->getReservationDepositValue() > $this->getReservationTotalValue()) {
                    $this->addError('reservation.deposit', 'El abono no puede ser mayor al total.');
                    return;
                }
            } else {
                $this->reservation->validate();
            }

            $this->validateGuestsDocument();

            $effectiveCheckIn = $hasOperationalStay
                ? (string) ($existingCheckInDate ?: $reservationModel->check_in_date?->format('Y-m-d') ?: $this->reservation->checkIn)
                : $this->reservation->checkIn;
            $effectiveCheckOut = $hasOperationalStay
                ? (string) ($existingCheckOutDate ?: $reservationModel->check_out_date?->format('Y-m-d') ?: $this->reservation->checkOut)
                : $this->reservation->checkOut;
            $effectiveCheckInTime = $hasOperationalStay
                ? (string) ($existingCheckInTime ?: $this->checkInTime)
                : $this->checkInTime;
            $effectiveRoomIds = $hasOperationalStay
                ? $existingRoomIds
                : array_values(array_unique(array_map('intval', $this->reservation->selectedRoomIds)));

            $reservationData = [
                'customerId' => $lockedCustomerId > 0 ? $lockedCustomerId : (int) $this->reservation->customerId,
                'check_in_date' => $effectiveCheckIn,
                'check_out_date' => $effectiveCheckOut,
                'check_in_time' => $effectiveCheckInTime,
                'room_ids' => $effectiveRoomIds,
                'total_amount' => $this->getReservationTotalValue(),
                'deposit' => $this->getReservationDepositValue(),
                'payment_method' => $this->getReservationPaymentMethodValue(),
                'guests_count' => $this->getGuestsCountValue(),
                'adults' => $this->getReservationAdultsValue(),
                'children' => $this->getReservationChildrenValue(),
                'room_guests' => $this->reservation->roomGuests ?? [],
                'notes' => $this->reservation->notes,
            ];

            $checkIn = Carbon::parse($effectiveCheckIn)->startOfDay();
            $checkOut = Carbon::parse($effectiveCheckOut)->startOfDay();

            if (!$hasOperationalStay) {
                $roomConflicts = $this->getSelectedRoomConflicts($effectiveRoomIds, $checkIn, $checkOut);
                if (!empty($roomConflicts)) {
                    $this->addError('reservation.selectedRoomIds', 'Las habitaciones seleccionadas tienen conflicto de ocupacion para las fechas indicadas.');
                    return;
                }
            }

            $validationErrors = [];
            $reservationService = new ReservationService();
            $reservationService->validateReservationData($reservationData, $validationErrors);

            if (!empty($validationErrors)) {
                throw ValidationException::withMessages($validationErrors);
            }

            $previousDocumentPath = $reservationModel->guests_document_path;
            if ($this->guestsDocument !== null) {
                $documentData = $this->storeGuestsDocument();
                $storedDocumentPath = $documentData['guests_document_path'] ?? null;
                $reservationData = array_merge($reservationData, $documentData);
            } else {
                $reservationData = array_merge($reservationData, [
                    'guests_document_path' => $reservationModel->guests_document_path,
                    'guests_document_original_name' => $reservationModel->guests_document_original_name,
                    'guests_document_mime_type' => $reservationModel->guests_document_mime_type,
                    'guests_document_size' => $reservationModel->guests_document_size,
                ]);
            }

            $updatedReservation = $reservationService->updateReservation($reservationModel, $reservationData, $hasOperationalStay);
            $this->editingReservation = $updatedReservation;
            $this->guestsDocument = null;

            if (
                !empty($storedDocumentPath)
                && !empty($previousDocumentPath)
                && $storedDocumentPath !== $previousDocumentPath
            ) {
                Storage::disk('public')->delete($previousDocumentPath);
            }

            $this->dispatch('reservationUpdated', reservationId: $updatedReservation->id);

            $month = $checkIn->format('Y-m');
            return redirect()
                ->route('reservations.index', ['view' => 'calendar', 'month' => $month])
                ->with('success', $hasOperationalStay
                    ? 'Reserva actualizada. Como ya tiene estadia, solo se aplicaron cambios de precio/datos y no de fechas/habitaciones.'
                    : 'Reserva actualizada exitosamente.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
        } catch (\Throwable $e) {
            if (!empty($storedDocumentPath)) {
                Storage::disk('public')->delete($storedDocumentPath);
            }

            Log::error('Error updating reservation', [
                'reservation_id' => $this->editingReservation?->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->addError('general', 'Error al actualizar la reserva: ' . $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    private function requireReservationModel(): Reservation
    {
        if ($this->editingReservation === null) {
            throw new ReservationRequiredForEditException();
        }

        return $this->editingReservation;
    }
}
