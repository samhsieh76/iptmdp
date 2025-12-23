<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class RelativeHumidityDailyReport extends MyModel
{
    use HasFactory;

    protected $fillable = [
        'date',
        'relative_humidity_sensor_id',
    ];

    public function sensor() {
        return $this->belongsTo(TemperatureSensor::class, 'relative_humidity_sensor_id', 'id');
    }
}
