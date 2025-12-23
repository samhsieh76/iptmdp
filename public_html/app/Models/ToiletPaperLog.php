<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class ToiletPaperLog extends MyModel {
    use HasFactory;

    protected $fillable = [
        'toilet_paper_sensor_id',
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
            $query->where("toilet_paper_sensor_id", $searchValue->sensor_id);
        }
        return $query;
    }

    public function sensor() {
        return $this->belongsTo(ToiletPaperSensor::class, 'toilet_paper_sensor_id', 'id');
    }
}
