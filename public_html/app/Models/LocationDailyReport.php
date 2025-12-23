<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationDailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'toilet_id',
        'date',
        'acting_sensor_count',
        'total_sensor_count',
        'acting_percentage',
    ];
}
