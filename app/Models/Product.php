<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'quantity',
        'low_stock_threshold',
        'price',
        'cost_price',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include products in stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Check if product is in stock.
     */
    public function isInStock()
    {
        return $this->quantity > 0;
    }

    /**
     * Check if product has low stock.
     */
    public function hasLowStock()
    {
        return $this->quantity <= $this->low_stock_threshold && $this->quantity > 0;
    }

    /**
     * Get the movements for the product.
     */
    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function shiftOuts()
    {
        return $this->hasMany(ShiftProductOut::class);
    }

    /**
     * Record a new movement for this product.
     */
    public function recordMovement(int $quantity, string $type, string $reason = null, ?int $roomId = null): InventoryMovement
    {
        $previousStock = $this->quantity;
        
        // El stock ya debería haber sido actualizado en el modelo antes de llamar a este método
        // o este método lo hace. Para consistencia, lo haremos aquí.
        $this->quantity += $quantity;
        $this->save();

        $movement = $this->movements()->create([
            'user_id' => auth()->id() ?? 1, // Fallback a user ID 1 si no hay auth (ej. seeders o consola)
            'room_id' => $roomId,
            'quantity' => $quantity,
            'type' => $type,
            'reason' => $reason,
            'previous_stock' => $previousStock,
            'current_stock' => $this->quantity,
        ]);

        AuditLog::create([
            'user_id' => auth()->id() ?? 1,
            'event' => 'inventory_movement',
            'description' => "Movimiento de inventario ({$type}): {$this->name}. Cantidad: {$quantity}. Motivo: {$reason}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'product_id' => $this->id,
                'movement_id' => $movement->id,
                'type' => $type,
                'quantity' => $quantity
            ]
        ]);

        return $movement;
    }

    /**
     * Get the profit margin.
     */
    public function getProfitMarginAttribute()
    {
        if ($this->cost_price && $this->cost_price > 0) {
            return (($this->price - $this->cost_price) / $this->cost_price) * 100;
        }
        return 0;
    }

}
