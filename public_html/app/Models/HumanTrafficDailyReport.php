<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class HumanTrafficDailyReport extends MyModel
{
    use HasFactory;

    protected $fillable = [
        'date',
        'human_traffic_sensor_id',
    ];

    public function sensor() {
        return $this->belongsTo(HumanTrafficSensor::class, 'human_traffic_sensor_id', 'id');
    }
}
