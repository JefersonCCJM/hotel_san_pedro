<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    /**
     * @throws ValidationException
     */
    public function validateReservationData(array $data, array &$errors): void
    {
        Log::error('Validando datos de reserva en ReservationService');

        if (empty($data['customerId'] ?? null)) {
            $errors['customerId'] = 'Debe seleccionar un cliente.';
        }

        $roomIds = $data['room_ids'] ?? (!empty($data['room_id']) ? [$data['room_id']] : []);
        if (empty($roomIds) || (is_array($roomIds) && empty(array_filter($roomIds)))) {
            $errors['roomId'] = 'Debe seleccionar al menos una habitacion.';
        }

        if (empty($data['check_in_date'] ?? null) || empty($data['check_out_date'] ?? null)) {
            $errors['dates'] = 'Debe seleccionar las fechas de check-in y check-out.';
        }

        if (!empty($data['check_in_date']) && !empty($data['check_out_date'])) {
            $checkIn = Carbon::parse($data['check_in_date']);
            $checkOut = Carbon::parse($data['check_out_date']);

            if ($checkIn >= $checkOut) {
                $errors['dates'] = 'La fecha de check-out debe ser posterior a la de check-in.';
            }

            if ($checkIn < now()->startOfDay()) {
                $errors['dates'] = 'No se puede hacer check-in en fechas pasadas.';
            }
        }

        $total = (float) ($data['total_amount'] ?? 0);
        if ($total <= 0) {
            $errors['total'] = 'El total debe ser mayor a 0.';
        }

        $deposit = (float) ($data['deposit'] ?? 0);
        if ($deposit < 0) {
            $errors['deposit'] = 'El deposito no puede ser negativo.';
        }

        if ($deposit > $total) {
            $errors['deposit'] = 'El deposito no puede ser mayor al total.';
        }

        if (array_key_exists('adults', $data) && $data['adults'] !== null && $data['adults'] !== '') {
            if (!is_numeric($data['adults']) || (int) $data['adults'] < 0) {
                $errors['adults'] = 'La cantidad de adultos debe ser un numero valido mayor o igual a 0.';
            }
        }

        if (array_key_exists('children', $data) && $data['children'] !== null && $data['children'] !== '') {
            if (!is_numeric($data['children']) || (int) $data['children'] < 0) {
                $errors['children'] = 'La cantidad de ninos debe ser un numero valido mayor o igual a 0.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function prepareReservationData(array $formData, int $guestsCount = 1): array
    {
        $roomIds = $formData['room_ids'] ?? (!empty($formData['room_id']) ? [$formData['room_id']] : []);

        return [
            'customerId' => $formData['customerId'],
            'room_id' => empty($roomIds) ? null : $roomIds[0],
            'room_ids' => !empty($roomIds) ? $roomIds : null,
            'check_in_date' => $formData['check_in_date'],
            'check_out_date' => $formData['check_out_date'],
            'check_in_time' => $formData['check_in_time'] ?? config('hotel.check_in_time', '15:00'),
            'total_amount' => (float) ($formData['total_amount'] ?? 0),
            'deposit' => (float) ($formData['deposit'] ?? 0),
            'guests_count' => $guestsCount > 0 ? $guestsCount : 1,
            'adults' => $formData['adults'] ?? null,
            'children' => $formData['children'] ?? null,
            'room_guests' => $formData['room_guests'] ?? [],
            'notes' => $formData['notes'] ?? null,
            'payment_method' => $formData['payment_method'] ?? 'efectivo',
            'reservation_date' => now()->format('Y-m-d'),
            'guests_document_path' => $formData['guests_document_path'] ?? null,
            'guests_document_original_name' => $formData['guests_document_original_name'] ?? null,
            'guests_document_mime_type' => $formData['guests_document_mime_type'] ?? null,
            'guests_document_size' => $formData['guests_document_size'] ?? null,
        ];
    }

    /**
     * @throws \Exception
     */
    public function createReservation(array $data): Reservation
    {
        Log::error('Creando reserva desde ReservationService');

        try {
            DB::beginTransaction();

            $roomIds = $data['room_ids'] ?? (!empty($data['room_id']) ? [$data['room_id']] : []);
            $roomIds = array_values(array_filter(array_map('intval', (array) $roomIds), fn (int $id) => $id > 0));

            if (empty($roomIds)) {
                throw new \Exception('No se han seleccionado habitaciones validas.');
            }

            $checkInDate = Carbon::parse($data['check_in_date']);
            $checkOutDate = Carbon::parse($data['check_out_date']);
            $totalAmount = (float) ($data['total_amount'] ?? 0);
            $initialDeposit = max(0, (float) ($data['deposit'] ?? 0));
            $balanceDue = max(0, $totalAmount - $initialDeposit);
            $paymentStatusCode = $balanceDue <= 0
                ? 'paid'
                : ($initialDeposit > 0 ? 'partial' : 'pending');
            $paymentStatusId = DB::table('payment_statuses')
                ->where('code', $paymentStatusCode)
                ->value('id');
            if (empty($paymentStatusId)) {
                $paymentStatusId = DB::table('payment_statuses')
                    ->where('code', 'pending')
                    ->value('id') ?: 1;
            }

            $year = date('Y');
            $prefix = "RES-{$year}-";

            $lastCode = Reservation::withTrashed()
                ->where('reservation_code', 'like', "{$prefix}%")
                ->orderBy('reservation_code', 'desc')
                ->value('reservation_code');

            $nextNumber = 1;
            if ($lastCode) {
                $lastNumber = substr($lastCode, -4);
                $nextNumber = (int) $lastNumber + 1;
            }

            $reservationCode = $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

            $guestComposition = $this->resolveGuestComposition($data);

            $reservationData = [
                'reservation_code' => $reservationCode,
                'client_id' => $data['customerId'],
                'status_id' => 1,
                'total_guests' => $guestComposition['total'],
                'adults' => $guestComposition['adults'],
                'children' => $guestComposition['children'],
                'total_amount' => $totalAmount,
                'deposit_amount' => $initialDeposit,
                'balance_due' => $balanceDue,
                'payment_status_id' => $paymentStatusId,
                'source_id' => 1,
                'created_by' => auth()->id() ?? 1,
                'notes' => $data['notes'] ?? null,
                'guests_document_path' => $data['guests_document_path'] ?? null,
                'guests_document_original_name' => $data['guests_document_original_name'] ?? null,
                'guests_document_mime_type' => $data['guests_document_mime_type'] ?? null,
                'guests_document_size' => $data['guests_document_size'] ?? null,
                'check_in_date' => $checkInDate->format('Y-m-d'),
                'check_out_date' => $checkOutDate->format('Y-m-d'),
                'room_id' => $roomIds[0],
            ];

            $reservation = Reservation::create($reservationData);

            $roomsCount = max(1, count($roomIds));
            $baseSubtotal = round($totalAmount / $roomsCount, 2);
            $allocatedSubtotal = 0.0;
            $lastIndex = $roomsCount - 1;
            $nights = max(1, $checkInDate->diffInDays($checkOutDate));

            foreach ($roomIds as $index => $roomId) {
                $roomSubtotal = $index === $lastIndex
                    ? round($totalAmount - $allocatedSubtotal, 2)
                    : $baseSubtotal;
                $allocatedSubtotal = round($allocatedSubtotal + $roomSubtotal, 2);

                ReservationRoom::create([
                    'reservation_id' => $reservation->id,
                    'room_id' => $roomId,
                    'check_in_date' => $checkInDate->format('Y-m-d'),
                    'check_out_date' => $checkOutDate->format('Y-m-d'),
                    'check_in_time' => $data['check_in_time'] ?? config('hotel.check_in_time', '15:00'),
                    'check_out_time' => null,
                    'nights' => $nights,
                    'price_per_night' => $nights > 0 ? round($roomSubtotal / $nights, 2) : $roomSubtotal,
                    'subtotal' => $roomSubtotal,
                ]);
            }

            if ($initialDeposit > 0) {
                $paymentMethodCode = strtolower(trim((string) ($data['payment_method'] ?? 'efectivo')));
                if (!in_array($paymentMethodCode, ['efectivo', 'transferencia'], true)) {
                    $paymentMethodCode = 'efectivo';
                }

                $paymentMethodName = $paymentMethodCode === 'transferencia'
                    ? 'Transferencia'
                    : 'Efectivo';

                DB::table('payments_methods')->updateOrInsert(
                    ['code' => $paymentMethodCode],
                    [
                        'name' => $paymentMethodName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $paymentMethodId = DB::table('payments_methods')
                    ->where('code', $paymentMethodCode)
                    ->value('id');

                Payment::create([
                    'reservation_id' => $reservation->id,
                    'amount' => $initialDeposit,
                    'payment_method_id' => $paymentMethodId ?: null,
                    'bank_name' => $paymentMethodCode === 'transferencia'
                        ? ($data['bank_name'] ?? null)
                        : null,
                    'reference' => $paymentMethodCode === 'transferencia'
                        ? ($data['reference'] ?? 'Abono inicial de reserva')
                        : 'Abono inicial de reserva',
                    'paid_at' => now(),
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            return $reservation;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando reserva desde ReservationService', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function updateReservation(Reservation $reservation, array $data, bool $preserveRoomAssignments = false): Reservation
    {
        Log::info('Actualizando reserva desde ReservationService', [
            'reservation_id' => $reservation->id,
        ]);

        try {
            DB::beginTransaction();

            if (method_exists($reservation, 'trashed') && $reservation->trashed()) {
                throw new \Exception('No se puede editar una reserva cancelada.');
            }

            $roomIds = $data['room_ids'] ?? (!empty($data['room_id']) ? [$data['room_id']] : []);
            $roomIds = array_values(array_filter(array_map('intval', (array) $roomIds), fn (int $id) => $id > 0));

            if (empty($roomIds)) {
                throw new \Exception('No se han seleccionado habitaciones validas.');
            }

            $checkInDate = Carbon::parse($data['check_in_date']);
            $checkOutDate = Carbon::parse($data['check_out_date']);
            $nights = max(1, $checkInDate->diffInDays($checkOutDate));

            $totalAmount = (float) ($data['total_amount'] ?? 0);
            $inputDeposit = (float) ($data['deposit'] ?? 0);
            $paymentsTotal = (float) ($reservation->payments()->sum('amount') ?? 0);
            $effectiveDeposit = max($inputDeposit, $paymentsTotal);
            $balanceDue = max(0, $totalAmount - $effectiveDeposit);

            $paymentStatusCode = $balanceDue <= 0
                ? 'paid'
                : ($effectiveDeposit > 0 ? 'partial' : 'pending');
            $paymentStatusId = DB::table('payment_statuses')
                ->where('code', $paymentStatusCode)
                ->value('id');

            $guestComposition = $this->resolveGuestComposition($data);

            $reservation->update([
                'client_id' => $data['customerId'],
                'room_id' => $roomIds[0],
                'check_in_date' => $checkInDate->format('Y-m-d'),
                'check_out_date' => $checkOutDate->format('Y-m-d'),
                'total_guests' => $guestComposition['total'],
                'adults' => $guestComposition['adults'],
                'children' => $guestComposition['children'],
                'total_amount' => $totalAmount,
                'deposit_amount' => $effectiveDeposit,
                'balance_due' => $balanceDue,
                'payment_status_id' => $paymentStatusId ?? $reservation->payment_status_id,
                'notes' => $data['notes'] ?? null,
                'guests_document_path' => $data['guests_document_path'] ?? $reservation->guests_document_path,
                'guests_document_original_name' => $data['guests_document_original_name'] ?? $reservation->guests_document_original_name,
                'guests_document_mime_type' => $data['guests_document_mime_type'] ?? $reservation->guests_document_mime_type,
                'guests_document_size' => $data['guests_document_size'] ?? $reservation->guests_document_size,
            ]);

            if ($preserveRoomAssignments) {
                $existingRooms = $reservation->reservationRooms()->orderBy('id')->get();

                if ($existingRooms->isNotEmpty()) {
                    $roomsCount = max(1, $existingRooms->count());
                    $baseSubtotal = round($totalAmount / $roomsCount, 2);
                    $allocatedSubtotal = 0.0;
                    $lastIndex = $roomsCount - 1;

                    foreach ($existingRooms as $index => $reservationRoom) {
                        $roomSubtotal = $index === $lastIndex
                            ? round($totalAmount - $allocatedSubtotal, 2)
                            : $baseSubtotal;

                        $allocatedSubtotal = round($allocatedSubtotal + $roomSubtotal, 2);
                        $roomNights = (int) ($reservationRoom->nights ?? 0);
                        if ($roomNights <= 0) {
                            $roomCheckIn = !empty($reservationRoom->check_in_date)
                                ? Carbon::parse($reservationRoom->check_in_date)
                                : $checkInDate;
                            $roomCheckOut = !empty($reservationRoom->check_out_date)
                                ? Carbon::parse($reservationRoom->check_out_date)
                                : $checkOutDate;
                            $roomNights = max(1, $roomCheckIn->diffInDays($roomCheckOut));
                        }

                        $reservationRoom->update([
                            'nights' => $roomNights,
                            'price_per_night' => round($roomSubtotal / max(1, $roomNights), 2),
                            'subtotal' => $roomSubtotal,
                        ]);
                    }
                } else {
                    $preserveRoomAssignments = false;
                }
            }

            if (!$preserveRoomAssignments) {
                $reservation->reservationRooms()->delete();

                $roomsCount = max(1, count($roomIds));
                $baseSubtotal = round($totalAmount / $roomsCount, 2);
                $allocatedSubtotal = 0.0;
                $lastIndex = $roomsCount - 1;

                foreach ($roomIds as $index => $roomId) {
                    $roomSubtotal = $index === $lastIndex
                        ? round($totalAmount - $allocatedSubtotal, 2)
                        : $baseSubtotal;

                    $allocatedSubtotal = round($allocatedSubtotal + $roomSubtotal, 2);
                    $pricePerNight = $nights > 0 ? round($roomSubtotal / $nights, 2) : $roomSubtotal;

                    ReservationRoom::create([
                        'reservation_id' => $reservation->id,
                        'room_id' => $roomId,
                        'check_in_date' => $checkInDate->format('Y-m-d'),
                        'check_out_date' => $checkOutDate->format('Y-m-d'),
                        'check_in_time' => $data['check_in_time'] ?? config('hotel.check_in_time', '15:00'),
                        'check_out_time' => null,
                        'nights' => $nights,
                        'price_per_night' => $pricePerNight,
                        'subtotal' => $roomSubtotal,
                    ]);
                }

                // Si no hay estadias creadas aun, eliminar noches huerfanas previas para evitar desalineacion.
                if (!$reservation->stays()->exists()) {
                    DB::table('stay_nights')->where('reservation_id', $reservation->id)->delete();
                }
            }

            DB::commit();

            return $reservation->refresh()->load([
                'customer.taxProfile',
                'reservationRooms.room',
                'reservationRooms.guests.taxProfile',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando reserva desde ReservationService', [
                'reservation_id' => $reservation->id ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function resolveGuestsCount(array $data): int
    {
        $guestsCount = (int) ($data['guests_count'] ?? 0);
        if ($guestsCount > 0) {
            return $guestsCount;
        }

        $roomGuests = $data['room_guests'] ?? [];
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

    /**
     * @return array{adults:int, children:int, total:int}
     */
    private function resolveGuestComposition(array $data): array
    {
        $adults = $this->normalizeOptionalNonNegativeInt($data['adults'] ?? null);
        $children = $this->normalizeOptionalNonNegativeInt($data['children'] ?? null);

        $hasCustomComposition = array_key_exists('adults', $data) || array_key_exists('children', $data);
        $customTotal = $adults + $children;

        if ($hasCustomComposition && $customTotal > 0) {
            return [
                'adults' => $adults,
                'children' => $children,
                'total' => $customTotal,
            ];
        }

        $fallbackTotal = $this->resolveGuestsCount($data);

        return [
            'adults' => $fallbackTotal,
            'children' => 0,
            'total' => $fallbackTotal,
        ];
    }

    private function normalizeOptionalNonNegativeInt($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (!is_numeric($value)) {
            return 0;
        }

        return max(0, (int) $value);
    }
}

