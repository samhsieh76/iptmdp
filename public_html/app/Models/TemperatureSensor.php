<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class TemperatureSensor extends MyModel {
    use HasFactory;

    protected $fillable = [
        'toilet_id'
    ];

    /**
     * search keyword
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param object $searchValue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $searchValue) {
        if (!empty($searchValue->toilet_id)) {
            $query->where("{$this->getTable()}.toilet_id", '=', $searchValue->toilet_id);
        }
        return $query;
    }

    public function toilet() {
        return $this->belongsTo(Toilet::class);
    }

    public function logs() {
        return $this->hasMany(TemperatureLog::class, 'temperature_sensor_id', 'id');
    }

    public function dailyReports() {
        return $this->hasMany(TemperatureDailyReport::class, 'temperature_sensor_id', 'id');
    }

    public function relativeHumiditylogs() {
        return $this->hasMany(RelativeHumidityLog::class, 'relative_humidity_sensor_id', 'id');
    }

    public function relativeHumidityDailyReports() {
        return $this->hasMany(RelativeHumidityDailyReport::class, 'relative_humidity_sensor_id', 'id');
    }

    public function scopeJoinTempReportByDate($query, $date) {
        return $query->join('temperature_daily_reports AS report', function ($join) use($date) {
            $join->on('temperature_sensors.id', '=', 'report.temperature_sensor_id')->where('report.date', '=', $date);
        });
    }

    public function scopeJoinHumidityReportByDate($query, $date) {
        return $query->join('relative_humidity_daily_reports AS report', function ($join) use($date) {
            $join->on('temperature_sensors.id', '=', 'report.relative_humidity_sensor_id')->where('report.date', '=', $date);
        });
    }
}
