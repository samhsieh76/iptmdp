<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class SmellySensor extends MyModel {
    use HasFactory;

    protected $fillable = [
        'toilet_id',
        'name',
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

    public function scopeLatestReport($query) {
        return $query->leftJoin('smelly_daily_reports AS latest_report', function ($join) {
            $join->on('smelly_sensors.id', '=', 'latest_report.smelly_sensor_id')
                ->where('latest_report.id', '=', function ($query) {
                    $query->select('id')
                        ->from('smelly_daily_reports')
                        ->whereColumn('smelly_sensor_id', 'smelly_sensors.id')
                        ->latest()
                        ->limit(1);
                });
        });
    }

    public function scopeJoinReportByDate($query, $date) {
        return $query->join('smelly_daily_reports AS report', function ($join) use ($date) {
            $join->on('smelly_sensors.id', '=', 'report.smelly_sensor_id')->where('report.date', '=', $date);
        });
    }

    public function scopeJoinReportByDateRange($query, $start_date, $end_date) {
        return $query->join('smelly_daily_reports AS report', function ($join) use ($start_date, $end_date) {
            $join->on('smelly_sensors.id', '=', 'report.smelly_sensor_id')
                ->where('report.date', '>=', $start_date)
                ->where('report.date', '<=', $end_date);
        });
    }

    public function toilet() {
        return $this->belongsTo(Toilet::class);
    }

    public function logs() {
        return $this->hasMany(SmellyLog::class, 'smelly_sensor_id', 'id');
    }

    public function dailyReports() {
        return $this->hasMany(SmellyDailyReport::class, 'smelly_sensor_id', 'id');
    }

    public function abnormals() {
        return $this->morphMany(Abnormal::class, 'triggerable');
    }
}
