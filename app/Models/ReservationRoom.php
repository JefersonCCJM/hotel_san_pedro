<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationRoom extends Model
{

    protected $table = "reservation_rooms";

    protected $fillable = [
        'reservation_id',
        'room_id',
        'check_in_date',
        'check_out_date',
        'check_in_time',
        'check_out_time',
        'nights',
        'price_per_night',
        'subtotal',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the reservation that owns this room assignment.
     */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Get the room assigned to this reservation.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get guests as a collection via the two-table join:
     * reservation_room_guests → reservation_guests → customers
     */
    public function getGuests()
    {
        try {
            $customerIds = \DB::table('reservation_room_guests as rrg')
                ->join('reservation_guests as rg', 'rrg.reservation_guest_id', '=', 'rg.id')
                ->where('rrg.reservation_room_id', $this->id)
                ->pluck('rg.customer_id');

            if ($customerIds->isEmpty()) {
                return collect();
            }

            return Customer::withTrashed()
                ->with('taxProfile')
                ->whereIn('id', $customerIds)
                ->get();
        } catch (\Exception $e) {
            \Log::warning('Error loading guests for ReservationRoom', [
                'reservation_room_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }
}


