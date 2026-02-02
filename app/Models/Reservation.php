<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reservation_code',
        'client_id',
        'status_id',
        'total_guests',
        'adults',
        'children',
        'total_amount',
        'deposit_amount',
        'balance_due',
        'payment_status_id',
        'source_id',
        'created_by',
        'notes',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'reservation_date' => 'date',
        'check_in_date' => 'date',
        'check_out_date' => 'date',
    ];

    /**
     * Get the customer that owns the reservation.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'client_id')->withTrashed();
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
     * NOTE: This relation goes through reservation_rooms since guests are assigned to specific rooms.
     */
    public function guests()
    {
        return $this->hasManyThrough(
            Customer::class,
            \App\Models\ReservationRoom::class,
            'reservation_id', // FK in reservation_rooms
            'id', // FK in customers (guest_id)
            'id', // FK in reservations
            'guest_id' // FK in reservation_rooms
        );
    }

    /**
     * Get the deposit payments for this reservation.
     * @deprecated Use payments() instead
     */
    public function reservationDeposits()
    {
        return $this->hasMany(ReservationDeposit::class);
    }

    /**
     * Get the payments for this reservation.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'reservation_id');
    }

    /**
     * Get the stay nights for this reservation.
     */
    public function stayNights()
    {
        return $this->hasMany(\App\Models\StayNight::class);
    }

    /**
     * Calculate payment status based on actual payments
     */
    public function getPaymentStatusAttribute()
    {
        $totalPaid = $this->payments()->sum('amount');
        $totalAmount = $this->total_amount;
        
        if ($totalPaid >= $totalAmount) {
            return (object) ['code' => 'paid'];
        } elseif ($totalPaid > 0) {
            return (object) ['code' => 'partial'];
        } else {
            return (object) ['code' => 'pending'];
        }
    }

    /**
     * Determine if the reservation is currently occupied (check-in has occurred)
     * 
     * @return bool True if check_in_date <= today AND check_out_date >= today
     */
    public function isOccupied(): bool
    {
        $today = \Carbon\Carbon::today();
        $checkInDate = \Carbon\Carbon::parse($this->check_in_date)->startOfDay();
        $checkOutDate = \Carbon\Carbon::parse($this->check_out_date)->startOfDay();
        
        return $today->gte($checkInDate) && $today->lte($checkOutDate);
    }

    /**
     * Determine if the reservation is a future reservation (check-in hasn't occurred yet)
     * 
     * @return bool True if check_in_date > today
     */
    public function isReserved(): bool
    {
        $today = \Carbon\Carbon::today();
        $checkInDate = \Carbon\Carbon::parse($this->check_in_date)->startOfDay();
        
        return $today->lt($checkInDate);
    }

    /**
     * Determine if checkout is pending for today
     * 
     * @return bool True if check_out_date == today
     */
    public function isPendingCheckout(): bool
    {
        $today = \Carbon\Carbon::today();
        $checkOutDate = \Carbon\Carbon::parse($this->check_out_date)->startOfDay();
        
        return $today->equalTo($checkOutDate);
    }

    /**
     * Determine if checkout has passed
     * 
     * @return bool True if check_out_date < today
     */
    public function isCheckedOut(): bool
    {
        $today = \Carbon\Carbon::today();
        $checkOutDate = \Carbon\Carbon::parse($this->check_out_date)->startOfDay();
        
        return $today->gt($checkOutDate);
    }

    /**
     * Get the status of this reservation for display
     * 
     * @return string One of: 'reserved', 'occupied', 'pending_checkout', 'checked_out'
     */
    public function getStatus(): string
    {
        if ($this->isReserved()) {
            return 'reserved';
        }
        
        if ($this->isPendingCheckout()) {
            return 'pending_checkout';
        }
        
        if ($this->isOccupied()) {
            return 'occupied';
        }
        
        if ($this->isCheckedOut()) {
            return 'checked_out';
        }
        
        return 'unknown';
    }

    /**
     * Query scope to get only occupied reservations (check-in has occurred)
     */
    public function scopeOccupied($query)
    {
        $today = \Carbon\Carbon::today();
        
        return $query
            ->where('check_in_date', '<=', $today)
            ->where('check_out_date', '>=', $today);
    }

    /**
     * Query scope to get only reserved reservations (future check-in)
     */
    public function scopeReserved($query)
    {
        $today = \Carbon\Carbon::today();
        
        return $query->where('check_in_date', '>', $today);
    }

    /**
     * Query scope to get pending checkout reservations (today is checkout date)
     */
    public function scopePendingCheckout($query)
    {
        $today = \Carbon\Carbon::today();
        
        return $query->whereDate('check_out_date', '=', $today);
    }

    /**
     * Query scope to get active reservations (occupied + reserved)
     */
    public function scopeActive($query)
    {
        $today = \Carbon\Carbon::today();
        
        return $query
            ->where('check_in_date', '<=', $today->addDays(30))
            ->where('check_out_date', '>=', $today);
    }
}

