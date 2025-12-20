<?php

namespace App\Enums;

enum RoomStatus: string
{
    case LIBRE = 'libre';
    case RESERVADA = 'reservada';
    case OCUPADA = 'ocupada';
    case MANTENIMIENTO = 'mantenimiento';
    case LIMPIEZA = 'limpieza';
    case SUCIA = 'sucia';
    case PENDIENTE_CHECKOUT = 'pendiente_checkout';

    public function label(): string
    {
        return match ($this) {
            self::LIBRE => 'Libre',
            self::RESERVADA => 'Reservada',
            self::OCUPADA => 'Ocupada',
            self::MANTENIMIENTO => 'Mantenimiento',
            self::LIMPIEZA => 'Limpieza',
            self::SUCIA => 'Sucia',
            self::PENDIENTE_CHECKOUT => 'Pendiente Checkout',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LIBRE => 'bg-emerald-50 text-emerald-700',
            self::RESERVADA => 'bg-blue-50 text-blue-700',
            self::OCUPADA => 'bg-blue-50 text-blue-700',
            self::MANTENIMIENTO => 'bg-amber-50 text-amber-700',
            self::LIMPIEZA => 'bg-orange-50 text-orange-700',
            self::SUCIA => 'bg-red-50 text-red-700',
            self::PENDIENTE_CHECKOUT => 'bg-purple-50 text-purple-700',
        };
    }
}

