<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomMaintenanceBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'start_at',
        'end_at',
        'reason',
        'status_id',
        'source_id',
        'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function status()
    {
        return $this->belongsTo(RoomMaintenanceBlockStatus::class, 'status_id');
    }

    public function source()
    {
        return $this->belongsTo(RoomMaintenanceBlockSource::class, 'source_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
