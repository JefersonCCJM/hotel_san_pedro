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
            self::RESERVADA => 'bg-indigo-50 text-indigo-700',
            self::OCUPADA => 'bg-red-50 text-red-700',
            self::MANTENIMIENTO => 'bg-amber-50 text-amber-700',
            self::LIMPIEZA => 'bg-orange-50 text-orange-700',
            self::SUCIA => 'bg-red-50 text-red-700',
            self::PENDIENTE_CHECKOUT => 'bg-orange-50 text-orange-700',
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
            self::RESERVADA => 'border-indigo-400',
            self::OCUPADA => 'border-red-400',
            self::MANTENIMIENTO => 'border-amber-400',
            self::LIMPIEZA => 'border-orange-500',
            self::SUCIA => 'border-red-500',
            self::PENDIENTE_CHECKOUT => 'border-orange-400',
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
            self::RESERVADA => 'bg-indigo-100 border-indigo-400 text-indigo-800',
            self::OCUPADA => 'bg-red-100 border-red-400 text-red-800',
            self::MANTENIMIENTO => 'bg-amber-100 border-amber-400 text-amber-800',
            self::LIMPIEZA => 'bg-orange-100 border-orange-500 text-orange-800',
            self::SUCIA => 'bg-red-100 border-red-500 text-red-800',
            self::PENDIENTE_CHECKOUT => 'bg-orange-100 border-orange-400 text-orange-800',
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

    /**
     * Get card background color class for UI display
     */
    public function cardBgColor(): string
    {
        return match ($this) {
            self::LIBRE => 'bg-emerald-50 hover:bg-emerald-100',
            self::RESERVADA => 'bg-blue-50 hover:bg-blue-100',
            self::OCUPADA => 'bg-blue-50 hover:bg-blue-100',
            self::MANTENIMIENTO => 'bg-amber-50 hover:bg-amber-100',
            self::LIMPIEZA => 'bg-orange-50 hover:bg-orange-100',
            self::SUCIA => 'bg-red-50 hover:bg-red-100',
            self::PENDIENTE_CHECKOUT => 'bg-purple-50 hover:bg-purple-100',
            self::PENDIENTE_ASEO => 'bg-yellow-50 hover:bg-yellow-100',
        };
    }

    /**
     * Get clean button color class for UI display
     */
    public function cleanButtonColor(): string
    {
        return match ($this) {
            self::LIBRE => 'bg-emerald-100 border-emerald-300 text-emerald-800',
            self::RESERVADA => 'bg-blue-100 border-blue-300 text-blue-800',
            self::OCUPADA => 'bg-blue-100 border-blue-300 text-blue-800',
            self::MANTENIMIENTO => 'bg-amber-100 border-amber-300 text-amber-800',
            self::LIMPIEZA => 'bg-orange-100 border-orange-300 text-orange-800',
            self::SUCIA => 'bg-red-100 border-red-300 text-red-800',
            self::PENDIENTE_CHECKOUT => 'bg-purple-100 border-purple-300 text-purple-800',
            self::PENDIENTE_ASEO => 'bg-yellow-100 border-yellow-300 text-yellow-800',
        };
    }
}

