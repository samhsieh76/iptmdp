<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class RelativeHumidityLog extends MyModel {
    use HasFactory;

    protected $fillable = [
        'relative_humidity_sensor_id',
        'raw_data',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y/m/d H:i:s',
        'updated_at' => 'datetime:Y/m/d H:i:s'
    ];

    public function scopeSearch($query, $searchValue) {
        if ($searchValue->start_date && $searchValue->end_date) {
            $query->whereBetween('created_at', [$searchValue->start_date, $searchValue->end_date]);
        }

        if ($searchValue->sensor_id) {
            $query->where("relative_humidity_sensor_id", $searchValue->sensor_id);
        }
        return $query;
    }

    public function sensor() {
        // 2023-06-16 Change relation due to temperature and relative humidity are same senser module.
        return $this->belongsTo(TemperatureSensor::class, 'relative_humidity_sensor_id', 'id');
    }
}
