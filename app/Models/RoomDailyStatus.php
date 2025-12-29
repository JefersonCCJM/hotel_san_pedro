<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomDailyStatus extends Model
{
    protected $fillable = [
        'room_id',
        'date',
        'status',
        'cleaning_status',
        'reservation_id',
        'guest_name',
        'total_amount',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => RoomStatus::class,
        'total_amount' => 'float',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class)->withTrashed();
    }
}
