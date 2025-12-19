<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomRate extends Model
{
    protected $fillable = [
        'room_id',
        'start_date',
        'end_date',
        'occupancy_prices',
        'event_name',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'occupancy_prices' => 'array',
    ];

    /**
     * Get the room that owns the rate.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
