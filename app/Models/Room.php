<?php

namespace App\Models;

use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_number',
        'beds_count',
        'max_capacity',
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
        'status' => RoomStatus::class,
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
     * @param \Carbon\Carbon|null $date Date to check. Defaults to today.
     * @return bool True if room has an active reservation on the given date.
     */
    public function isOccupied(?\Carbon\Carbon $date = null): bool
    {
        $date = $date ?? \Carbon\Carbon::today();

        // Room is occupied if: check_in <= date AND check_out > date
        // If checkout is today, room is NOT occupied (guest leaves today)
        return $this->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
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

        return $this->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
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
     * Derived from last_cleaned_at, NOT from reservations or occupancy.
     * 
     * Rules:
     * - If never cleaned (last_cleaned_at is NULL) → needs cleaning
     * - If last cleaning was 24+ hours ago → needs cleaning (only applies when last_cleaned_at exists)
     * - If less than 24 hours since last cleaning → clean
     *
     * @return array{code: string, label: string, color: string, icon: string}
     */
    public function cleaningStatus(): array
    {
        // If never cleaned, needs cleaning immediately
        if (!$this->last_cleaned_at) {
            return [
                'code' => 'pendiente',
                'label' => 'Pendiente por Aseo',
                'color' => 'bg-yellow-100 text-yellow-800',
                'icon' => 'fa-broom',
            ];
        }

        // If last cleaning was 24+ hours ago, needs cleaning
        // This rule ONLY applies when last_cleaned_at exists (room was cleaned before)
        if ($this->last_cleaned_at->diffInHours(now()) >= 24) {
            return [
                'code' => 'pendiente',
                'label' => 'Pendiente por Aseo',
                'color' => 'bg-yellow-100 text-yellow-800',
                'icon' => 'fa-broom',
            ];
        }

        // Clean (less than 24 hours since last cleaning)
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

        // Priority 1: Maintenance blocks everything
        if ($this->isInMaintenance()) {
            return RoomStatus::MANTENIMIENTO;
        }

        // Priority 2: Active reservation means occupied
        if ($this->isOccupied($date)) {
            return RoomStatus::OCUPADA;
        }

        // Priority 3: Check if reservation ends today (Pendiente Checkout)
        $reservationEndingToday = $this->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '=', $date->toDateString())
            ->exists();
        
        if ($reservationEndingToday) {
            return RoomStatus::PENDIENTE_CHECKOUT;
        }

        // Priority 4: If status is SUCIA, show as SUCIA
        if ($this->status === RoomStatus::SUCIA) {
            return RoomStatus::SUCIA;
        }

        // Priority 5: Default to LIBRE
        // Note: Cleaning status (Pendiente por Aseo) is shown separately in "ESTADO DE LIMPIEZA" column
        return RoomStatus::LIBRE;
    }

    /**
     * Accessor for display_status attribute.
     * Uses getDisplayStatus() with today's date by default.
     * This allows using $room->display_status in views.
     *
     * @return RoomStatus
     */
    public function getDisplayStatusAttribute(): RoomStatus
    {
        return $this->getDisplayStatus();
    }
}
