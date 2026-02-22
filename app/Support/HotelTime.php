<?php

namespace App\Support;

use Carbon\Carbon;

class HotelTime
{
    /**
     * Zona horaria operativa del hotel.
     */
    public static function timezone(): string
    {
        return (string) config('hotel.timezone', config('app.timezone', 'America/Bogota'));
    }

    /**
     * Obtener hora estándar de check-in del hotel
     */
    public static function checkInTime(): string
    {
        return config('hotel.check_in_time', '15:00');
    }

    /**
     * Obtener hora estándar de check-out del hotel
     */
    public static function checkOutTime(): string
    {
        return config('hotel.check_out_time', '12:00');
    }

    /**
     * Obtener hora de inicio del dia operativo.
     * Ejemplo: 06:00 => entre 00:00 y 05:59 se considera dia anterior.
     */
    public static function operationalDayStartTime(): string
    {
        return config('hotel.operational_day_start_time', '06:00');
    }

    /**
     * Obtener hora de check-in temprano
     */
    public static function earlyCheckInTime(): string
    {
        return config('hotel.early_check_in_time', '12:00');
    }

    /**
     * Obtener hora de check-out tardío
     */
    public static function lateCheckOutTime(): string
    {
        return config('hotel.late_check_out_time', '15:00');
    }

    /**
     * Obtener tiempo de limpieza en minutos
     */
    public static function cleaningBufferMinutes(): int
    {
        return config('hotel.cleaning_buffer_minutes', 120);
    }

    /**
     * Obtener período de gracia de check-out en minutos
     */
    public static function checkOutGracePeriodMinutes(): int
    {
        return config('hotel.check_out_grace_period_minutes', 30);
    }

    /**
     * Crear datetime de check-in por defecto para una fecha
     */
    public static function defaultCheckIn(Carbon $date): Carbon
    {
        return Carbon::parse($date->toDateString() . ' ' . self::checkInTime());
    }

    /**
     * Crear datetime de check-out por defecto para una fecha
     */
    public static function defaultCheckOut(Carbon $date): Carbon
    {
        return Carbon::parse($date->toDateString() . ' ' . self::checkOutTime());
    }

    /**
     * Obtener inicio (datetime) del dia operativo para una fecha operativa dada.
     */
    public static function startOfOperationalDay(Carbon $operationalDate): Carbon
    {
        return Carbon::parse(
            $operationalDate->toDateString() . ' ' . self::operationalDayStartTime(),
            self::timezone()
        );
    }

    /**
     * Obtener fin (datetime) del dia operativo para una fecha operativa dada.
     */
    public static function endOfOperationalDay(Carbon $operationalDate): Carbon
    {
        return self::startOfOperationalDay($operationalDate)->copy()->addDay()->subSecond();
    }

    /**
     * Resolver la fecha operativa asociada a un momento puntual.
     */
    public static function operationalDateFor(?Carbon $moment = null): Carbon
    {
        $moment = ($moment ?? Carbon::now(self::timezone()))->copy()->setTimezone(self::timezone());
        $startToday = self::startOfOperationalDay($moment->copy()->startOfDay());

        if ($moment->lt($startToday)) {
            return $moment->copy()->subDay()->startOfDay();
        }

        return $moment->copy()->startOfDay();
    }

    /**
     * Fecha operativa actual del hotel.
     */
    public static function currentOperationalDate(): Carbon
    {
        return self::operationalDateFor(Carbon::now(self::timezone()));
    }

    /**
     * Verifica si una fecha corresponde al "hoy operativo".
     */
    public static function isOperationalToday(Carbon $date, ?Carbon $moment = null): bool
    {
        return $date->copy()->startOfDay()->equalTo(self::operationalDateFor($moment));
    }

    /**
     * Verifica si una fecha es pasada respecto al dia operativo actual.
     */
    public static function isOperationalPastDate(Carbon $date, ?Carbon $moment = null): bool
    {
        return $date->copy()->startOfDay()->lt(self::operationalDateFor($moment));
    }

    /**
     * Verifica si una fecha es futura respecto al dia operativo actual.
     */
    public static function isOperationalFutureDate(Carbon $date, ?Carbon $moment = null): bool
    {
        return $date->copy()->startOfDay()->gt(self::operationalDateFor($moment));
    }

