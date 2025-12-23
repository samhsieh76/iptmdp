<?php

namespace App\Jobs;

use App\Models\RelativeHumidityDailyReport;
use App\Models\RelativeHumidityLog;
use Carbon\Carbon;

class ProcessRelativeHumidityLog extends BaseLogJob
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RelativeHumidityLog $log)
    {
        $this->log = $log;

        $date = Carbon::parse($this->log->created_at, config('app.timezone'));

        // retrieve related report
        $this->report = RelativeHumidityDailyReport
            ::where('relative_humidity_sensor_id', $this->log->relative_humidity_sensor_id)
            ->where('date', '=', $date->format('Y-m-d'))
            ->firstOr(function () use ($date) {
                $report = RelativeHumidityDailyReport::create([
                    'relative_humidity_sensor_id' => $this->log->relative_humidity_sensor_id,
                    'date' => $date->format('Y-m-d'),
                ]);

                return $report;
            });
    }

    /** Execute the job.
     *
     * @return void
     */
    // public function handle()
    // {
    //     parent::handle();
    // }

    /**
     * Calculate human-readable value
     *
     * @param RelativeHumidityLog $log
     * @return bool
     */
    public function calcValue($log): bool
    {
        $value = $log->raw_data;

        $log->value = $value;

        return true;
    }

    /**
     * Check logic of alert event
     * Counter for other event
     *
     * @param RelativeHumidityLog $log
     * @param RelativeHumidityDailyReport $report
     *
     * @return bool
     */
    public function checkAlert($log, $report): bool
    {
        return true;
    }

    /**
     * Check logic of Notification event
     *
     * @param RelativeHumidityLog $log
     * @param RelativeHumidityDailyReport $report
     *
     * @return bool
     */
    public function checkNotification($log, $report): bool
    {
        return true;
    }

    /**
     * Check logic of Abnormal event
     * basically same with notification
     *
     * @param RelativeHumidityLog $log
     * @param RelativeHumidityDailyReport $report
     *
     * @return bool
     */
    public function checkAbnormal($log, $report): bool
    {
        return true;
    }

    /**
     * Check logic of Improvement event
     *
     * @param RelativeHumidityLog $log
     * @param RelativeHumidityDailyReport $report
     *
     * @return bool
     */
    public function checkImprovement($log, $report): bool
    {
        return true;
    }

    /**
     * Update latest value into Sensor
     *
     * @param RelativeHumidityLog $log
     * @return bool
     */
    public function updateLatest($log): bool
    {
        $sensor = $log->sensor;

        $sensor->latest_humidity_raw_data   = $log->raw_data;
        $sensor->latest_humidity_value      = $log->value;
        $sensor->latest_humidity_updated_at = $log->created_at;

        $sensor->save();

        return true;
    }
}
