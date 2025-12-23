<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class HandLotionRefillLog extends MyModel
{
    use HasFactory;

    protected $fillable = [
        'hand_lotion_sensor_id',
        'created_at',
        'updated_at',
    ];
}
