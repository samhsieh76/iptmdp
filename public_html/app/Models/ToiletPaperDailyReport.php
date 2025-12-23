<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class ToiletPaperDailyReport extends MyModel
{
    use HasFactory;

    protected $fillable = [
        'date',
        'toilet_paper_sensor_id',
    ];

    public function sensor() {
        return $this->belongsTo(ToiletPaperSensor::class, 'toilet_paper_sensor_id', 'id');
    }
}
