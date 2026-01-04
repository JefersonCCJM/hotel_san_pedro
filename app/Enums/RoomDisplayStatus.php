<?php

namespace App\Enums;

/**
 * Room Display Status (UI only - NOT persisted)
 * 
 * These are CALCULATED states based on:
 * - Active stays (from stays table)
 * - Active reservations (from reservations/reservation_rooms)
 * - Maintenance blocks (from room_maintenance_blocks)
 * - Cleaning status (from last_cleaned_at)
 * 
 * DO NOT persist these values in database.
 * DO NOT use in migrations or model attributes.
 */
enum RoomDisplayStatus: string
{
    case LIBRE = 'libre';
    case RESERVADA = 'reservada';
    case OCUPADA = 'ocupada';
    case MANTENIMIENTO = 'mantenimiento';
    case SUCIA = 'sucia';
    case PENDIENTE_CHECKOUT = 'pendiente_checkout';

    public function label(): string
    {
        return match ($this) {
            self::LIBRE => 'Libre',
            self::RESERVADA => 'Reservada',
            self::OCUPADA => 'Ocupada',
            self::MANTENIMIENTO => 'Mantenimiento',
            self::SUCIA => 'Sucia',
            self::PENDIENTE_CHECKOUT => 'Pendiente Checkout',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LIBRE => 'bg-emerald-50 text-emerald-700',
            self::RESERVADA => 'bg-indigo-50 text-indigo-700',
            self::OCUPADA => 'bg-red-50 text-red-700',
            self::MANTENIMIENTO => 'bg-amber-50 text-amber-700',
            self::SUCIA => 'bg-red-50 text-red-700',
            self::PENDIENTE_CHECKOUT => 'bg-orange-50 text-orange-700',
        };
    }

    public function borderColor(): string
    {
        return match ($this) {
            self::LIBRE => 'border-emerald-400',
            self::RESERVADA => 'border-indigo-400',
            self::OCUPADA => 'border-red-400',
            self::MANTENIMIENTO => 'border-amber-400',
            self::SUCIA => 'border-red-400',
            self::PENDIENTE_CHECKOUT => 'border-orange-400',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::LIBRE => 'check-circle',
            self::RESERVADA => 'calendar',
            self::OCUPADA => 'user',
            self::MANTENIMIENTO => 'wrench',
            self::SUCIA => 'exclamation-circle',
            self::PENDIENTE_CHECKOUT => 'clock',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::LIBRE => 'success',
            self::RESERVADA => 'info',
            self::OCUPADA => 'danger',
            self::MANTENIMIENTO => 'warning',
            self::SUCIA => 'danger',
            self::PENDIENTE_CHECKOUT => 'warning',
        };
    }

    public function cardBgColor(): string
    {
        return match ($this) {
            self::LIBRE => 'bg-white hover:bg-emerald-50',
            self::RESERVADA => 'bg-indigo-50 hover:bg-indigo-100',
            self::OCUPADA => 'bg-red-50 hover:bg-red-100',
            self::MANTENIMIENTO => 'bg-amber-50 hover:bg-amber-100',
            self::SUCIA => 'bg-orange-50 hover:bg-orange-100',
            self::PENDIENTE_CHECKOUT => 'bg-yellow-50 hover:bg-yellow-100',
        };
    }
}
