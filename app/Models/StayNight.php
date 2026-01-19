<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * StayNight Model
 *
 * Representa una noche cobrable individual de una estadía.
 * Cada fila = 1 noche cobrable con fecha, precio y estado de pago.
 *
 * SINGLE SOURCE OF TRUTH para el cobro por noches:
 * - Cada noche que una habitación está ocupada genera un registro
 * - Tiene fecha, precio y estado de pago
 * - Permite rastrear qué noches específicas están pagadas vs pendientes
 */
class StayNight extends Model
{
    protected $table = 'stay_nights';

    protected $fillable = [
        'stay_id',
        'reservation_id',
        'room_id',
        'date',
        'price',
        'is_paid',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
        'is_paid' => 'boolean',
    ];

    /**
     * Get the stay associated with this night.
     */
    public function stay(): BelongsTo
    {
        return $this->belongsTo(Stay::class);
    }

    /**
     * Get the reservation associated with this night.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Get the room where this night occurred.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Scope: Get only paid nights.
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Scope: Get only unpaid nights.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Scope: Get nights for a specific date.
     */
    public function scopeForDate($query, Carbon $date)
    {
        return $query->whereDate('date', $date->toDateString());
    }
}
