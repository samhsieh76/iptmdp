<?php

namespace App\Jobs;

use App\Models\Abnormal;
use App\Models\ToiletPaperDailyReport;
use App\Models\ToiletPaperLog;
use App\Models\ToiletPaperRefillLog;
use App\Models\ToiletPaperSensor;
use Carbon\Carbon;
use stdClass;

class ProcessToiletPaperLog extends BaseLogJob
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ToiletPaperLog $log)
    {
        $this->log = $log;

        $date = Carbon::parse($this->log->created_at, config('app.timezone'));

        // retrieve related report
        $this->report = ToiletPaperDailyReport
            ::where('toilet_paper_sensor_id', $this->log->toilet_paper_sensor_id)
            ->where('date', '=', $date->format('Y-m-d'))
            ->firstOr(function () use ($date) {
                $report = ToiletPaperDailyReport::create([
                    'toilet_paper_sensor_id' => $this->log->toilet_paper_sensor_id,
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
     * @param ToiletPaperLog $log
     * @return bool
     */
    public function calcValue($log): bool
    {
        bcscale(4);

        $max = $log->sensor->max_value;
        $min = $log->sensor->min_value;
        $raw = $log->raw_data;

        $value = bcmul(
            bcdiv(bcsub($max, $raw), bcsub($max, $min)),
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
     * note: alert has no effect for this version
     * condition: capability less than 10%
     *
     * @param ToiletPaperLog $log
     * @param ToiletPaperDailyReport $report
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
     * condition: capability equal to 0% AND no notification
     *
     * @param ToiletPaperLog $log
     * @param ToiletPaperDailyReport $report
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
            $dataObj->trigged_at      = $log->created_at->format('H:i:s');

            $this->notification($log->sensor->toilet, $dataObj, 'notification');
        }

        return true;
    }

    /**
     * Check logic of Abnormal event
     * basically same with notification
     * condition: capability equal to 0% AND no abnormal events, due to abnormal will not reset after day pass
     *
     * @param ToiletPaperLog $log
     * @param ToiletPaperDailyReport $report
     *
     * @return bool
     */
    public function checkAbnormal($log, $report): bool
    {
        // check has not soloved abnormal event
        $notImprovedAbnormal = Abnormal
            ::where('triggerable_id', $log->sensor->id)
            ->where('triggerable_type', ToiletPaperSensor::class)
            ->where('is_improved', '<>', 1)
            ->first();

        $hasAbnormal = !empty($notImprovedAbnormal);

        $log->is_trigger_abnormal = intval(
            $log->sensor->is_abnormal
            and ($log->raw_data <= $log->sensor->max_value)
            and ($report->alert_times >= 3)
            and !($hasAbnormal)
        );

        if ($log->is_trigger_abnormal) {
            $report->abnormal_times ++;

            // Create Abnormal Record
            Abnormal::create([
                'toilet_id'        => $log->sensor->toilet_id,
                'triggerable_id'   => $log->sensor->id,
                'triggerable_type' => ToiletPaperSensor::class,
                'created_at'       => $log->created_at,
            ]);
        }

        return true;
    }

    /**
     * Check logic of Improvement event
     * condition: campare with previous capabality greater than 50% and under abnormal
     *
     * @param ToiletPaperLog $log
     * @param ToiletPaperDailyReport $report
     *
     * @return bool
     */
    public function checkImprovement($log, $report): bool
    {
        $abnormal = Abnormal
            ::where('triggerable_id', $log->sensor->id)
            ->where('triggerable_type', ToiletPaperSensor::class)
            ->where('is_improved', '<>', 1)
            ->first();

        $bound = bcmul(bcadd($log->sensor->max_value, $log->sensor->min_value), 0.5, 2);

        $log->is_trigger_improvement = intval(
            ($log->raw_data <= $bound)
            and !empty($abnormal)
        );

        // todo check if under abnormal
        if ($log->is_trigger_improvement) {
            $report->is_under_notification = 0;
            $report->improvement_times ++;

            // Update Abnormal Record
            $abnormal->is_improved = 1;
            $abnormal->improved_at = $log->created_at;
            $abnormal->improve_efficient = $abnormal->improved_at->timestamp - $abnormal->created_at->timestamp;

            $abnormal->save();

            // Create refill log
            ToiletPaperRefillLog::create([
                'toilet_paper_sensor_id' => $log->sensor->id,
                'created_at'             => $log->created_at,
                'updated_at'             => $log->updated_at,
            ]);

            // Send notification
            $toilet = $log->sensor->toilet;
            $delay  = $this->calcDelaySecondsForImprovement($toilet, $abnormal);

            $dataObj = new stdClass();
            $dataObj->name            = $log->sensor->name;
            $dataObj->is_notification = $log->sensor->is_notification;
            $dataObj->sensorType      = get_class($log->sensor);
            $dataObj->sensorId        = $log->sensor->id;
            $dataObj->trigged_at      = $log->created_at->format('H:i:s');

            $this->notification($toilet, $dataObj, 'improvement', $delay);
        }

        return true;
    }
}
