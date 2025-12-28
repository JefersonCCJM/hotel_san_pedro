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
        
        return Reservation::where('check_in_date', '<=', $today)
            ->where('check_out_date', '>=', $today)
            ->count();
    }

    public function getCancelledReservationsProperty(): int
    {
        return Reservation::onlyTrashed()->count();
    }

    public function getOccupiedRoomsTodayProperty(): int
    {
        $today = Carbon::today();
        
        // Get rooms from reservation_rooms pivot table (multi-room reservations)
        $roomsFromPivot = DB::table('reservations')
            ->join('reservation_rooms', 'reservations.id', '=', 'reservation_rooms.reservation_id')
            ->whereNull('reservations.deleted_at')
            ->where('reservations.check_in_date', '<=', $today)
            ->where('reservations.check_out_date', '>=', $today)
            ->distinct('reservation_rooms.room_id')
            ->pluck('reservation_rooms.room_id');
        
        // Get rooms from room_id field (backward compatibility for single-room reservations)
        $roomsFromField = DB::table('reservations')
            ->whereNull('deleted_at')
            ->where('check_in_date', '<=', $today)
            ->where('check_out_date', '>=', $today)
            ->whereNotNull('room_id')
            ->distinct('room_id')
            ->pluck('room_id');
        
        // Merge and count unique rooms
        return $roomsFromPivot->merge($roomsFromField)->unique()->count();
    }

    public function getReservationsTodayProperty(): int
    {
        $today = Carbon::today();
        
        return Reservation::whereDate('check_in_date', $today)
            ->count();
    }

    public function getTotalGuestsTodayProperty(): int
    {
        $today = Carbon::today();
        
        return Reservation::where('check_in_date', '<=', $today)
            ->where('check_out_date', '>=', $today)
            ->sum('guests_count');
    }

    public function render()
    {
        return view('livewire.reservations.reservation-stats');
    }
}

