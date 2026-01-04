<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\RoomDisplayStatus;

class Room extends Model
{
    use HasFactory;
    protected $fillable = [
        'room_number',
        'room_type_id',
        'ventilation_type_id',
        'beds_count',
        'max_capacity',
        'base_price_per_night',
        'last_cleaned_at',
        'is_active',
        'last_cleaned_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'room_type_id' => 'integer',
        'ventilation_type_id' => 'integer',
        'beds_count' => 'integer',
        'max_capacity' => 'integer',
        'base_price_per_night' => 'decimal:2',
        'is_active' => 'boolean',
        'last_cleaned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the room type associated with the room.
     */
    public function RoomType()
    {
        return $this->belongsTo(RoomType::class);
    }


    /**
     * Get the ventilation type associated with the room.
     */
    public function VentilationType()
    {
        return $this->belongsTo(VentilationType::class);
    }

    /**
     * Get the reservations for the room.
     */
    public function reservations()
    {
        return $this->belongsToMany(Reservation::class,'reservation_rooms');
    }

    /**
     * Get the reservation rooms for the room.
     */
    public function reservationRooms()
    {
        return $this->hasMany(ReservationRoom::class);
    }

    /**
     * Get the maintenance blocks for the room.
     */
    public function maintenanceBlocks()
    {
        return $this->hasMany(RoomMaintenanceBlock::class);
    }

    public function dailyStatuses(): HasMany
    {
        return $this->hasMany(RoomDailyStatus::class);
    }

    /**
     * Get the special rates for the room.
     */
    public function rates()
    {
        return $this->hasMany(RoomRate::class);
    }

    /**
     * Get the dynamic price list for a specific date.
     */
    public function getPricesForDate($date)
    {
        return $this->rates;
    }

    /**
     * Check if the room is occupied on a specific date.
     * OCCUPATION IS DERIVED FROM RESERVATIONS, NOT FROM room_statuses.
     * This is the SINGLE SOURCE OF TRUTH for occupancy.
     *
     * Enhanced with validation to ensure only valid reservations are considered.
     *
     * @param \Carbon\Carbon|null $date Date to check. Defaults to today.
     * @return bool True if room has an active reservation on the given date.
     */
    public function isOccupied(?\Carbon\Carbon $date = null): bool
    {
        $date = $date ?? \Carbon\Carbon::today();

        // Normalize date to start of day for consistent comparison
        $normalizedDate = $date->copy()->startOfDay();

        // Room is occupied if: check_in <= date AND check_out > date
        // If checkout is today, room is NOT occupied (guest leaves today)
        // Validate that checkout is after checkin (data integrity)
        return $this->reservationRooms()
            ->where('check_in_date', '<=', $normalizedDate->toDateString())
            ->where('check_out_date', '>', $normalizedDate->toDateString())
            ->whereColumn('check_out_date', '>', 'check_in_date')
            ->exists();
    }

    /**
     * Get the active reservation for a specific date.
     *
     * @param \Carbon\Carbon|null $date Date to check. Defaults to today.
     * @return \App\Models\Reservation|null
     */
    public function getActiveReservation(?\Carbon\Carbon $date = null): ?\App\Models\Reservation
    {
        $date = $date ?? \Carbon\Carbon::today();

        $reservationRoom = $this->reservationRooms()
            ->where('check_in_date', '<=', $date?->toDateString())
            ->where('check_out_date', '>', $date?->toDateString())
            ->orderBy('check_in_date', 'asc')
            ->with('reservation')
            ->first();

        return $reservationRoom?->reservation;
    }

    /**
     * Check if the room is in maintenance (blocks everything).
     *
     * @return bool
     */
    public function isInMaintenance(): bool
    {
        return $this->maintenanceBlocks()
            ->whereHas('status', function($q) {
                $q->where('code', 'active');
            })
            ->exists();
    }

    /**
     * Get the cleaning status of the room.
     * SINGLE SOURCE OF TRUTH for cleaning status.
     *
     * Rules (Single Responsibility Principle - each rule is clear):
     * - If never cleaned (last_cleaned_at is NULL) → needs cleaning
     * - If room is OCCUPIED and 24+ hours have passed since last_cleaned_at → needs cleaning
     * - If room is FREE → clean (no 24-hour rule, stays clean indefinitely)
     * - If room is OCCUPIED but less than 24 hours have passed → clean
     *
     * IMPORTANT: The 24-hour rule ONLY applies when the room is occupied.
     * A free room that hasn't been used stays clean indefinitely.
     *
     * Cleaning status is managed explicitly:
     * - When a room is released as "libre" or "limpia" → last_cleaned_at = now() (clean)
     * - When a room is released as "pendiente_aseo" → last_cleaned_at = null (needs cleaning)
     * - When a stay is continued → last_cleaned_at = null (will need cleaning when released)
     *
     * @param \Carbon\Carbon|null $date Date to check. Defaults to today.
     * @return array{code: string, label: string, color: string, icon: string}
     */
    public function cleaningStatus(?\Carbon\Carbon $date = null): array
    {
        $date = $date ?? \Carbon\Carbon::today();

        // If never cleaned or explicitly marked as needing cleaning (last_cleaned_at is NULL)
        if (!$this->last_cleaned_at) {
            return $this->getPendingCleaningStatus();
        }

        // Check if room is currently occupied (Dependency Inversion - uses abstraction)
        $isOccupied = $this->isOccupied($date);

        // If room is NOT occupied, it stays clean indefinitely (Open/Closed Principle)
        if (!$isOccupied) {
            return $this->getCleanStatus();
        }

        // If room is OCCUPIED, check if cleaning occurred before check-in
        // Get active reservation to compare cleaning date with check-in date
        $reservation = $this->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->orderBy('check_in_date', 'asc')
            ->first();

        // Parse and normalize cleaning date
        $cleaningDate = $this->last_cleaned_at instanceof \Carbon\Carbon
            ? $this->last_cleaned_at
            : \Carbon\Carbon::parse($this->last_cleaned_at);

        $cleaningDateNormalized = $cleaningDate->copy()->startOfDay();

        // If room was cleaned BEFORE check-in, it stays clean during the stay
        // Hotel rule: cleaning before guest arrival is valid for the entire initial stay
        if ($reservation) {
            $checkInDate = \Carbon\Carbon::parse($reservation->check_in_date)->startOfDay();

            // If cleaning occurred before check-in, room is clean (no 24-hour rule applies)
            if ($cleaningDateNormalized->lt($checkInDate)) {
                return $this->getCleanStatus();
            }
        }

        // If room was cleaned AFTER check-in (or on check-in day), apply 24-hour rule
        // Calculate hours from last_cleaned_at to the queried date (not now())
        // This ensures consistency when querying past or future dates
        $queryDateNormalized = $date->copy()->startOfDay();

        $hoursSinceLastCleaning = $cleaningDateNormalized->diffInHours($queryDateNormalized);

        if ($hoursSinceLastCleaning >= 24) {
            return $this->getPendingCleaningStatus();
        }

        return $this->getCleanStatus();
    }

    /**
     * Get pending cleaning status array (Single Responsibility)
     *
     * @return array{code: string, label: string, color: string, icon: string}
     */
    private function getPendingCleaningStatus(): array
    {
        return [
            'code' => 'pendiente',
            'label' => 'Pendiente por Aseo',
            'color' => 'bg-yellow-100 text-yellow-800',
            'icon' => 'fa-broom',
        ];
    }

    /**
     * Get clean status array (Single Responsibility)
     *
     * @return array{code: string, label: string, color: string, icon: string}
     */
    private function getCleanStatus(): array
    {
        return [
            'code' => 'limpia',
            'label' => 'Limpia',
            'color' => 'bg-green-100 text-green-800',
            'icon' => 'fa-check-circle',
        ];
    }

    /**
     * Get the display status of the room for a specific date.
     * SINGLE SOURCE OF TRUTH for room display status (ESTADO column).
     * Returns a RoomStatus Enum based on business logic priority:
     * 1. If room is in maintenance → MANTENIMIENTO
     * 2. If room has active reservation → OCUPADA
     * 3. If room status is SUCIA → SUCIA
     * 4. If room has reservation ending today → PENDIENTE_CHECKOUT
     * 5. Otherwise → LIBRE
     *
     * NOTE: Cleaning status (Pendiente por Aseo) is handled separately by cleaningStatus()
     * and displayed in the "ESTADO DE LIMPIEZA" column, not in the "ESTADO" column.
     *
     * @param \Carbon\Carbon|null $date Date to check. Defaults to today.
     * @return RoomDisplayStatus
     */
    public function getDisplayStatus(?\Carbon\Carbon $date = null): RoomDisplayStatus
    {
        $date = $date ?? \Carbon\Carbon::today();
        $normalizedDate = $date->copy()->startOfDay();

        // Priority 1: Maintenance blocks everything
        if ($this->isInMaintenance()) {
            return RoomDisplayStatus::MANTENIMIENTO;
        }

        // Priority 2: Active stay/reservation means occupied
        if ($this->isOccupied($normalizedDate)) {
            return RoomDisplayStatus::OCUPADA;
        }

        // Priority 3: Check if reservation ends today or starts today (Pendiente Checkout)
        $previousDay = $normalizedDate->copy()->subDay();
        $tomorrow = $normalizedDate->copy()->addDay();
        $wasOccupiedYesterday = $this->isOccupied($previousDay);

        // Case 1: Was occupied yesterday, checkout today
        if ($wasOccupiedYesterday) {
            $reservationEndingToday = $this->reservationRooms()
                ->where('check_in_date', '<=', $previousDay->toDateString())
                ->where('check_out_date', '=', $date->toDateString())
                ->exists();

            if ($reservationEndingToday) {
                return RoomDisplayStatus::PENDIENTE_CHECKOUT;
            }
        }

        // Case 2: One-day reservation starting today
        $oneDayReservationStartingToday = $this->reservationRooms()
            ->where('check_in_date', '=', $normalizedDate->toDateString())
            ->where('check_out_date', '=', $tomorrow->toDateString())
            ->whereColumn('check_out_date', '>', 'check_in_date')
            ->exists();

        if ($oneDayReservationStartingToday) {
            return RoomDisplayStatus::PENDIENTE_CHECKOUT;
        }

        // Case 3: Reservation ending tomorrow
        $reservationEndingTomorrow = $this->reservationRooms()
            ->where('check_in_date', '<=', $normalizedDate->toDateString())
            ->where('check_out_date', '=', $tomorrow->toDateString())
            ->whereColumn('check_out_date', '>', 'check_in_date')
            ->exists();

        if ($reservationEndingTomorrow) {
            return RoomDisplayStatus::PENDIENTE_CHECKOUT;
        }

        // Priority 4: If room needs cleaning
        $cleaningStatus = $this->cleaningStatus($date);
        if ($cleaningStatus['code'] === 'needs_cleaning') {
            return RoomDisplayStatus::SUCIA;
        }

        // Priority 5: Check if there's a future reservation (RESERVADA)
        $hasFutureReservation = $this->reservationRooms()
            ->where('check_in_date', '>', $normalizedDate->toDateString())
            ->exists();

        if ($hasFutureReservation) {
            return RoomDisplayStatus::RESERVADA;
        }

        // Priority 6: Default to LIBRE (no stays, no reservations, no maintenance)
        return RoomDisplayStatus::LIBRE;
    }

    /**
     * Get the reservation that causes Pendiente Checkout status for a specific date.
     *
     * @param \Carbon\Carbon|null $date Date to check. Defaults to today.
     * @return \App\Models\Reservation|null
     */
    public function getPendingCheckoutReservation(?\Carbon\Carbon $date = null): ?\App\Models\Reservation
    {
        $date = $date ?? \Carbon\Carbon::today();

        // If not in Pendiente Checkout status, return null
        if ($this->getDisplayStatus($date) !== RoomStatus::PENDIENTE_CHECKOUT) {
            return null;
        }

        $previousDay = $date->copy()->subDay();
        $tomorrow = $date->copy()->addDay();

        // Case 1: Was occupied yesterday, checkout today
        $reservationEndingToday = $this->reservations()
            ->where('check_in_date', '<=', $previousDay)
            ->where('check_out_date', '=', $date->toDateString())
            ->first();

        if ($reservationEndingToday) {
            return $reservationEndingToday;
        }

        // Case 2: Reservation starts today and is one-day reservation (check-in today, check-out tomorrow)
        $oneDayReservationStartingToday = $this->reservations()
            ->where('check_in_date', '=', $date->toDateString())
            ->where('check_out_date', '=', $tomorrow->toDateString())
            ->first();

        if ($oneDayReservationStartingToday) {
            return $oneDayReservationStartingToday;
        }

        // Case 3: Reservation has one day remaining (check-out tomorrow)
        $reservationEndingTomorrow = $this->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '=', $tomorrow->toDateString())
            ->first();

        return $reservationEndingTomorrow;
    }

