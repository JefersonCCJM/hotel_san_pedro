<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Stay;
use App\Enums\RoomDisplayStatus;
use Carbon\Carbon;

/**
 * RoomAvailabilityService
 * 
 * Determina el estado de una habitación en una fecha específica.
 * Implementa la regla de negocio: una ocupación es un INTERVALO DE TIEMPO.
 * 
 * Una habitación estuvo OCUPADA en una fecha X SÍ Y SOLO SÍ:
 * - check_in_date < end_of_day(X)  [ocupación comenzó antes de que termine el día X]
 * - Y (check_out_date IS NULL OR check_out_date > start_of_day(X))  [aún no ha salido el día X]
 * 
 * Responsabilidades:
 * - Calcular correctamente la intersección entre intervalos de tiempo
 * - Respetar que días pasados son históricos (solo lectura)
 * - No permitir modificaciones en días pasados
 * - Retornar un estado claro y bloquear acciones operativas si es necesario
 */
class RoomAvailabilityService
{
    private Room $room;

    public function __construct(Room $room)
    {
        $this->room = $room;
    }

    /**
     * Determina si una habitación estuvo ocupada en una fecha específica.
     * 
     * DELEGADO A: getStayForDate() - single source of truth
     * 
     * @param Carbon|null $date Fecha a consultar. Por defecto, hoy.
     * @return bool True si la habitación estuvo ocupada en esa fecha.
     */
    public function isOccupiedOn(?Carbon $date = null): bool
    {
        return $this->getStayForDate($date) !== null;
    }

    /**
     * SINGLE SOURCE OF TRUTH: Obtiene el stay que intersecta una fecha específica.
     * 
     * Implementa la regla de negocio correcta:
     * Un stay intersecta con una fecha D si y solo si:
     * - check_in_at < endOfDay(D)  [el stay comenzó antes de que termine el día D]
     * - Y check_out_at >= startOfDay(D)  [el stay no terminó antes del día D]
     * 
     * CRÍTICO: 
     * - NO filtra por status='active' porque para fechas históricas necesitamos stays finished
     * - Si check_out_at IS NULL, usa la check_out_date de reservation_rooms como fallback
     * - El estado del stay es independiente de si intersecta una fecha
     * 
     * @param Carbon|null $date Fecha a consultar. Por defecto, hoy.
     * @return \App\Models\Stay|null El stay que ocupa la habitación en esa fecha, o null
     */
    public function getStayForDate(?Carbon $date = null): ?\App\Models\Stay
    {
        $date = $date ?? Carbon::today();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // CRITICAL: Usar nueva query directamente, NO la relación en memoria
        // Esto evita problemas de caché cuando se crea una nueva stay
        $stayQuery = \App\Models\Stay::query()
            ->where('room_id', $this->room->id)
            ->with([
                'reservation.customer',
                'reservation.reservationRooms' => function ($query) {
                    $query->where('room_id', $this->room->id);
                }
            ])
            ->where('check_in_at', '<=', $endOfDay);

        // CRÍTICO: Debe haber un check_out que sea >= startOfDay
        // Si check_out_at IS NULL, usamos la fecha de checkout de reservation_rooms
        // IMPORTANTE: Para fechas futuras, solo retornar stay si la fecha está ANTES del checkout
        $isFutureDate = $date->isFuture();
        
        $stayQuery->where(function ($query) use ($startOfDay, $endOfDay, $isFutureDate) {
            $query->where(function ($q) use ($startOfDay) {
                // Caso 1: check_out_at IS NOT NULL y es >= startOfDay
                // Esto significa que el checkout ocurrió en o después de esta fecha
                $q->whereNotNull('check_out_at')
                  ->where('check_out_at', '>=', $startOfDay);
            })
            ->orWhere(function ($q) use ($startOfDay, $endOfDay, $isFutureDate) {
                // Caso 2: check_out_at IS NULL, pero reservation_rooms.check_out_date debe ser >= startOfDay
                $q->whereNull('check_out_at')
                  ->whereHas('reservation', function ($r) use ($startOfDay, $endOfDay, $isFutureDate) {
                      $r->whereHas('reservationRooms', function ($rr) use ($startOfDay, $endOfDay, $isFutureDate) {
                          $rr->where('room_id', $this->room->id)
                             ->where('check_out_date', '>=', $startOfDay->toDateString());
                          
                          // CRITICAL: Para fechas futuras, solo retornar si la fecha consultada está DENTRO del rango
                          // Regla: check_out_date >= endOfDay (la fecha consultada es <= día del checkout)
                          // Esto asegura que:
                          // - Si la fecha consultada es ANTES del checkout: check_out_date > endOfDay → retorna stay
                          // - Si la fecha consultada ES el día del checkout: check_out_date = endOfDay → retorna stay
                          // - Si la fecha consultada es DESPUÉS del checkout: check_out_date < endOfDay → NO retorna stay
                          if ($isFutureDate) {
                              $rr->where('check_out_date', '>=', $endOfDay->toDateString());
                          }
                      });
                  });
            });
        });

        return $stayQuery
            ->orderBy('check_in_at', 'desc') // El más reciente primero
            ->first();
    }

