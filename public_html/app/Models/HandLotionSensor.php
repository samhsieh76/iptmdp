<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class HandLotionSensor extends MyModel {
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
        return $query->leftJoin('hand_lotion_daily_reports AS latest_report', function ($join) {
            $join->on('hand_lotion_sensors.id', '=', 'latest_report.hand_lotion_sensor_id')
                ->where('latest_report.id', '=', function ($query) {
                    $query->select('id')
                        ->from('hand_lotion_daily_reports')
                        ->whereColumn('hand_lotion_sensor_id', 'hand_lotion_sensors.id')
                        ->latest()
                        ->limit(1);
                });
        });
    }

    public function scopeJoinReportByDate($query, $date) {
        return $query->join('hand_lotion_daily_reports AS report', function ($join) use($date) {
            $join->on('hand_lotion_sensors.id', '=', 'report.hand_lotion_sensor_id')->where('report.date', '=', $date);
        });
    }

    public function toilet() {
        return $this->belongsTo(Toilet::class);
    }

    public function logs() {
        return $this->hasMany(HandLotionLog::class, 'hand_lotion_sensor_id', 'id');
    }

    public function dailyReports() {
        return $this->hasMany(HandLotionDailyReport::class, 'hand_lotion_sensor_id', 'id');
    }

    public function abnormals() {
        return $this->morphMany(Abnormal::class, 'triggerable');
    }
}
