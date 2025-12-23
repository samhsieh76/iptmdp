<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class HandLotionDailyReport extends MyModel
{
    use HasFactory;

    protected $fillable = [
        'date',
        'hand_lotion_sensor_id',
    ];

    public function sensor() {
        return $this->belongsTo(HandLotionSensor::class, 'hand_lotion_sensor_id', 'id');
    }
}
