<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    protected $table = "room_types";

    protected $fillable = ["id", "code", "name", "created_at", "updated_at"];
}
