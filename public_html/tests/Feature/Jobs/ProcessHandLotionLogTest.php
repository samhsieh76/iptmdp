<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessHandLotionLog;
use App\Models\Abnormal;
use App\Models\HandLotionDailyReport;
use App\Models\HandLotionLog;
use App\Models\HandLotionRefillLog;
use App\Models\HandLotionSensor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessHandLotionLogTest extends TestCase
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
            'Database\\Seeders\\HandLotionSensorSeeder',
        ]);

        $this->sensor = HandLotionSensor::all()->random();
        $this->date   = Carbon::parse('today', config('app.timezone'));
    }

    private function calcValue($raw)
    {
        return $raw;
    }

    /**
     * Calculate value by sensor logs
     *
     * @return void
     */
    public function test_calculate_value()
    {
        // preprae data
        $log = HandLotionLog::create([
            'hand_lotion_sensor_id' => $this->sensor->id,
            'raw_data'              => 1,
        ]);

        // action
        $job = new ProcessHandLotionLog($log);
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
        $log = HandLotionLog::create([
            'hand_lotion_sensor_id' => $this->sensor->id,
            'raw_data'              => 0,
        ]);

        // action
        $job = new ProcessHandLotionLog($log);
        $job->handle();

        // assertions
        $log->refresh();

        $value = $this->calcValue($log->raw_data);

        // check log
        $this->assertEquals($value, $log->value);
        $this->assertEquals(1, $log->is_trigger_alert);

        // check report
        $report = HandLotionDailyReport
            ::where('hand_lotion_sensor_id', $this->sensor->id)
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
        $createdAt = Carbon::parse('now -30 minutes', config('app.timezone'));
        $rawData   = 0;

        for ($i = 0; $i < 3; $i++) {
            $log = HandLotionLog::create([
                'hand_lotion_sensor_id' => $this->sensor->id,
                'raw_data'              => $rawData,
                'created_at'            => $createdAt,
                'updated_at'            => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            // action
            $job = new ProcessHandLotionLog($log);
            $job->handle();
        }

        // check logs
        $newLogs = HandLotionLog::whereIn('id', $logs->pluck('id'))->get();
        foreach ($newLogs as $log) {
            $value = $this->calcValue($log->raw_data);

            $this->assertEquals($value, $log->value);
            $this->assertEquals(1, $log->is_trigger_alert);
        }

        $latestLog = $newLogs->sortByDesc('created_at')->first();
        $this->assertEquals(1, $latestLog->is_trigger_notification);

        // check report
        $report = HandLotionDailyReport
            ::where('hand_lotion_sensor_id', $this->sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertEquals(3, $report->alert_times);
        $this->assertEquals(1, $report->notification_times);
        $this->assertEquals(1, $report->is_under_notification);

        // check notification
        Queue::assertPushed(SendQueuedNotifications::class);
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
        $createdAt = Carbon::parse('now -30 minutes', config('app.timezone'));
        $rawData   = 0;

        for ($i = 0; $i < 3; $i++) {
            $log = HandLotionLog::create([
                'hand_lotion_sensor_id' => $this->sensor->id,
                'raw_data'              => $rawData,
                'created_at'            => $createdAt,
                'updated_at'            => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            // action
            $job = new ProcessHandLotionLog($log);
            $job->handle();
        }

        // check logs
        $newLogs = HandLotionLog::whereIn('id', $logs->pluck('id'))->get();
        foreach ($newLogs as $log) {
            $value = $this->calcValue($log->raw_data);

            $this->assertEquals($value, $log->value);
            $this->assertEquals(1, $log->is_trigger_alert);
        }

        $latestLog = $newLogs->sortByDesc('created_at')->first();
        $this->assertEquals(1, $latestLog->is_trigger_notification);

        // check report
        $report = HandLotionDailyReport
            ::where('hand_lotion_sensor_id', $this->sensor->id)
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
            ->where('triggerable_type', HandLotionSensor::class)
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
        $createdAt = Carbon::parse('now -30 minutes', config('app.timezone'));
        $rawData   = 0;

        for ($i = 0; $i < 4; $i++) {
            if ($i >= 3) {
                $rawData = 1;
            }

            $log = HandLotionLog::create([
                'hand_lotion_sensor_id' => $this->sensor->id,
                'raw_data'               => $rawData,
                'created_at'             => $createdAt,
                'updated_at'             => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            // action
            $job = new ProcessHandLotionLog($log);
            $job->handle();
        }

        // check logs
        $newLogs   = HandLotionLog::whereIn('id', $logs->pluck('id'))->get();
        $latestLog = $newLogs->sortByDesc('created_at')->first();

        $this->assertEquals(0, $latestLog->is_trigger_alert);
        $this->assertEquals(0, $latestLog->is_trigger_notification);
        $this->assertEquals(0, $latestLog->is_trigger_abnormal);
        $this->assertEquals(1, $latestLog->is_trigger_improvement);

        // check report
        $report = HandLotionDailyReport
            ::where('hand_lotion_sensor_id', $this->sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertEquals(0, $report->is_under_notification);
        $this->assertEquals(0, $report->alert_times);
        $this->assertEquals(1, $report->notification_times);
        $this->assertEquals(1, $report->abnormal_times);
        $this->assertEquals(1, $report->improvement_times);

        // check abnormal
        $abnormal = Abnormal
            ::where('triggerable_id', $log->sensor->id)
            ->where('triggerable_type', HandLotionSensor::class)
            ->where('is_improved', '=', 1)
            ->first();

        $this->assertNotNull($abnormal);

        // check refill log
        $log = $logs->sortByDesc('created_at')->first();

        $refillLog = HandLotionRefillLog
            ::where('hand_lotion_sensor_id', $this->sensor->id)
            ->where('created_at', $log->created_at)
            ->first();

        $this->assertNotNull($refillLog);
        $this->assertEquals($log->created_at, $abnormal->improved_at);

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
        $rawData   = 0;

        for ($i = 0; $i < 4; $i++) {
            if ($i >= 3) {
                $rawData = 1;
            }

            $log = HandLotionLog::create([
                'hand_lotion_sensor_id' => $this->sensor->id,
                'raw_data'               => $rawData,
                'created_at'             => $createdAt,
                'updated_at'             => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            // action
            $job = new ProcessHandLotionLog($log);
            $job->handle();
        }

        // check logs
        $newLogs   = HandLotionLog::whereIn('id', $logs->pluck('id'))->get();
        $latestLog = $newLogs->sortByDesc('created_at')->first();

        $this->assertEquals(0, $latestLog->is_trigger_alert);
        $this->assertEquals(0, $latestLog->is_trigger_notification);
        $this->assertEquals(0, $latestLog->is_trigger_abnormal);
        $this->assertEquals(1, $latestLog->is_trigger_improvement);

        // check report
        $report = HandLotionDailyReport
            ::where('hand_lotion_sensor_id', $this->sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertEquals(0, $report->is_under_notification);
        $this->assertEquals(0, $report->alert_times);
        $this->assertEquals(1, $report->notification_times);
        $this->assertEquals(1, $report->abnormal_times);
        $this->assertEquals(1, $report->improvement_times);

        // check abnormal
        $abnormal = Abnormal
            ::where('triggerable_id', $log->sensor->id)
            ->where('triggerable_type', HandLotionSensor::class)
            ->where('is_improved', '=', 1)
            ->first();

        $this->assertNotNull($abnormal);

        // check refill log
        $log = $logs->sortByDesc('created_at')->first();

        $refillLog = HandLotionRefillLog
            ::where('hand_lotion_sensor_id', $this->sensor->id)
            ->where('created_at', $log->created_at)
            ->first();

        $this->assertNotNull($refillLog);
        $this->assertEquals($log->created_at, $abnormal->improved_at);

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
