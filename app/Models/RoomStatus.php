<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomStatus extends Model
{
    protected $fillable = [
        'name',
        'code',
        'color',
        'icon',
        'is_visible_public',
        'is_actionable',
        'next_status_id',
        'description',
        'order',
    ];

    protected $casts = [
        'is_visible_public' => 'boolean',
        'is_actionable' => 'boolean',
        'order' => 'integer',
    ];

    public function nextStatus(): BelongsTo
    {
        return $this->belongsTo(RoomStatus::class, 'next_status_id');
    }

    public function previousStatuses(): HasMany
    {
        return $this->hasMany(RoomStatus::class, 'next_status_id');
    }

    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }
}
