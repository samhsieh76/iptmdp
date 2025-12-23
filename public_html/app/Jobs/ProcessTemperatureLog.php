<?php

namespace App\Jobs;

use App\Models\TemperatureDailyReport;
use App\Models\TemperatureLog;
use Carbon\Carbon;

class ProcessTemperatureLog extends BaseLogJob
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(TemperatureLog $log)
    {
        $this->log = $log;

        $date = Carbon::parse($this->log->created_at, config('app.timezone'));

        // retrieve related report
        $this->report = TemperatureDailyReport
            ::where('temperature_sensor_id', $this->log->temperature_sensor_id)
            ->where('date', '=', $date->format('Y-m-d'))
            ->firstOr(function () use ($date) {
                $report = TemperatureDailyReport::create([
                    'temperature_sensor_id' => $this->log->temperature_sensor_id,
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
     * @param TemperatureLog $log
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
     * @param TemperatureLog $log
     * @param TemperatureDailyReport $report
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
     * @param TemperatureLog $log
     * @param TemperatureDailyReport $report
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
     * @param TemperatureLog $log
     * @param TemperatureDailyReport $report
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
     * @param TemperatureLog $log
     * @param TemperatureDailyReport $report
     *
     * @return bool
     */
    public function checkImprovement($log, $report): bool
    {
        return true;
    }
}
