<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RoomDailyStatusService
{
    /**
     * 🔥 SERVICIO CRÍTICO: Generar room_daily_statuses_data
     * 
     * Problema: Calendario vacío porque no hay registros diarios
     * Solución: Generar automáticamente para cada reserva
     */
    public function generateForReservation(Reservation $reservation): void
    {
        $reservation->load('reservationRooms.room');
        
        foreach ($reservation->reservationRooms as $reservationRoom) {
            if (!$reservationRoom->check_in_at || !$reservationRoom->check_out_at) {
                continue; // 🔥 Ignorar si no tiene fechas
            }
            
            $this->generateForRoomDates(
                $reservationRoom->room,
                Carbon::parse($reservationRoom->check_in_at),
                Carbon::parse($reservationRoom->check_out_at),
                $reservation
            );
        }
    }
    
    /**
     * Generar estados diarios para una habitación en un rango de fechas
     */
    private function generateForRoomDates(
        Room $room, 
        Carbon $checkIn, 
        Carbon $checkOut, 
        Reservation $reservation
    ): void {
        $current = $checkIn->copy()->startOfDay();
        $endDay = $checkOut->copy()->startOfDay();
        
        while ($current->lte($endDay)) {
            $this->createOrUpdateDailyStatus($room, $current, $reservation);
            $current->addDay();
        }
    }
    
    /**
     * Crear o actualizar estado diario de una habitación
     */
    private function createOrUpdateDailyStatus(
        Room $room, 
        Carbon $date, 
        Reservation $reservation
    ): void {
        // 🔥 Determinar estado según fecha
        $status = $this->determineStatus($date, $reservation);
        
        // 🔥 Usar UPSERT para evitar duplicados
        DB::table('room_daily_statuses_data')->upsert([
            [
                'room_id' => $room->id,
                'date' => $date->toDateString(),
                'status' => $status,
                'reservation_id' => $reservation->id,
                'guest_id' => $reservation->client_id, // 🔥 CORRECCIÓN: usar client_id
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ], 
        ['room_id', 'date'], 
        ['status', 'reservation_id', 'guest_id', 'updated_at']);
    }
    
    /**
     * Determinar estado para una fecha específica
     */
    private function determineStatus(Carbon $date, Reservation $reservation): string
    {
        $checkIn = Carbon::parse($reservation->check_in_at)->startOfDay();
        $checkOut = Carbon::parse($reservation->check_out_at)->startOfDay();
        
        if ($date->equalTo($checkOut)) {
            return 'checkout_day'; // 🔥 Día especial
        } elseif ($date->gte($checkIn) && $date->lt($checkOut)) {
            return 'reserved'; // 🔥 Reserva activa
        }
        
        return 'free'; // 🔥 Libre
    }
    
    /**
     * Regenerar todos los estados para un mes específico
     */
    public function regenerateForMonth(Carbon $month): void
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        
        // 🔥 Limpiar estados existentes del mes
        DB::table('room_daily_statuses_data')
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->delete();
        
        // 🔥 Generar para todas las reservas del mes
        $reservations = Reservation::with(['reservationRooms.room'])
            ->where(function($query) use ($start, $end) {
                $query->whereDate('check_in_at', '<=', $end)
                      ->whereDate('check_out_at', '>=', $start);
            })
            ->get();
        
        foreach ($reservations as $reservation) {
            $this->generateForReservation($reservation);
        }
    }
}
