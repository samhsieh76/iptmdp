<?php

namespace App\Jobs;

use App\Models\HumanTrafficDailyReport;
use App\Models\HumanTrafficLog;
use Carbon\Carbon;
use stdClass;

class ProcessHumanTrafficLog extends BaseLogJob
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(HumanTrafficLog $log)
    {
        $this->log = $log;

        $date = Carbon::parse($this->log->created_at, config('app.timezone'));

        // retrieve related report
        $this->report = HumanTrafficDailyReport
            ::where('human_traffic_sensor_id', $this->log->human_traffic_sensor_id)
            ->where('date', '=', $date->format('Y-m-d'))
            ->firstOr(function () use ($date) {
                $report = HumanTrafficDailyReport::create([
                    'human_traffic_sensor_id' => $this->log->human_traffic_sensor_id,
                    'date' => $date->format('Y-m-d'),
                ]);

                return $report;
            });
    }

     /**
     * Execute the job.
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
     * @param HumanTrafficLog $log
     * @return bool
     */
    public function calcValue($log): bool
    {
        $min = $log->sensor->min_value;

        $value = ($log->raw_data > $min) ? $log->raw_data : 0;
        $value = ceil(($value / 2));

        $log->value = $value;
        $this->report->summary_value += $value;

        return true;
    }

    /**
     * Check logic of alert event
     * A counter for event
     * condition: greater than sensor.critical_value
     *
     * @param HumanTrafficLog $log
     * @param HumanTrafficDailyReport $report
     *
     * @return bool
     */
    public function checkAlert($log, $report): bool
    {
        return true;
    }

    /**
     * Check logic of Notification event
     * condition: reprot.summary_value > sensor.critical_value
     *
     * @param HumanTrafficLog $log
     * @param HumanTrafficDailyReport $report
     *
     * @return bool
     */
    public function checkNotification($log, $report): bool
    {
        $triggerTimes = intdiv($report->summary_value, $log->sensor->critical_value);

        $log->is_trigger_notification = intval(
            $log->sensor->is_notification
            and ($triggerTimes > 0)
            and ($triggerTimes > $report->notification_times)
        );

        if ($log->is_trigger_notification) {
            $report->notification_times ++;
            $report->is_under_notification = 1;

            // Send Notification
            $dataObj = new stdClass();
            $dataObj->name            = $log->sensor->name;
            $dataObj->is_notification = $log->sensor->is_notification;
            $dataObj->sensorType      = get_class($log->sensor);
            $dataObj->summary_value   = ($triggerTimes * $log->sensor->critical_value);
            $dataObj->trigged_at      = $log->created_at->format('H:i:s');

            $this->notification($log->sensor->toilet, $dataObj, 'notification');
        }

        return true;
    }

    /**
     * Check logic of Abnormal event
     * basically same with notification
     * condition: counter >= 3 and greater than sensor.max_value
     *
     * @param HumanTrafficLog $log
     * @param HumanTrafficDailyReport $report
     *
     * @return bool
     */
    public function checkAbnormal($log, $report): bool
    {
        return true;
    }

    /**
     * Check logic of Improvement event
     * condition: lesser then sensor.critical_value and under abnormal
     *
     * @param HumanTrafficLog $log
     * @param HumanTrafficDailyReport $report
     *
     * @return bool
     */
    public function checkImprovement($log, $report): bool
    {
        return true;
    }
}