    /**
     * Determina si una fecha es histórica (pasada).
     * 
     * Una fecha es histórica si es anterior a hoy.
     * El sistema NO debe permitir modificaciones en fechas históricas.
     * 
     * @param Carbon|null $date Fecha a consultar. Por defecto, ahora.
     * @return bool True si la fecha es histórica.
     */
    public function isHistoricDate(?Carbon $date = null): bool
    {
        $date = $date ?? now();
        return $date->copy()->startOfDay()->lt(Carbon::today());
    }

    /**
     * Determina si las modificaciones operativas (check-in, checkout, cambios de estado)
     * están permitidas para una fecha específica.
     * 
     * Regla: Solo hoy y fechas futuras permiten modificaciones.
     * Las fechas históricas son solo lectura.
     * 
     * @param Carbon|null $date Fecha a consultar. Por defecto, ahora.
     * @return bool True si se permiten modificaciones.
     */
    public function canModifyOn(?Carbon $date = null): bool
    {
        return !$this->isHistoricDate($date);
    }

    /**
     * Obtiene el estado de checkout pendiente para una fecha.
     * 
     * Una habitación está en PENDIENTE_CHECKOUT si:
     * - Estuvo ocupada ayer
     * - La ocupación termina hoy (check_out_at = hoy)
     * 
     * @param Carbon|null $date Fecha a consultar. Por defecto, hoy.
     * @return bool
     */
    public function hasPendingCheckoutOn(?Carbon $date = null): bool
    {
        $date = $date ?? Carbon::today();
        $previousDay = $date->copy()->subDay();

        // Verificar si había ocupación el día anterior
        $stayYesterday = $this->getStayForDate($previousDay);
        
        if (!$stayYesterday) {
            return false;
        }

        // Verificar si ese stay terminó hoy (check_out_at dentro de hoy)
        if ($stayYesterday->check_out_at) {
            return $stayYesterday->check_out_at->isSameDay($date);
        }

        return false;
    }

    /**
     * Obtiene el estado de limpieza de la habitación para una fecha específica.
     * 
     * Utiliza el método existente cleaningStatus() del modelo Room.
     * 
     * @param Carbon|null $date Fecha a consultar. Por defecto, hoy.
     * @return array{code: string, label: string, color: string, icon: string}
     */
    public function getCleaningStatusOn(?Carbon $date = null): array
    {
        return $this->room->cleaningStatus($date);
    }

    /**
     * Determina el estado de display de la habitación para una fecha específica.
     * 
     * Implementa la prioridad de estados:
     * 1. MANTENIMIENTO (bloquea todo)
     * 2. OCUPADA (hay stay activa)
     * 3. PENDIENTE_CHECKOUT (checkout hoy, después de ocupación ayer)
     * 4. SUCIA (needs cleaning)
     * 5. RESERVADA (reserva futura)
     * 6. LIBRE (default)
     * 
     * @param Carbon|null $date Fecha a consultar. Por defecto, hoy.
     * @return RoomDisplayStatus
     */
    public function getDisplayStatusOn(?Carbon $date = null): RoomDisplayStatus
    {
        $date = $date ?? Carbon::today();

        // Priority 1: Maintenance blocks everything
        if ($this->room->isInMaintenance()) {
            return RoomDisplayStatus::MANTENIMIENTO;
        }

        // Priority 2: Active stay = occupied
        if ($this->isOccupiedOn($date)) {
            return RoomDisplayStatus::OCUPADA;
        }

        // Priority 3: Pending checkout
        if ($this->hasPendingCheckoutOn($date)) {
            return RoomDisplayStatus::PENDIENTE_CHECKOUT;
        }

        // Priority 4: Needs cleaning
        $cleaningStatus = $this->getCleaningStatusOn($date);
        if ($cleaningStatus['code'] === 'pendiente') {
            return RoomDisplayStatus::SUCIA;
        }

        // Priority 5: Future reservation
        $hasFutureReservation = $this->room->reservationRooms()
            ->where('check_in_date', '>', $date->copy()->endOfDay()->toDateString())
            ->exists();

        if ($hasFutureReservation) {
            return RoomDisplayStatus::RESERVADA;
        }

        // Priority 6: Free (default)
        return RoomDisplayStatus::LIBRE;
    }

    /**
     * Obtiene un array con información de acceso para una fecha.
     * 
     * Útil para que el controller/Livewire sepa si puede permitir acciones.
     * 
     * @param Carbon|null $date Fecha a consultar. Por defecto, hoy.
     * @return array{isHistoric: bool, canModify: bool, status: RoomDisplayStatus, reason: string}
     */
    public function getAccessInfo(?Carbon $date = null): array
    {
        $date = $date ?? now();
        $isHistoric = $this->isHistoricDate($date);
        $canModify = $this->canModifyOn($date);
        $status = $this->getDisplayStatusOn($date);

        $reason = 'OK';
        if ($isHistoric) {
            $reason = 'Fecha histórica: datos en solo lectura.';
        } elseif ($status === RoomDisplayStatus::MANTENIMIENTO) {
            $reason = 'Habitación en mantenimiento.';
        }

        return [
            'isHistoric' => $isHistoric,
            'canModify' => $canModify,
            'status' => $status,
            'reason' => $reason,
        ];
    }
}