    /**
     * Obtener fin del día operativo (para cálculos de disponibilidad)
     */
    public static function endOfOperatingDay(Carbon $date): Carbon
    {
        return self::endOfOperationalDay($date);
    }

    /**
     * Obtener momento en que una stay termina (con check-out real o fin del día)
     */
    public static function getStayEndsAt(?Carbon $checkOutAt, Carbon $referenceDate = null): Carbon
    {
        $referenceDate = $referenceDate ?? now();
        
        return $checkOutAt 
            ? Carbon::parse($checkOutAt) 
            : self::endOfOperatingDay($referenceDate);
    }

    /**
     * Calcular cuándo estará disponible la habitación después de una stay
     */
    public static function getRoomAvailableAt(?Carbon $checkOutAt, Carbon $referenceDate = null): Carbon
    {
        $stayEndsAt = self::getStayEndsAt($checkOutAt, $referenceDate);
        
        return $stayEndsAt->copy()->addMinutes(self::cleaningBufferMinutes());
    }

    /**
     * Verificar si es check-out tardío
     */
    public static function isLateCheckout(Carbon $checkoutTime): bool
    {
        $lateCheckoutThreshold = Carbon::parse(
            $checkoutTime->toDateString() . ' ' . self::lateCheckOutTime()
        );
        
        return $checkoutTime->gt($lateCheckoutThreshold);
    }

    /**
     * Verificar si es check-in temprano
     */
    public static function isEarlyCheckIn(Carbon $checkinTime): bool
    {
        $earlyCheckinThreshold = Carbon::parse(
            $checkinTime->toDateString() . ' ' . self::earlyCheckInTime()
        );
        
        return $checkinTime->lt($earlyCheckinThreshold);
    }

    /**
     * Verificar si un check-in solicitado intersecta con una stay activa
     */
    public static function checkInIntersectsWithStay(Carbon $requestedCheckIn, ?Carbon $stayCheckOutAt): bool
    {
        $stayEndsAt = self::getStayEndsAt($stayCheckOutAt, $requestedCheckIn);
        
        // Solo intersecta si es el mismo día y antes de que termine la stay
        return $requestedCheckIn->isSameDay($stayEndsAt) && 
               $requestedCheckIn->lte($stayEndsAt);
    }

    /**
     * Verificar si una habitación está disponible para reservas futuras
     * considerando el tiempo de limpieza
     */
    public static function isRoomAvailableForReservation(Carbon $requestedCheckIn, ?Carbon $stayCheckOutAt): bool
    {
        $roomAvailableAt = self::getRoomAvailableAt($stayCheckOutAt, $requestedCheckIn);
        
        return $requestedCheckIn->gt($roomAvailableAt);
    }

    /**
     * Verificar si la hora actual es después de la hora de check-in
     */
    public static function isAfterCheckInTime(): bool
    {
        $now = Carbon::now();
        $checkInDateTime = self::defaultCheckIn($now);
        
        return $now->gte($checkInDateTime);
    }

    /**
     * Verificar si la hora actual es antes de la hora de check-in
     */
    public static function isBeforeCheckInTime(): bool
    {
        return !self::isAfterCheckInTime();
    }

    /**
     * Obtener datetime de check-in para hoy
     */
    public static function checkInDateTimeToday(): Carbon
    {
        return self::defaultCheckIn(Carbon::today());
    }

    /**
     * Verificar si la hora actual es después de la hora de check-out
     * (para permitir reservas para el mismo día)
     */
    public static function isAfterCheckOutTime(): bool
    {
        $now = Carbon::now();
        $checkOutDateTime = self::defaultCheckOut($now);
        
        return $now->gte($checkOutDateTime);
    }

    /**
     * Verificar si la hora actual es antes de la hora de check-out
     */
    public static function isBeforeCheckOutTime(): bool
    {
        return !self::isAfterCheckOutTime();
    }

    /**
     * Obtener datetime de check-out para hoy
     */
    public static function checkOutDateTimeToday(): Carbon
    {
        return self::defaultCheckOut(Carbon::today());
    }

    /**
     * Verificar si se puede reservar para hoy (después de hora de check-out)
     */
    public static function canReserveForToday(): bool
    {
        return self::isAfterCheckOutTime();
    }
}