    /**
     * Accessor for display_status attribute.
     * Uses getDisplayStatus() with today's date by default.
     * This allows using $room->display_status in views.
     *
     * @return RoomDisplayStatus
     */
    public function getDisplayStatusAttribute(): RoomDisplayStatus
    {
        return $this->getDisplayStatus();
    }

    /**
     * Accessor for cleaning_status attribute.
     * Uses cleaningStatus() with today's date by default.
     * This allows using $room->cleaning_status in views.
     *
     * @return array{code: string, label: string, color: string, icon: string}
     */
    public function getCleaningStatusAttribute(): array
    {
        return $this->cleaningStatus();
    }

    /**
     * Validate and clean invalid reservations.
     * This helps maintain data integrity (Interface Segregation Principle)
     *
     * @return array{invalid_count: int, invalid_reservations: \Illuminate\Database\Eloquent\Collection}
     */
    public function validateReservations(): array
    {
        $today = \Carbon\Carbon::today();

        // Find reservations with invalid date ranges (check_out <= check_in)
        $invalidReservations = $this->reservations()
            ->whereColumn('check_out_date', '<=', 'check_in_date')
            ->get();

        if ($invalidReservations->isNotEmpty()) {
            \Illuminate\Support\Facades\Log::warning("Room {$this->room_number} has invalid reservations", [
                'room_id' => $this->id,
                'room_number' => $this->room_number,
                'invalid_count' => $invalidReservations->count(),
                'reservation_ids' => $invalidReservations->pluck('id')->toArray()
            ]);
        }

        return [
            'invalid_count' => $invalidReservations->count(),
            'invalid_reservations' => $invalidReservations
        ];
    }
}
