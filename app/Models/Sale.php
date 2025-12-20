<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Sale extends Model
{
    protected $fillable = [
        'user_id',
        'room_id',
        'shift',
        'payment_method',
        'cash_amount',
        'transfer_amount',
        'debt_status',
        'sale_date',
        'total',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'total' => 'decimal:2',
        'cash_amount' => 'decimal:2',
        'transfer_amount' => 'decimal:2',
    ];

    /**
     * Get the receptionist (user) who made the sale.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the room associated with the sale (if any).
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the sale items (products).
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Scope a query to filter by date.
     */
    public function scopeByDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('sale_date', $date);
    }

    /**
     * Scope a query to filter by receptionist.
     */
    public function scopeByReceptionist(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by shift.
     */
    public function scopeByShift(Builder $query, string $shift): Builder
    {
        return $query->where('shift', $shift);
    }

    /**
     * Scope a query to filter by debt status.
     */
    public function scopeWithDebt(Builder $query): Builder
    {
        return $query->where('debt_status', 'pendiente');
    }

    /**
     * Scope a query to filter by payment method.
     */
    public function scopeByPaymentMethod(Builder $query, string $method): Builder
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Check if sale has debt.
     */
    public function hasDebt(): bool
    {
        return $this->debt_status === 'pendiente';
    }

    /**
     * Check if sale is paid.
     */
    public function isPaid(): bool
    {
        return $this->debt_status === 'pagado';
    }
}
