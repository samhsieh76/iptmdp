<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessToiletPaperLog;
use App\Models\Abnormal;
use App\Models\ToiletPaperDailyReport;
use App\Models\ToiletPaperLog;
use App\Models\ToiletPaperRefillLog;
use App\Models\ToiletPaperSensor;
use App\Notifications\LineNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessToiletPaperLogTest extends TestCase
{
    use RefreshDatabase;

    protected $sensor = null;
    protected $date   = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            'Database\\Seeders\\BaseSeeder',
            'Database\\Seeders\\LocationSeeder',
            'Database\\Seeders\\ToiletSeeder',
            'Database\\Seeders\\ToiletPaperSensorSeeder',
        ]);

        $this->sensor = ToiletPaperSensor::all()->random();
        $this->sensor->critical_value = bcadd(
            bcmul(bcsub($this->sensor->max_value, $this->sensor->min_value, 2), 0.9, 2),
            $this->sensor->min_value,
            2
        );
        $this->sensor->save();
        $this->date   = Carbon::parse('today', config('app.config'));
    }

    private function calcValue($raw)
    {
        bcscale(4);

        // calculate value
        $max = $this->sensor->max_value;
        $min = $this->sensor->min_value;
        $raw = $raw;

        $value = bcmul(
            bcdiv(bcsub($max, $raw), bcsub($max, $min)),
            100,
            2
        );

        $value = floatval($value);

        // set limit for capabality, 0~100
        $value = min(max(0.0, $value), 100.0);

        return $value;
    }

    /**
     * Calculate value by sensor logs
     *
     * @return void
     */
    public function test_calculate_value()
    {
        // preprae data
        $log = ToiletPaperLog::create([
            'toilet_paper_sensor_id' => $this->sensor->id,
            'raw_data' => 15.0,
        ]);

        $job = new ProcessToiletPaperLog($log);
        $job->handle();

        $log->refresh();

        $value = $this->calcValue($log->raw_data);

        $this->assertEquals($value, $log->value);

        // check sensor latest value
        $this->sensor->refresh();
        $this->assertEquals($log->raw_data, $this->sensor->latest_raw_data);
        $this->assertEquals($log->value, $this->sensor->latest_value);
        $this->assertEquals($log->created_at, $this->sensor->latest_updated_at);
    }

    /**
     * Calculate value by sensor logs
     * Trigger alert
     *
     * @return void
     */
    public function test_calculate_value_with_alert()
    {
        // preprae data
        $log = ToiletPaperLog::create([
            'toilet_paper_sensor_id' => $this->sensor->id,
            'raw_data'               => $this->sensor->critical_value,
        ]);

        $job = new ProcessToiletPaperLog($log);
        $job->handle();

        $log->refresh();

        $value = $this->calcValue($log->raw_data);

        // check log
        $this->assertEquals($value, $log->value);
        $this->assertEquals(1, $log->is_trigger_alert);

        // check report
        $report = ToiletPaperDailyReport
            ::where('toilet_paper_sensor_id', $this->sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertEquals(1, $report->alert_times);
    }

    /**
     * Calculate value by sensor logs
     * Trigger notification
     *
     * @return void
     */
    public function test_calculate_value_with_notification()
    {
        Queue::fake();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('now -30 minutes', config('app.config'));
        $rawData   = $this->sensor->critical_value;

        for ($i = 0; $i < 3; $i++) {
            $log = ToiletPaperLog::create([
                'toilet_paper_sensor_id' => $this->sensor->id,
                'raw_data'               => $rawData,
                'created_at'             => $createdAt,
                'updated_at'             => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            if ($i >= 1) {
                $rawData = $this->sensor->max_value;
            }

            $logs->push($log);

            $job = new ProcessToiletPaperLog($log);
            $job->handle();
        }

        // check logs
        $newLogs = ToiletPaperLog::whereIn('id', $logs->pluck('id'))->get();
        foreach ($newLogs as $log) {
            $value = $this->calcValue($log->raw_data);

            $this->assertEquals($value, $log->value);
            $this->assertEquals(1, $log->is_trigger_alert);
        }

        $latestLog = $newLogs->sortByDesc('created_at')->first();
        $this->assertEquals(1, $latestLog->is_trigger_notification);

        // check report
        $report = ToiletPaperDailyReport
            ::where('toilet_paper_sensor_id', $this->sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertEquals(3, $report->alert_times);
        $this->assertEquals(1, $report->notification_times);
        $this->assertEquals(1, $report->is_under_notification);

        // check notification
        Queue::assertPushed(SendQueuedNotifications::class, function ($job) {
            return ($job->notification instanceof LineNotification);
        });
    }

    /**
     * Calculate value by sensor logs
     * Trigger abnormal
     *
     * @return void
     */
    public function test_calculate_value_with_abnormal()
    {
        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('now -30 minutes', config('app.config'));
        $rawData   = $this->sensor->critical_value;

        for ($i = 0; $i < 3; $i++) {
            $log = ToiletPaperLog::create([
                'toilet_paper_sensor_id' => $this->sensor->id,
                'raw_data'               => $rawData,
                'created_at'             => $createdAt,
                'updated_at'             => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            if ($i >= 1) {
                $rawData = $this->sensor->max_value;
            }

            $logs->push($log);

            $job = new ProcessToiletPaperLog($log);
            $job->handle();
        }

        // check logs
        $newLogs = ToiletPaperLog::whereIn('id', $logs->pluck('id'))->get();
        foreach ($newLogs as $log) {
            $value = $this->calcValue($log->raw_data);

            $this->assertEquals($value, $log->value);
            $this->assertEquals(1, $log->is_trigger_alert);
        }

        $latestLog = $newLogs->sortByDesc('created_at')->first();
        $this->assertEquals(1, $latestLog->is_trigger_notification);

        // check report
        $report = ToiletPaperDailyReport
            ::where('toilet_paper_sensor_id', $this->sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertEquals(1, $report->is_under_notification);
        $this->assertEquals(3, $report->alert_times);
        $this->assertEquals(1, $report->notification_times);
        $this->assertEquals(1, $report->abnormal_times);

        // check abnormal
        $log = $logs->sortByDesc('created_at')->first();

        $abnormal = Abnormal
            ::where('triggerable_id', $log->sensor->id)
            ->where('triggerable_type', ToiletPaperSensor::class)
            ->where('is_improved', '<>', 1)
            ->first();

        $this->assertNotNull($abnormal);
        $this->assertEquals($log->created_at, $abnormal->created_at);
    }

    /**
     * Calculate value by sensor logs
     * Trigger improvement
     *
     * @return void
     */
    public function test_calculate_value_with_improvement()
    {
        Queue::fake();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('now -30 minutes', config('app.config'));
        $rawData   = $this->sensor->critical_value;

        for ($i = 0; $i < 4; $i++) {
            $log = ToiletPaperLog::create([
                'toilet_paper_sensor_id' => $this->sensor->id,
                'raw_data'               => $rawData,
                'created_at'             => $createdAt,
                'updated_at'             => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            switch ($i) {
                case 0:
                    break;
                case 1:
                    $rawData = $this->sensor->max_value;
                    break;
                case 2:
                    $rawData = bcmul(bcadd($this->sensor->max_value, $this->sensor->min_value, 2), 0.5, 2);
                    break;
            }

            $logs->push($log);

            $job = new ProcessToiletPaperLog($log);
            $job->handle();
        }

        // check logs
        $newLogs   = ToiletPaperLog::whereIn('id', $logs->pluck('id'))->get();
        $latestLog = $newLogs->sortByDesc('created_at')->first();

        $this->assertEquals(0, $log->is_trigger_alert);
        $this->assertEquals(0, $latestLog->is_trigger_notification);
        $this->assertEquals(0, $log->is_trigger_abnormal);
        $this->assertEquals(1, $latestLog->is_trigger_improvement);

        // check report
        $report = ToiletPaperDailyReport
            ::where('toilet_paper_sensor_id', $this->sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertEquals(0, $report->is_under_notification);
        $this->assertEquals(0, $report->alert_times);
        $this->assertEquals(1, $report->notification_times);
        $this->assertEquals(1, $report->abnormal_times);
        $this->assertEquals(1, $report->improvement_times);

        // check abnormal
        $log = $logs->sortByDesc('created_at')->first();

        $abnormal = Abnormal
            ::where('triggerable_id', $log->sensor->id)
            ->where('triggerable_type', ToiletPaperSensor::class)
            ->where('is_improved', '=', 1)
            ->first();

        $this->assertNotNull($abnormal);
        $this->assertEquals($log->created_at, $abnormal->improved_at);

        // check refill log
        $refillLog = ToiletPaperRefillLog
            ::where('toilet_paper_sensor_id', $this->sensor->id)
            ->where('created_at', $log->created_at)
            ->first();

        $this->assertNotNull($refillLog);

        // check notification
        Queue::assertPushed(SendQueuedNotifications::class);
    }

    /**
     * Calculate value by sensor logs
     * Trigger improvement
     * By requirement, if improment happened out of timeframe, should be sent in next timeframe.
     *
     * @return void
     */
    public function test_calculate_value_with_improvement_has_delay()
    {
        Queue::fake();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse($this->sensor->toilet->notification_end . ' -10 minutes', config('app.timezone'));
        $rawData   = $this->sensor->critical_value;

        for ($i = 0; $i < 4; $i++) {
            $log = ToiletPaperLog::create([
                'toilet_paper_sensor_id' => $this->sensor->id,
                'raw_data'               => $rawData,
                'created_at'             => $createdAt,
                'updated_at'             => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            switch ($i) {
                case 0:
                    break;
                case 1:
                    $rawData = $this->sensor->max_value;
                    break;
                case 2:
                    $rawData = bcmul(bcadd($this->sensor->max_value, $this->sensor->min_value, 2), 0.5, 2);
                    break;
            }

            $logs->push($log);

            $job = new ProcessToiletPaperLog($log);
            $job->handle();
        }

        // check logs
        $newLogs   = ToiletPaperLog::whereIn('id', $logs->pluck('id'))->get();
        $latestLog = $newLogs->sortByDesc('created_at')->first();

        $this->assertEquals(0, $log->is_trigger_alert);
        $this->assertEquals(0, $latestLog->is_trigger_notification);
        $this->assertEquals(0, $log->is_trigger_abnormal);
        $this->assertEquals(1, $latestLog->is_trigger_improvement);

        // check report
        $report = ToiletPaperDailyReport
            ::where('toilet_paper_sensor_id', $this->sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertEquals(0, $report->is_under_notification);
        $this->assertEquals(0, $report->alert_times);
        $this->assertEquals(1, $report->notification_times);
        $this->assertEquals(1, $report->abnormal_times);
        $this->assertEquals(1, $report->improvement_times);

        // check abnormal
        $log = $logs->sortByDesc('created_at')->first();

        $abnormal = Abnormal
            ::where('triggerable_id', $log->sensor->id)
            ->where('triggerable_type', ToiletPaperSensor::class)
            ->where('is_improved', '=', 1)
            ->first();

        $this->assertNotNull($abnormal);
        $this->assertEquals($log->created_at, $abnormal->improved_at);

        // check refill log
        $refillLog = ToiletPaperRefillLog
            ::where('toilet_paper_sensor_id', $this->sensor->id)
            ->where('created_at', $log->created_at)
            ->first();

        $this->assertNotNull($refillLog);

        // check notification
        Queue::assertPushed(SendQueuedNotifications::class, function ($job) {
            $check = (
                ($job->notification->type == 'improvement')
                and ($job->notification->delay > 0)
                and ($job->notification->delayed == true)
            );

            return $check;
        });
    }
}
