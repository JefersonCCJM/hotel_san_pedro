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
    case PENDIENTE_ASEO = 'pendiente_aseo';

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
            self::PENDIENTE_ASEO => 'Pendiente por Aseo',
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
            self::PENDIENTE_ASEO => 'bg-yellow-50 text-yellow-700',
        };
    }

    /**
     * Get border color class for UI display
     */
    public function borderColor(): string
    {
        return match ($this) {
            self::LIBRE => 'border-emerald-400',
            self::RESERVADA => 'border-blue-400',
            self::OCUPADA => 'border-blue-400',
            self::MANTENIMIENTO => 'border-amber-400',
            self::LIMPIEZA => 'border-orange-500',
            self::SUCIA => 'border-red-500',
            self::PENDIENTE_CHECKOUT => 'border-purple-400',
            self::PENDIENTE_ASEO => 'border-yellow-400',
        };
    }

    /**
     * Get badge color class for UI display
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::LIBRE => 'bg-emerald-100 border-emerald-400 text-emerald-800',
            self::RESERVADA => 'bg-blue-100 border-blue-400 text-blue-800',
            self::OCUPADA => 'bg-blue-100 border-blue-400 text-blue-800',
            self::MANTENIMIENTO => 'bg-amber-100 border-amber-400 text-amber-800',
            self::LIMPIEZA => 'bg-orange-100 border-orange-500 text-orange-800',
            self::SUCIA => 'bg-red-100 border-red-500 text-red-800',
            self::PENDIENTE_CHECKOUT => 'bg-purple-100 border-purple-400 text-purple-800',
            self::PENDIENTE_ASEO => 'bg-yellow-100 border-yellow-400 text-yellow-800',
        };
    }

    /**
     * Get icon class for UI display
     */
    public function icon(): string
    {
        return match ($this) {
            self::LIBRE => 'fa-check-circle',
            self::RESERVADA => 'fa-calendar-check',
            self::OCUPADA => 'fa-user',
            self::MANTENIMIENTO => 'fa-tools',
            self::LIMPIEZA => 'fa-broom',
            self::SUCIA => 'fa-broom',
            self::PENDIENTE_CHECKOUT => 'fa-clock',
            self::PENDIENTE_ASEO => 'fa-broom',
        };
    }
}

