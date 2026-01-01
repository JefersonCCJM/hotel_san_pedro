<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomReleaseHistory extends Model
{
    protected $table = 'room_release_history';

    protected $fillable = [
        'room_id',
        'reservation_id',
        'customer_id',
        'released_by',
        'room_number',
        'total_amount',
        'deposit',
        'consumptions_total',
        'pending_amount',
        'guests_count',
        'check_in_date',
        'check_out_date',
        'release_date',
        'target_status',
        'customer_name',
        'customer_identification',
        'customer_phone',
        'customer_email',
        'reservation_data',
        'sales_data',
        'deposits_data',
        'guests_data',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'deposit' => 'decimal:2',
        'consumptions_total' => 'decimal:2',
        'pending_amount' => 'decimal:2',
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'release_date' => 'date',
        'reservation_data' => 'array',
        'sales_data' => 'array',
        'deposits_data' => 'array',
        'guests_data' => 'array',
    ];

    /**
     * Get the room that was released.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who released the room.
     */
    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}

