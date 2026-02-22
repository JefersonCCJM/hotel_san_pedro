<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Reservation;
use Carbon\Carbon;

final class ReservationFormService
{
    private const DEFAULT_CHECK_IN_TIME = '14:00';
    /**
     * @return array{valid: bool, errors: array<string, string>}
     */
    public function validateDates(Carbon $checkIn, Carbon $checkOut, bool $enforceNotPast): array
    {
        $errors = [];

        if ($enforceNotPast) {
            $today = Carbon::today();
            if ($checkIn->isBefore($today)) {
                $errors['check_in_date'] = 'La fecha de entrada no puede ser anterior al día actual.';
            }
        }

        if ($checkOut->lte($checkIn)) {
            $errors['check_out_date'] = 'La fecha de salida debe ser posterior a la fecha de entrada.';
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function isRoomAvailable(
        int $roomId,
        Carbon $checkIn,
        Carbon $checkOut,
        ?int $excludeReservationId
    ): bool {
        $availabilityService = new RoomAvailabilityService();

        return $availabilityService->isRoomAvailableForDates(
            $roomId,
            $checkIn,
            $checkOut,
            $excludeReservationId
        );
    }

    /**
     * @param array{
     *   occupancyPrices?: array<int, float|int|string>,
     *   capacity?: int|float|string,
     *   price?: float|int|string,
     *   price1Person?: float|int|string,
     *   priceAdditionalPerson?: float|int|string
     * } $room
     */
    public function calculatePriceForRoom(array $room, int $guestsCount): float
    {
        if ($guestsCount <= 0) {
            return 0.0;
        }

        $capacityRaw = $room['capacity'] ?? 0;
        $capacity = is_numeric($capacityRaw) ? (int) $capacityRaw : 0;
        $effectiveCapacity = $capacity > 0 ? $capacity : $guestsCount;
        $actualGuests = min($guestsCount, $effectiveCapacity);

        $occupancyPrices = $room['occupancyPrices'] ?? [];
        $price1 = 0.0;
        if (is_array($occupancyPrices) && array_key_exists(1, $occupancyPrices) && is_numeric($occupancyPrices[1])) {
            $price1 = (float) $occupancyPrices[1];
        } elseif (array_key_exists('price1Person', $room) && is_numeric($room['price1Person'])) {
            $price1 = (float) $room['price1Person'];
        } elseif (array_key_exists('price', $room) && is_numeric($room['price'])) {
            $price1 = (float) $room['price'];
        }

        $additionalPrice = 0.0;
        if (array_key_exists('priceAdditionalPerson', $room) && is_numeric($room['priceAdditionalPerson'])) {
            $additionalPrice = (float) $room['priceAdditionalPerson'];
        }

        $additionalPersons = max(0, $actualGuests - 1);

        return $price1 + ($additionalPrice * $additionalPersons);
    }

    /**
     * @param array<int, array{id: int, occupancyPrices?: array<int, float|int|string>, capacity?: int|float|string, price?: float|int|string, price1Person?: float|int|string, priceAdditionalPerson?: float|int|string}> $roomsData
     * @param array<int, int> $selectedRoomIds
     * @param array<int, array<int, array{id: int}>> $roomGuests
     */
    public function calculateTotal(
        array $roomsData,
        array $selectedRoomIds,
        array $roomGuests,
        Carbon $checkIn,
        Carbon $checkOut
    ): float {
        $nights = $checkIn->diffInDays($checkOut);
        if ($nights <= 0) {
            return 0.0;
        }

        $roomsById = [];
        foreach ($roomsData as $room) {
            $roomId = $room['id'] ?? null;
            if ($roomId !== null) {
                $roomsById[(int) $roomId] = $room;
            }
        }

        $total = 0.0;
        foreach ($selectedRoomIds as $roomId) {
            $roomIdInt = (int) $roomId;
            $room = $roomsById[$roomIdInt] ?? null;
            if ($room === null) {
                continue;
            }

            $guestsForRoom = $roomGuests[$roomIdInt] ?? [];
            $guestCount = is_array($guestsForRoom) ? count($guestsForRoom) : 0;
            if ($guestCount <= 0) {
                continue;
            }

            $pricePerNight = $this->calculatePriceForRoom($room, $guestCount);
            $total += $pricePerNight * $nights;
        }

        return $total;
    }

    /**
     * @return array{
     *   customerId: string,
     *   checkIn: string,
     *   checkOut: string,
     *   checkInTime: string,
     *   total: float,
     *   deposit: float,
     *   guestsCount: int,
     *   adults: int,
     *   children: int,
     *   notes: string,
     *   paymentMethod: string,
     *   showMultiRoomSelector: bool,
     *   roomId: string,
     *   selectedRoomIds: array<int, int>,
     *   roomGuests: array<int, array<int, array{id: int, name: string, identification: string, phone: string, email: string|null}>>
     * }
     */
    public function loadReservationData(Reservation $reservation): array
    {
        $reservation->load([
            'customer.taxProfile',
            'reservationRooms.room',
            'reservationRooms.guests.taxProfile',
            'payments.paymentMethod',
        ]);

        $reservationRooms = $reservation->reservationRooms;
        $isMultiRoom = $reservationRooms->count() > 1;
        $checkInDate = $reservationRooms->pluck('check_in_date')->filter()->sort()->first();
        $checkOutDate = $reservationRooms->pluck('check_out_date')->filter()->sortDesc()->first();
        $checkInTime = $reservationRooms->pluck('check_in_time')->filter()->first();

        $reservationRoomsSubtotal = (float) $reservationRooms->sum(static fn ($room) => (float) ($room->subtotal ?? 0));
        $enteredTotal = (float) ($reservation->total_amount ?? 0);
        $totalAmount = $enteredTotal > 0 ? $enteredTotal : max(0, $reservationRoomsSubtotal);

        $paymentsTotal = (float) ($reservation->payments->sum('amount') ?? 0);
        $depositAmount = $paymentsTotal > 0
            ? $paymentsTotal
            : (float) ($reservation->deposit_amount ?? 0);
        $paymentMethod = $reservation->payments
            ->filter(static fn ($payment): bool => (float) ($payment->amount ?? 0) > 0)
            ->sortByDesc(static fn ($payment) => $payment->paid_at ?? $payment->created_at)
            ->map(static function ($payment): string {
                $code = (string) ($payment->paymentMethod->code ?? '');
                return strtolower(trim($code));
            })
            ->first(static fn (string $code): bool => in_array($code, ['efectivo', 'transferencia'], true));

        if (!is_string($paymentMethod) || $paymentMethod === '') {
            $paymentMethod = 'efectivo';
        }

        $roomId = $this->resolveSingleRoomIdString($reservation, $reservationRooms, $isMultiRoom);
        $selectedRoomIds = $isMultiRoom
            ? $reservationRooms->pluck('room_id')->map(static fn ($id): int => (int) $id)->toArray()
            : [];

        $roomGuests = $isMultiRoom
            ? $this->buildRoomGuestsMulti($reservationRooms)
            : $this->buildRoomGuestsSingle($reservation, $reservationRooms, $roomId);

        return [
            'customerId' => (string) ($reservation->client_id ?? ''),
            'checkIn' => $checkInDate ? Carbon::parse($checkInDate)->format('Y-m-d') : '',
            'checkOut' => $checkOutDate ? Carbon::parse($checkOutDate)->format('Y-m-d') : '',
            'checkInTime' => $this->normalizeTimeTo24h($checkInTime),
            'total' => $totalAmount,
            'deposit' => $depositAmount,
            'guestsCount' => (int) ($reservation->total_guests ?? 0),
            'adults' => (int) ($reservation->adults ?? 0),
            'children' => (int) ($reservation->children ?? 0),
            'notes' => (string) ($reservation->notes ?? ''),
            'paymentMethod' => $paymentMethod,
            'showMultiRoomSelector' => $isMultiRoom,
            'roomId' => $roomId,
            'selectedRoomIds' => $selectedRoomIds,
            'roomGuests' => $roomGuests,
        ];
    }

    private function resolveSingleRoomIdString(Reservation $reservation, $reservationRooms, bool $isMultiRoom): string
    {
        if ($isMultiRoom) {
            return '';
        }

        $singleRoomId = $reservationRooms->first()?->room_id ?? $reservation->room_id;
        if ($singleRoomId === null) {
            return '';
        }

        return (string) (int) $singleRoomId;
    }

    /**
     * @return array<int, array<int, array{id: int, name: string, identification: string, phone: string, email: string|null}>>
     */
    private function buildRoomGuestsMulti($reservationRooms): array
    {
        $roomGuests = [];

        foreach ($reservationRooms as $reservationRoom) {
            $roomIdInt = (int) $reservationRoom->room_id;
            $roomGuests[$roomIdInt] = $reservationRoom->guests->map(
                fn ($guest): array => $this->mapGuest($guest)
            )->toArray();
        }

        return $roomGuests;
    }

    /**
     * @return array<int, array<int, array{id: int, name: string, identification: string, phone: string, email: string|null}>>
     */
    private function buildRoomGuestsSingle(Reservation $reservation, $reservationRooms, string $roomId): array
    {
        $result = [];

        if ($roomId !== '') {
            $reservationRoom = $reservationRooms->first();
            if ($reservationRoom !== null && $reservationRoom->guests->isNotEmpty()) {
                $result[(int) $roomId] = $reservationRoom->guests->map(
                    fn ($guest): array => $this->mapGuest($guest)
                )->toArray();
            }
        }

        // Ensure main customer is visible as assigned guest in single-room mode.
        // The UI considers "assignedGuests" as "additional", but operationally the main customer is always a guest.
        if ($roomId !== '' && empty($result[(int) $roomId]) && $reservation->relationLoaded('customer') && $reservation->customer) {
            $result[(int) $roomId] = [$this->mapGuest($reservation->customer)];
        }

        return $result;
    }

    /**
     * @return array{id: int, name: string, identification: string, phone: string, email: string|null}
     */
    private function mapGuest($guest): array
    {
        return [
            'id' => (int) $guest->id,
            'name' => (string) ($guest->name ?? ''),
            'identification' => (string) ($guest->taxProfile->identification ?? 'S/N'),
            'phone' => (string) ($guest->phone ?? 'S/N'),
            'email' => $guest->email !== null ? (string) $guest->email : null,
        ];
    }

    private function normalizeTimeTo24h(?string $rawTime): string
    {
        $normalized = self::DEFAULT_CHECK_IN_TIME;

        if ($rawTime !== null) {
            $value = trim(mb_strtolower($rawTime));

            if ($this->isTime24h($value)) {
                $normalized = $value;
            } else {
                $parsed = $this->parseAmPmTime($value);
                if ($parsed !== null) {
                    $normalized = $this->formatTime24h($parsed['h24'], $parsed['m']);
                }
            }
        }

        return $normalized;
    }

    private function isTime24h(string $value): bool
    {
        return preg_match('/^([0-1]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }

    /**
     * @return array{h24: int, m: int}|null
     */
    private function parseAmPmTime(string $raw): ?array
    {
        $value = str_replace([' ', '.', "\t"], '', $raw);

        if (preg_match('/^(?<h>\d{1,2}):(?<m>\d{2})(?<ampm>am|pm)$/', $value, $m) !== 1) {
            return null;
        }

        $h = (int) $m['h'];
        $min = (int) $m['m'];
        $ampm = (string) $m['ampm'];

        if ($h < 1 || $h > 12 || $min < 0 || $min > 59) {
            return null;
        }

        $h24 = $h;
        if ($ampm === 'am') {
            $h24 = $h === 12 ? 0 : $h;
        }
        if ($ampm === 'pm') {
            $h24 = $h === 12 ? 12 : $h + 12;
        }

        return ['h24' => $h24, 'm' => $min];
    }

    private function formatTime24h(int $h24, int $m): string
    {
        return str_pad((string) $h24, 2, '0', STR_PAD_LEFT)
            . ':'
            . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    }
}
