<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class HumanTrafficSensor extends MyModel {
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
        return $query->leftJoin('human_traffic_daily_reports AS latest_report', function ($join) {
            $join->on('human_traffic_sensors.id', '=', 'latest_report.human_traffic_sensor_id')
                ->where('latest_report.id', '=', function ($query) {
                    $query->select('id')
                        ->from('human_traffic_daily_reports')
                        ->whereColumn('human_traffic_sensor_id', 'human_traffic_sensors.id')
                        ->latest()
                        ->limit(1);
                });
        });
    }

    public function scopeJoinLogByDate($query, $start_date, $end_date) {
        return $query->join('human_traffic_logs AS logs', function ($join) use($start_date, $end_date) {
            $join->on('human_traffic_sensors.id', '=', 'logs.human_traffic_sensor_id')->whereBetween('logs.created_at', [$start_date, $end_date]);
        });
    }

    public function toilet() {
        return $this->belongsTo(Toilet::class);
    }

    public function logs() {
        return $this->hasMany(HumanTrafficLog::class, 'human_traffic_sensor_id', 'id');
    }

    public function dailyReports() {
        return $this->hasMany(HumanTrafficDailyReport::class, 'human_traffic_sensor_id', 'id');
    }
}
