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
}
