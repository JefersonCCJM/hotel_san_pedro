<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'customer_id',
        'room_id',
        'total_amount',
        'deposit',
        'reservation_date',
        'check_in_date',
        'check_out_date',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'deposit' => 'decimal:2',
        'reservation_date' => 'date',
        'check_in_date' => 'date',
        'check_out_date' => 'date',
    ];

    /**
     * Get the customer that owns the reservation.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    /**
     * Get the room that is reserved.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
