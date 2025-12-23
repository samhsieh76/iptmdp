<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class SmellyDailyReport extends MyModel
{
    use HasFactory;

    protected $fillable = [
        'date',
        'smelly_sensor_id',
    ];

    public function sensor() {
        return $this->belongsTo(SmellySensor::class, 'smelly_sensor_id', 'id');
    }
}
