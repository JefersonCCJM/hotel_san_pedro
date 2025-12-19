<?php

namespace App\Enums;

enum RoomStatus: string
{
    case LIBRE = 'libre';
    case RESERVADA = 'reservada';
    case OCUPADA = 'ocupada';
    case MANTENIMIENTO = 'mantenimiento';
    case LIMPIEZA = 'limpieza';

    public function label(): string
    {
        return match ($this) {
            self::LIBRE => 'Libre',
            self::RESERVADA => 'Reservada',
            self::OCUPADA => 'Ocupada',
            self::MANTENIMIENTO => 'Mantenimiento',
            self::LIMPIEZA => 'Limpieza',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LIBRE => 'bg-emerald-50 text-emerald-700',
            self::RESERVADA => 'bg-blue-50 text-blue-700',
            self::OCUPADA => 'bg-red-50 text-red-700',
            self::MANTENIMIENTO => 'bg-amber-50 text-amber-700',
            self::LIMPIEZA => 'bg-orange-50 text-orange-700',
        };
    }
}

