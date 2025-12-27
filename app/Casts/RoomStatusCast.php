<?php

namespace App\Casts;

use App\Enums\RoomStatus;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Backward-compatible cast for Room.status.
 *
 * Some DBs may still contain legacy/English values like "available".
 * Native enum casting (RoomStatus::class) throws ValueError on unknown values,
 * causing 500s in views (e.g. reservations calendar).
 */
class RoomStatusCast implements CastsAttributes
{
    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): RoomStatus
    {
        if ($value instanceof RoomStatus) {
            return $value;
        }

        $raw = is_string($value) ? trim(mb_strtolower($value)) : '';
        $normalized = $this->normalize($raw);

        // Never throw: fallback to LIBRE to prevent 500s.
        return RoomStatus::tryFrom($normalized) ?? RoomStatus::LIBRE;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array<string, mixed>  $attributes
     * @return string|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value instanceof RoomStatus) {
            return $value->value;
        }

        if (!is_string($value)) {
            return null;
        }

        $raw = trim(mb_strtolower($value));
        $normalized = $this->normalize($raw);

        return RoomStatus::tryFrom($normalized)?->value ?? RoomStatus::LIBRE->value;
    }

    private function normalize(string $value): string
    {
        // Map legacy/english statuses to current enum backing values.
        return match ($value) {
            'available', 'free' => RoomStatus::LIBRE->value,
            'reserved', 'booked' => RoomStatus::RESERVADA->value,
            'occupied' => RoomStatus::OCUPADA->value,
            'maintenance' => RoomStatus::MANTENIMIENTO->value,
            'cleaning' => RoomStatus::LIMPIEZA->value,
            'dirty' => RoomStatus::SUCIA->value,
            default => $value,
        };
    }
}


