<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'room_id',
        'guests_count',
        'total_amount',
        'deposit',
        'payment_method',
        'reservation_date',
        'check_in_date',
        'check_out_date',
        'check_in_time',
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
     * Maintained for backward compatibility with single room reservations.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get all rooms assigned to this reservation.
     */
    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'reservation_rooms')
                    ->withTimestamps();
    }

    /**
     * Get the reservation room pivot entries.
     */
    public function reservationRooms()
    {
        return $this->hasMany(ReservationRoom::class);
    }

    /**
     * Get the sales/consumptions for this reservation.
     */
    public function sales()
    {
        return $this->hasMany(ReservationSale::class);
    }

    /**
     * Get the guests assigned to this reservation.
     * Maintained for backward compatibility.
     */
    public function guests()
    {
        return $this->belongsToMany(Customer::class, 'reservation_guests')
                    ->withTimestamps()
                    ->withTrashed();
    }
}
