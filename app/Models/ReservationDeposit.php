<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationDeposit extends Model
{
    protected $fillable = [
        'reservation_id',
        'amount',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the reservation that owns this deposit.
     */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}

