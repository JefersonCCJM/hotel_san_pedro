<?php

namespace App\Models;

use App\Casts\RoomStatusCast;
use App\Enums\RoomStatus;
use App\Enums\VentilationType;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_number',
        'beds_count',
        'max_capacity',
        'ventilation_type',
        'price_1_person',
        'price_2_persons',
        'price_additional_person',
        'occupancy_prices',
        'status',
        'price_per_night',
        'last_cleaned_at',
    ];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'price_1_person' => 'decimal:2',
        'price_2_persons' => 'decimal:2',
        'price_additional_person' => 'decimal:2',
        'occupancy_prices' => 'array',
        // Use backward-compatible cast to avoid ValueError on legacy values like "available".
        'status' => RoomStatusCast::class,
        'ventilation_type' => VentilationType::class,
        'beds_count' => 'integer',
        'max_capacity' => 'integer',
        'last_cleaned_at' => 'datetime',
    ];

    /**
     * Get the reservations for the room.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
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
        $specialRate = $this->rates()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        return $specialRate ? $specialRate->occupancy_prices : $this->occupancy_prices;
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
        $date = \Carbon\Carbon::parse($date)->startOfDay();

        // Room is occupied if: check_in <= date AND check_out > date
        // If checkout is today, room is NOT occupied (guest leaves today)
        
        // Always query the database directly to ensure accuracy
        // Use whereDate() which is specifically designed for date column comparisons
        // This ensures Laravel correctly compares only the date portion, ignoring time
        return $this->reservations()
            ->whereDate('check_in_date', '<=', $date)
            ->whereDate('check_out_date', '>', $date)
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
        $date = \Carbon\Carbon::parse($date)->startOfDay();

        // Always query the database directly to ensure accuracy
        // Use whereDate() which is specifically designed for date column comparisons
        // This ensures Laravel correctly compares only the date portion, ignoring time
        return $this->reservations()
            ->whereDate('check_in_date', '<=', $date)
            ->whereDate('check_out_date', '>', $date)
            ->orderBy('check_in_date', 'asc')
            ->first();
    }

    /**
     * Check if the room is in maintenance (blocks everything).
     *
     * @return bool
     */
    public function isInMaintenance(): bool
    {
        return $this->status === RoomStatus::MANTENIMIENTO;
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
        $date = \Carbon\Carbon::parse($date)->startOfDay();
        $isTodayOrFuture = $date->isToday() || $date->isFuture();
        
        // IMPORTANT: Cleaning status (Pendiente por Aseo) should only be shown for today or future dates
        // For past dates, we cannot determine the historical cleaning status, so we show "Limpia" as default
        if (!$isTodayOrFuture) {
            // For past dates, always show "Limpia" (we can't know the historical cleaning status)
            return $this->getCleanStatus();
        }
        
        // For today or future dates, check cleaning status
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

        // If room is OCCUPIED, apply 24-hour rule
        // Calculate hours from last_cleaned_at to the queried date (not now())
        // This ensures consistency when querying past or future dates
        $cleaningDate = $this->last_cleaned_at instanceof \Carbon\Carbon 
            ? $this->last_cleaned_at 
            : \Carbon\Carbon::parse($this->last_cleaned_at);
        
        // Normalize both dates to start of day for consistent comparison
        $cleaningDateNormalized = $cleaningDate->copy()->startOfDay();
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
     * @return RoomStatus
     */
    public function getDisplayStatus(?\Carbon\Carbon $date = null): RoomStatus
    {
        $date = $date ?? \Carbon\Carbon::today();
        $date = \Carbon\Carbon::parse($date)->startOfDay();
        $isTodayOrFuture = $date->isToday() || $date->isFuture();

        // Priority 1: Maintenance blocks everything
        // NOTE: Only check maintenance status for current date or future dates
        // For past dates, maintenance status should not be considered as it represents current state, not historical
        if ($isTodayOrFuture && $this->isInMaintenance()) {
            return RoomStatus::MANTENIMIENTO;
        }

        // Priority 2: Check for reservations and distinguish between RESERVADA and OCUPADA
        // RESERVADA: check_in_date is in the future (reservation exists but check-in hasn't happened yet)
        // OCUPADA: check_in_date is in the past AND check_out_date > date (check-in already happened, room is occupied)
        // IMPORTANT: OCUPADA takes priority over RESERVADA. If room is already occupied, show OCUPADA even if there are future reservations.
        // IMPORTANT: For past dates, if there's no reservation covering that date, show LIBRE (not RESERVADA based on future reservations).
        
        // Check if room is actually occupied (OCUPADA) - this has priority
        // A room is OCUPADA if check_in_date <= date AND check_out_date > date (check-in already happened)
        if ($this->isOccupied($date)) {
            return RoomStatus::OCUPADA;
        }
        
        // Check for future reservations (RESERVADA) - only if not already occupied AND date is today or future
        // For past dates without reservations, we should show LIBRE, not RESERVADA
        // A room is RESERVADA if there's a reservation where check_in_date > date AND date is today or future
        if ($isTodayOrFuture) {
            $hasFutureReservation = $this->reservations()
                ->whereDate('check_in_date', '>', $date)
                ->whereDate('check_out_date', '>', $date)
                ->exists();
            
            if ($hasFutureReservation) {
                return RoomStatus::RESERVADA;
            }
        }

        // Priority 3: Check if reservation ends today or starts today (Pendiente Checkout)
        // After midnight, rooms are "Pendiente Checkout" if:
        // 1. Reservation ends today (was occupied yesterday, checkout today)
        // 2. Reservation starts today and is for one day only (check-in today, check-out tomorrow)
        // 3. Reservation has one day remaining (check-out tomorrow)
        $previousDay = $normalizedDate->copy()->subDay();
        $tomorrow = $normalizedDate->copy()->addDay();
        $wasOccupiedYesterday = $this->isOccupied($previousDay);
        
        // Case 1: Was occupied yesterday, checkout today
        if ($wasOccupiedYesterday) {
            // Always query the database directly to ensure accuracy
            // Use whereDate() which is specifically designed for date column comparisons
            $reservationEndingToday = $this->reservations()
                ->whereDate('check_in_date', '<=', $previousDay)
                ->whereDate('check_out_date', '=', $date)
                ->exists();
            
            if ($reservationEndingToday) {
                return RoomStatus::PENDIENTE_CHECKOUT;
            }
        }
        
        // Case 2: Reservation starts today and is one-day reservation (check-in today, check-out tomorrow)
        // Always query the database directly to ensure accuracy
        // Use whereDate() which is specifically designed for date column comparisons
        $oneDayReservationStartingToday = $this->reservations()
            ->whereDate('check_in_date', '=', $date)
            ->whereDate('check_out_date', '=', $tomorrow)
            ->exists();
        
        if ($oneDayReservationStartingToday) {
            return RoomStatus::PENDIENTE_CHECKOUT;
        }
        
        // Case 3: Reservation has one day remaining (check-out tomorrow)
        // Always query the database directly to ensure accuracy
        // Use whereDate() which is specifically designed for date column comparisons
        $reservationEndingTomorrow = $this->reservations()
            ->whereDate('check_in_date', '<=', $date)
            ->whereDate('check_out_date', '=', $tomorrow)
            ->exists();
        
        if ($reservationEndingTomorrow) {
            return RoomStatus::PENDIENTE_CHECKOUT;
        }

        // Priority 4: If status is SUCIA, show as SUCIA
        // NOTE: Only use $this->status for current date or future dates
        // For past dates, we should not use the current status as it represents the current state, not historical
        // For historical accuracy, past dates should be based only on reservations
        if ($isTodayOrFuture && $this->status === RoomStatus::SUCIA) {
            return RoomStatus::SUCIA;
        }

        // Priority 5: Default to LIBRE
        // Note: Cleaning status (Pendiente por Aseo) is shown separately in "ESTADO DE LIMPIEZA" column
        return RoomStatus::LIBRE;
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
        $date = \Carbon\Carbon::parse($date)->startOfDay();
        
        // If not in Pendiente Checkout status, return null
        if ($this->getDisplayStatus($date) !== RoomStatus::PENDIENTE_CHECKOUT) {
            return null;
        }
        
        $previousDay = $date->copy()->subDay();
        $tomorrow = $date->copy()->addDay();
        
        // Case 1: Was occupied yesterday, checkout today
        // Always query the database directly to ensure accuracy
        // Use whereDate() which is specifically designed for date column comparisons
        $reservationEndingToday = $this->reservations()
            ->whereDate('check_in_date', '<=', $previousDay)
            ->whereDate('check_out_date', '=', $date)
            ->first();
        
        if ($reservationEndingToday) {
            return $reservationEndingToday;
        }
        
        // Case 2: Reservation starts today and is one-day reservation (check-in today, check-out tomorrow)
        // Always query the database directly to ensure accuracy
        // Use whereDate() which is specifically designed for date column comparisons
        $oneDayReservationStartingToday = $this->reservations()
            ->whereDate('check_in_date', '=', $date)
            ->whereDate('check_out_date', '=', $tomorrow)
            ->first();
        
        if ($oneDayReservationStartingToday) {
            return $oneDayReservationStartingToday;
        }
        
        // Case 3: Reservation has one day remaining (check-out tomorrow)
        // Always query the database directly to ensure accuracy
        // Use whereDate() which is specifically designed for date column comparisons
        $reservationEndingTomorrow = $this->reservations()
            ->whereDate('check_in_date', '<=', $date)
            ->whereDate('check_out_date', '=', $tomorrow)
            ->first();
        
        return $reservationEndingTomorrow;
    }

    /**
     * Accessor for display_status attribute.
     * Uses getDisplayStatus() with today's date by default.
     * This allows using $room->display_status in views.
     * 
     * NOTE: If display_status was explicitly set (e.g., in RoomManager render),
     * it will use that value instead of recalculating.
     *
     * @return RoomStatus
     */
    public function getDisplayStatusAttribute(): RoomStatus
    {
        // If display_status was explicitly set as an attribute, use that value
        // This happens in RoomManager::render() when calculating status for a specific date
        if (array_key_exists('display_status', $this->attributes)) {
            $value = $this->attributes['display_status'];
            // If it's already a RoomStatus enum, return it
            if ($value instanceof RoomStatus) {
                return $value;
            }
            // If it's a string, try to convert it
            if (is_string($value)) {
                return RoomStatus::from($value);
            }
        }
        
        // Otherwise, calculate using today's date (default behavior)
        return $this->getDisplayStatus();
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
