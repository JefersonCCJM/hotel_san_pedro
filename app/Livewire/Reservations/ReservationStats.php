<?php

namespace App\Livewire\Reservations;

use Livewire\Component;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationStats extends Component
{
    protected $listeners = [
        'reservation-created' => '$refresh',
        'reservation-updated' => '$refresh',
        'reservation-cancelled' => '$refresh',
    ];

    public function getTotalReservationsProperty(): int
    {
        return Reservation::withTrashed()->count();
    }

    public function getActiveReservationsProperty(): int
    {
        $today = Carbon::today();
        
        return Reservation::join('reservation_rooms', 'reservations.id', '=', 'reservation_rooms.reservation_id')
            ->where('reservation_rooms.check_in_date', '<=', $today)
            ->where('reservation_rooms.check_out_date', '>=', $today)
            ->distinct('reservations.id')
            ->count('reservations.id');
    }

    public function getCancelledReservationsProperty(): int
    {
        return Reservation::onlyTrashed()->count();
    }

    public function getOccupiedRoomsTodayProperty(): int
    {
        $today = Carbon::today();
        
        return DB::table('reservations')
            ->join('reservation_rooms', 'reservations.id', '=', 'reservation_rooms.reservation_id')
            ->whereNull('reservations.deleted_at')
            ->where('reservation_rooms.check_in_date', '<=', $today)
            ->where('reservation_rooms.check_out_date', '>=', $today)
            ->distinct('reservation_rooms.room_id')
            ->count('reservation_rooms.room_id');
    }

    public function getReservationsTodayProperty(): int
    {
        $today = Carbon::today();
        
        return Reservation::join('reservation_rooms', 'reservations.id', '=', 'reservation_rooms.reservation_id')
            ->whereDate('reservation_rooms.check_in_date', $today)
            ->distinct('reservations.id')
            ->count('reservations.id');
    }

    public function getTotalGuestsTodayProperty(): int
    {
        $today = Carbon::today();
        
        return Reservation::join('reservation_rooms', 'reservations.id', '=', 'reservation_rooms.reservation_id')
            ->where('reservation_rooms.check_in_date', '<=', $today)
            ->where('reservation_rooms.check_out_date', '>=', $today)
            ->sum('reservations.total_guests');
    }

    public function render()
    {
        return view('livewire.reservations.reservation-stats');
    }
}

