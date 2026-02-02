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
        $today = Carbon::today();
        
        return Reservation::whereNull('deleted_at')
            ->whereNotExists(function ($query) use ($today) {
                $query->select(DB::raw(1))
                    ->from('stays')
                    ->whereColumn('stays.reservation_id', 'reservations.id')
                    ->whereIn('stays.status', ['active', 'pending_checkout', 'finished']);
            })
            ->count();
    }

    /**
     * Get active reservations (reservas vigentes - planificación válida sin stays activos).
     * NOTA: Incluye reservas futuras y activas, excluye canceladas, terminadas y las que tienen stays activos.
     */
    public function getActiveReservationsProperty(): int
    {
        $today = Carbon::today();
        
        return Reservation::whereNull('deleted_at')
            ->whereHas('reservationRooms', function ($query) {
                $query->whereDate('check_out_date', '>=', now()->toDateString());
            })
            ->whereNotExists(function ($query) use ($today) {
                $query->select(DB::raw(1))
                    ->from('stays')
                    ->whereColumn('stays.reservation_id', 'reservations.id')
                    ->whereIn('stays.status', ['active', 'pending_checkout', 'finished']);
            })
            ->count();
    }

    public function getCancelledReservationsProperty(): int
    {
        return Reservation::onlyTrashed()->count();
    }

    /**
     * Get occupied rooms today (basado en stays activas - operación real).
     * NOTA: Mide ocupación real, no planificación.
     */
    public function getOccupiedRoomsTodayProperty(): int
    {
        return \App\Models\Stay::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('check_out_at')
                      ->orWhere('check_out_at', '>', now());
            })
            ->distinct('room_id')
            ->count('room_id');
    }

    public function getReservationsTodayProperty(): int
    {
        $today = Carbon::today();
        
        return Reservation::join('reservation_rooms', 'reservations.id', '=', 'reservation_rooms.reservation_id')
            ->whereDate('reservation_rooms.check_in_date', $today)
            ->whereNotExists(function ($query) use ($today) {
                $query->select(DB::raw(1))
                    ->from('stays')
                    ->whereColumn('stays.reservation_id', 'reservations.id')
                    ->whereIn('stays.status', ['active', 'pending_checkout', 'finished']);
            })
            ->distinct('reservations.id')
            ->count('reservations.id');
    }

    /**
     * Get total guests today (basado en planificación de reservas sin stays activos).
     * NOTA: Son huéspedes planificados, excluyendo aquellos con stays activos.
     */
    public function getTotalGuestsTodayProperty(): int
    {
        $today = Carbon::today();
        
        return Reservation::join('reservation_rooms', 'reservations.id', '=', 'reservation_rooms.reservation_id')
            ->where('reservation_rooms.check_in_date', '<=', $today)
            ->where('reservation_rooms.check_out_date', '>=', $today)
            ->whereNotExists(function ($query) use ($today) {
                $query->select(DB::raw(1))
                    ->from('stays')
                    ->whereColumn('stays.reservation_id', 'reservations.id')
                    ->whereIn('stays.status', ['active', 'pending_checkout', 'finished']);
            })
            ->sum('reservations.total_guests');
    }

    public function render()
    {
        return view('livewire.reservations.reservation-stats');
    }
}

