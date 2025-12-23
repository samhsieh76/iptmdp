<?php

namespace App\Jobs;

use App\Models\Abnormal;
use App\Models\HandLotionDailyReport;
use App\Models\HandLotionLog;
use App\Models\HumanTrafficDailyReport;
use App\Models\HumanTrafficLog;
use App\Models\RelativeHumidityDailyReport;
use App\Models\RelativeHumidityLog;
use App\Models\SmellyDailyReport;
use App\Models\SmellyLog;
use App\Models\TemperatureDailyReport;
use App\Models\TemperatureLog;
use App\Models\Toilet;
use App\Models\ToiletPaperDailyReport;
use App\Models\ToiletPaperLog;
use App\Notifications\LineNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

abstract class BaseLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $log    = null;
    protected $report = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct($log)
    {
        // $this->log = $log;
        // this->report = Report::firstOrCreate();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::beginTransaction();

        try {
            // calcute value
            $this->calcValue($this->log);

            // alert
            $this->checkAlert($this->log, $this->report);

            // // nodification
            $this->checkNotification($this->log, $this->report);

            // // abnormal
            $this->checkAbnormal($this->log, $this->report);

            // // improve
            $this->checkImprovement($this->log, $this->report);

            // // update latest_* in sensor
            $this->updateLatest($this->log);

            $this->log->save();
            $this->report->save();

            DB::commit();
        } catch (\Exception $e) {
            $message = sprintf("%s: %d process failed. Reason: %s", __CLASS__, $this->log->id, $e->getMessage());
            Log::error($message);

            DB::rollBack();
        }

        return true;
    }

    /**
     * Calculate human-readable value
     *
     * @param ToiletPaperLog|SmellyLog|HumanTrafficLog|HandLotionLog|RelativeHumidityLog|TemperatureLog $log
     * @return bool
     */
    abstract public function calcValue($log): bool;

    /**
     * Check logic of Alert event
     *
     * @param ToiletPaperLog|SmellyLog|HumanTrafficLog|HandLotionLog|RelativeHumidityLog|TemperatureLog $log
     * @param ToiletPaperDailyReport|SmellyDailyReport|HumanTrafficDailyReport|HandLotionDailyReport|RelativeHumidityDailyReport|TemperatureDailyReport $report
     *
     * @return bool
     */
    abstract public function checkAlert($log, $report): bool;

    /**
     * Check logic of Notification event
     *
     * @param ToiletPaperLog|SmellyLog|HumanTrafficLog|HandLotionLog|RelativeHumidityLog|TemperatureLog $log
     * @param ToiletPaperDailyReport|SmellyDailyReport|HumanTrafficDailyReport|HandLotionDailyReport|RelativeHumidityDailyReport|TemperatureDailyReport $report
     *
     * @return bool
     */
    abstract public function checkNotification($log, $report): bool;

    /**
     * Check logic of Abnormal event
     *
     * @param ToiletPaperLog|SmellyLog|HumanTrafficLog|HandLotionLog|RelativeHumidityLog|TemperatureLog $log
     * @param ToiletPaperDailyReport|SmellyDailyReport|HumanTrafficDailyReport|HandLotionDailyReport|RelativeHumidityDailyReport|TemperatureDailyReport $report
     *
     * @return bool
     */
    abstract public function checkAbnormal($log, $report): bool;

    /**
     * Check logic of Improvement event
     *
     * @param ToiletPaperLog|SmellyLog|HumanTrafficLog|HandLotionLog|RelativeHumidityLog|TemperatureLog $log
     * @param ToiletPaperDailyReport|SmellyDailyReport|HumanTrafficDailyReport|HandLotionDailyReport|RelativeHumidityDailyReport|TemperatureDailyReport $report
     *
     * @return bool
     */
    abstract public function checkImprovement($log, $report): bool;

    /**
     * Update latest value into Sensor
     *
     * @param ToiletPaperLog|SmellyLog|HumanTrafficLog|HandLotionLog|RelativeHumidityLog|TemperatureLog $log
     * @return bool
     */
    public function updateLatest($log): bool
    {
        $sensor = $log->sensor;

        $sensor->latest_raw_data   = $log->raw_data;
        $sensor->latest_value      = $log->value;
        $sensor->latest_updated_at = $log->created_at;

        $sensor->save();

        return true;
    }


    /**
     * The function calculates the delay in seconds for improvement based on the start and end time of
     * a toilet notification and the timestamps of an abnormal event.
     *
     * @param Toilet toilet An instance of the Toilet class, which represents a toilet object.
     * @param Abnormal abnormal The `` parameter is an instance of the `Abnormal` class, which
     * represents an abnormal event or occurrence related to a toilet. It contains information such as
     * the timestamp when the abnormal event was created (`created_at`) and the timestamp when the
     * abnormal event was marked as improved (`improved
     *
     * @return int an integer value representing the delay in seconds for improvement.
     */
    protected function calcDelaySecondsForImprovement(Toilet $toilet, Abnormal $abnormal): int
    {
        $delay = 0;

        $start = Carbon::parse($toilet->notification_start, config('app.timezone'));
        $end   = Carbon::parse($toilet->notification_end, config('app.timezone'));

        if ($abnormal->created_at <= $end and $abnormal->improved_at > $end) {
            $tomorrowStart = (clone $start)->addDay();
            $delay         = $abnormal->improved_at->diffInSeconds($tomorrowStart);
        }

        return $delay;
    }

    /**
     * Send notification into queue
     * Calc delay if not in notificable timeframe
     */
    protected function notification(Toilet $toilet, stdClass $dataObj, $type = 'notification', $delay = 0)
    {
        $delayed = ($delay > 0);
        $toilet->notify((new LineNotification($dataObj, $type, $delayed))->delay($delay));

        return true;
    }
}
