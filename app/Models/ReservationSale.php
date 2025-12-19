<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationSale extends Model
{
    protected $fillable = [
        'reservation_id',
        'product_id',
        'quantity',
        'unit_price',
        'total',
        'payment_method',
        'is_paid',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
