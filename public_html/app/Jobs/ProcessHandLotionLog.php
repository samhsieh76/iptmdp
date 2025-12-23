<?php

namespace App\Jobs;

use App\Models\Abnormal;
use App\Models\HandLotionDailyReport;
use App\Models\HandLotionLog;
use App\Models\HandLotionRefillLog;
use App\Models\HandLotionSensor;
use Carbon\Carbon;
use stdClass;

class ProcessHandLotionLog extends BaseLogJob
{
      /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(HandLotionLog $log)
    {
        $this->log = $log;

        $date = Carbon::parse($this->log->created_at, config('app.timezone'));

          // retrieve related report
        $this->report = HandLotionDailyReport
            ::where('hand_lotion_sensor_id', $this->log->hand_lotion_sensor_id)
            ->where('date', '=', $date->format('Y-m-d'))
            ->firstOr(function () use ($date) {
                $report = HandLotionDailyReport::create([
                    'hand_lotion_sensor_id' => $this->log->hand_lotion_sensor_id,
                    'date'                  => $date->format('Y-m-d'),
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
     * @param HandLotionLog $log
     * @return bool
     */
    public function calcValue($log): bool
    {
        $log->value = $log->raw_data;

        return true;
    }

      /**
     * Check logic of alert event
     * Counter for other event
     * condition: log.raw_data = 0
     *
     * @param HandLotionLog $log
     * @param HandLotionDailyReport $report
     *
     * @return bool
     */
    public function checkAlert($log, $report): bool
    {
        $log->is_trigger_alert = intval(
            $log->sensor->is_alert
            and ($log->value <= 0)
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
     * condition: log.raw_data = 0 and counter > = 3
     *
     * @param HandLotionLog $log
     * @param HandLotionDailyReport $report
     *
     * @return bool
     */
    public function checkNotification($log, $report): bool
    {
        $log->is_trigger_notification = intval(
            $log->sensor->is_notification
            and ($log->value <= 0)
            and !($report->is_under_notification)
            and ($report->alert_times >= 3)
        );

        if ($log->is_trigger_notification) {
            $report->notification_times ++;
            $report->is_under_notification = 1;

              // Send Notification
            $dataObj                  = new stdClass();
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
     * condition: log.raw_data = 0 and counter > = 3
     *
     * @param HandLotionLog $log
     * @param HandLotionDailyReport $report
     *
     * @return bool
     */
    public function checkAbnormal($log, $report): bool
    {
          // check has not soloved abnormal event
        $notImprovedAbnormal = Abnormal
            ::where('triggerable_id', $log->sensor->id)
            ->where('triggerable_type', HandLotionSensor::class)
            ->where('is_improved', '<>', 1)
            ->first();

        $hasAbnormal = !empty($notImprovedAbnormal);

        $log->is_trigger_abnormal = intval(
            $log->sensor->is_abnormal
            and ($log->value <= 0)
            and ($report->alert_times >= 3)
            and !($hasAbnormal)
        );

        if ($log->is_trigger_abnormal) {
            $report->abnormal_times ++;

              // Create Abnormal Record
            Abnormal::create([
                'toilet_id'        => $log->sensor->toilet_id,
                'triggerable_id'   => $log->sensor->id,
                'triggerable_type' => HandLotionSensor::class,
                'created_at'       => $log->created_at,
            ]);
        }

        return true;
    }

      /**
     * Check logic of Improvement event
     * condition: campare with previous capabality greater than 50% and under abnormal
     *
     * @param HandLotionLog $log
     * @param HandLotionDailyReport $report
     *
     * @return bool
     */
    public function checkImprovement($log, $report): bool
    {
        $abnormal = Abnormal
            ::where('triggerable_id', $log->sensor->id)
            ->where('triggerable_type', HandLotionSensor::class)
            ->where('is_improved', '<>', 1)
            ->first();

        $log->is_trigger_improvement = intval(
            ($log->value >= 1)
            and !empty($abnormal)
        );

          // check if under abnormal
        if ($log->is_trigger_improvement) {
            $report->is_under_notification = 0;
            $report->improvement_times ++;

              // Update Abnormal Record
            $abnormal->is_improved       = 1;
            $abnormal->improved_at       = $log->created_at;
            $abnormal->improve_efficient = $abnormal->improved_at->timestamp - $abnormal->created_at->timestamp;

            $abnormal->save();

              // Create refill log
            HandLotionRefillLog::create([
                'hand_lotion_sensor_id' => $log->sensor->id,
                'created_at'            => $log->created_at,
                'updated_at'            => $log->updated_at,
            ]);

            // Send notification
            $toilet = $log->sensor->toilet;
            $delay  = $this->calcDelaySecondsForImprovement($toilet, $abnormal);

            $dataObj                  = new stdClass();
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
