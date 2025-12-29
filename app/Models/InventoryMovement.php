<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'room_id',
        'quantity',
        'type',
        'reason',
        'previous_stock',
        'current_stock',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_stock' => 'integer',
        'current_stock' => 'integer',
    ];

    /**
     * Get the product associated with the movement.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who performed the movement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the room associated with the movement (if any).
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get translated movement type.
     */
    public function getTranslatedTypeAttribute(): string
    {
        return match ($this->type) {
            'input' => 'Entrada',
            'output' => 'Salida',
            'sale' => 'Venta',
            'adjustment' => 'Ajuste',
            'room_consumption' => 'Consumo Hab.',
            default => $this->type,
        };
    }
}

