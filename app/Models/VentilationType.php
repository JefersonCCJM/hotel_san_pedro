<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentilationType extends Model
{
    protected $table = "ventilation_types";

    protected $fillable = ["id", "code", "name", "created_at", "updated_at"];
}
