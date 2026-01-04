<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomRate extends Model
{

    protected $table = "room_rates";

    protected $fillable = [
        'room_id',
        'min_guests',
        'max_guests',
        'price_per_night',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'min_guests' => 'integer',
        'max_guests' => 'integer',
        'price_per_night' => 'float',
    ];

    /**
     * Get the room that owns the rate.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
