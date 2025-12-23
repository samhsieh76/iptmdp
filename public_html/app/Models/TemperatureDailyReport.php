<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class TemperatureDailyReport extends MyModel
{
    use HasFactory;

    protected $fillable = [
        'date',
        'temperature_sensor_id',
    ];

    public function sensor() {
        return $this->belongsTo(TemperatureSensor::class, 'temperature_sensor_id', 'id');
    }
}
