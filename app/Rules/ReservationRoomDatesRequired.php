<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ReservationRoomDatesRequired implements ValidationRule
{
    /**
     * 🔥 REGLA CRÍTICA: reservation_rooms SIEMPRE debe tener fechas
     * 
     * Problema: Se crean reservation_rooms sin fechas (NULL)
     * Impacto: Calendario vacío, disponibilidad rota
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $reservationRoom = $value;
        
        // 🔥 Validaciones OBLIGATORIAS
        if (!$reservationRoom['check_in_at'] ?? null) {
            $fail('La fecha de check-in es obligatoria.');
            return;
        }
        
        if (!$reservationRoom['check_out_at'] ?? null) {
            $fail('La fecha de check-out es obligatoria.');
            return;
        }
        
        // 🔥 Validar que check_out > check_in
        $checkIn = \Carbon\Carbon::parse($reservationRoom['check_in_at']);
        $checkOut = \Carbon\Carbon::parse($reservationRoom['check_out_at']);
        
        if ($checkOut->lte($checkIn)) {
            $fail('La fecha de check-out debe ser posterior a la de check-in.');
            return;
        }
        
        // 🔥 Validar que no sea en el pasado (para nuevas reservas)
        if ($checkIn->lt(now()->startOfDay())) {
            $fail('La fecha de check-in no puede ser en el pasado.');
        }
    }
}
