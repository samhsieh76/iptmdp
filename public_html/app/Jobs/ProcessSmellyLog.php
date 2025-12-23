<?php

namespace App\Jobs;

use App\Models\Abnormal;
use App\Models\SmellyDailyReport;
use App\Models\SmellyLog;
use App\Models\SmellySensor;
use Carbon\Carbon;
use stdClass;

class ProcessSmellyLog extends BaseLogJob
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SmellyLog $log)
    {
        $this->log = $log;

        $date = Carbon::parse($this->log->created_at, config('app.timezone'));

        // retrieve related report
        $this->report = SmellyDailyReport
            ::where('smelly_sensor_id', $this->log->smelly_sensor_id)
            ->where('date', '=', $date->format('Y-m-d'))
            ->firstOr(function () use ($date) {
                $report = SmellyDailyReport::create([
                    'smelly_sensor_id' => $this->log->smelly_sensor_id,
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
     * @param SmellyLog $log
     * @return bool
     */
    public function calcValue($log): bool
    {
        bcscale(4);

        $max = $log->sensor->max_value;
        $min = $log->sensor->min_value;
        $raw = $log->raw_data;

        $value = bcmul(
            bcdiv(bcsub($raw, $min), bcsub($max, $min)),
            100,
            2
        );

        $value = floatval($value);

        // set limit for capabality, 0~100
        $value = min(max(0.0, $value), 100.0);

        $log->value = $value;

        return true;
    }

    /**
     * Check logic of alert event
     * A counter for event
     * condition: greater than sensor.critical_value
     *
     * @param SmellyLog $log
     * @param SmellyDailyReport $report
     *
     * @return bool
     */
    public function checkAlert($log, $report): bool
    {
        $log->is_trigger_alert = intval(
            $log->sensor->is_alert
            and ($log->raw_data >= $log->sensor->critical_value)
        );

        if ($log->is_trigger_alert) {
            $report->alert_times ++;
        } else {
            // reset counter if not continuously
            $report->alert_times = 0;
        }

        return true;
    }

    /**
     * Check logic of Notification event
     * condition: counter >= 3 and greater than sensor.max_value
     *
     * @param SmellyLog $log
     * @param SmellyDailyReport $report
     *
     * @return bool
     */
    public function checkNotification($log, $report): bool
    {
        $log->is_trigger_notification = intval(
            $log->sensor->is_notification
            and ($log->raw_data >= $log->sensor->max_value)
            and !($report->is_under_notification)
            and ($report->alert_times >= 3)
        );

        if ($log->is_trigger_notification) {
            $report->notification_times ++;
            $report->is_under_notification = 1;

            // Send Notification
            $dataObj = new stdClass();
            $dataObj->name            = $log->sensor->name;
            $dataObj->is_notification = $log->sensor->is_notification;
            $dataObj->sensorType      = get_class($log->sensor);
            $dataObj->latest_value    = $log->raw_data;
            $dataObj->max_value       = $log->sensor->max_value;
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
     * @param SmellyLog $log
     * @param SmellyDailyReport $report
     *
     * @return bool
     */
    public function checkAbnormal($log, $report): bool
    {
        // check has not soloved abnormal event
        $notImprovedAbnormal = Abnormal
            ::where('triggerable_id', $log->sensor->id)
            ->where('triggerable_type', SmellySensor::class)
            ->where('is_improved', '<>', 1)
            ->first();

        $hasAbnormal = !empty($notImprovedAbnormal);

        $log->is_trigger_abnormal = intval(
            $log->sensor->is_abnormal
            and ($log->raw_data >= $log->sensor->max_value)
            and ($report->alert_times >= 3)
            and !($hasAbnormal)
        );

        if ($log->is_trigger_abnormal) {
            $report->abnormal_times ++;

            // Create Abnormal Record
            Abnormal::create([
                'toilet_id'        => $log->sensor->toilet_id,
                'triggerable_id'   => $log->sensor->id,
                'triggerable_type' => SmellySensor::class,
                'created_at'       => $log->created_at,
            ]);
        }

        return true;
    }

    /**
     * Check logic of Improvement event
     * condition: lesser then sensor.critical_value and under abnormal
     *
     * @param SmellyLog $log
     * @param SmellyDailyReport $report
     *
     * @return bool
     */
    public function checkImprovement($log, $report): bool
    {
        $abnormal = Abnormal
            ::where('triggerable_id', $log->sensor->id)
            ->where('triggerable_type', SmellySensor::class)
            ->where('is_improved', '<>', 1)
            ->first();

        $log->is_trigger_improvement = intval(
            ($log->raw_data <= $log->sensor->critical_value)
            and !empty($abnormal)
        );

        // check  abnormal
        if ($log->is_trigger_improvement and !empty($abnormal)) {
            $report->is_under_notification = 0;
            $report->improvement_times ++;

            // Update Abnormal Record
            $abnormal->is_improved = 1;
            $abnormal->improved_at = $log->created_at;
            $abnormal->improve_efficient = $abnormal->improved_at->timestamp - $abnormal->created_at->timestamp;

            $abnormal->save();

            // Do not send improvement notification
        }

        return true;
    }
}
