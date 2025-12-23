<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessSmellyLog;
use App\Models\Abnormal;
use App\Models\SmellyDailyReport;
use App\Models\SmellyLog;
use App\Models\SmellySensor;
use App\Notifications\LineNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessSmellyLogTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $sensor = null;
    protected $date   = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            'Database\\Seeders\\BaseSeeder',
            'Database\\Seeders\\LocationSeeder',
            'Database\\Seeders\\ToiletSeeder',
            'Database\\Seeders\\SmellySensorSeeder',
        ]);

        $this->sensor = SmellySensor::all()->random();
        $this->date   = Carbon::parse('today', config('app.timezone'));
    }

    private function calcValue($raw)
    {
        // calculate value
        bcscale(4);

        $max = $this->sensor->max_value;
        $min = $this->sensor->min_value;

        $value = bcmul(
            bcdiv(bcsub($raw, $min), bcsub($max, $min)),
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
        $log = SmellyLog::create([
            'smelly_sensor_id' => $this->sensor->id,
            'raw_data'         => $this->faker->randomFloat(
                2,
                $this->sensor->min_value,
                $this->sensor->max_value
            ),
        ]);

        // action
        $job = new ProcessSmellyLog($log);
        $job->handle();

        // assertions
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
        $log = SmellyLog::create([
            'smelly_sensor_id' => $this->sensor->id,
            'raw_data'         => $this->sensor->critical_value,
        ]);

        // action
        $job = new ProcessSmellyLog($log);
        $job->handle();

        // assertions
        $log->refresh();

        $value = $this->calcValue($log->raw_data);

        // check log
        $this->assertEquals($value, $log->value);
        $this->assertEquals(1, $log->is_trigger_alert);

        // check report
        $report = SmellyDailyReport
            ::where('smelly_sensor_id', $this->sensor->id)
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
            switch ($i) {
                case 0:
                case 1:
                    $rawData = $this->sensor->critical_value;
                    break;
                case 2:
                    $rawData = $this->sensor->max_value;
                    break;
            }

            $log = SmellyLog::create([
                'smelly_sensor_id' => $this->sensor->id,
                'raw_data'         => $rawData,
                'created_at'       => $createdAt,
                'updated_at'       => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            // action
            $job = new ProcessSmellyLog($log);
            $job->handle();
        }

        // assertions
        // check logs
        $newLogs = SmellyLog::whereIn('id', $logs->pluck('id'))->get();
        foreach ($newLogs as $log) {
            $value = $this->calcValue($log->raw_data);

            $this->assertEquals($value, $log->value);
            $this->assertEquals(1, $log->is_trigger_alert);
        }

        $latestLog = $newLogs->sortByDesc('created_at')->first();
        $this->assertEquals(1, $latestLog->is_trigger_notification);

        // check report
        $report = SmellyDailyReport
            ::where('smelly_sensor_id', $this->sensor->id)
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
        $createdAt = Carbon::parse('now -30 minutes', config('app.timezone'));
        $rawData   = 0;

        for ($i = 0; $i < 3; $i++) {
            switch ($i) {
                case 0:
                case 1:
                    $rawData = $this->sensor->critical_value;
                    break;
                case 2:
                    $rawData = $this->sensor->max_value;
                    break;
            }

            $log = SmellyLog::create([
                'smelly_sensor_id' => $this->sensor->id,
                'raw_data'         => $rawData,
                'created_at'       => $createdAt,
                'updated_at'       => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            // action
            $job = new ProcessSmellyLog($log);
            $job->handle();
        }

        // assertions
        // check logs
        $newLogs = SmellyLog::whereIn('id', $logs->pluck('id'))->get();
        foreach ($newLogs as $log) {
            $value = $this->calcValue($log->raw_data);

            $this->assertEquals($value, $log->value);
            $this->assertEquals(1, $log->is_trigger_alert);
        }

        $latestLog = $newLogs->sortByDesc('created_at')->first();
        $this->assertEquals(1, $latestLog->is_trigger_notification);

        // check report
        $report = SmellyDailyReport
            ::where('smelly_sensor_id', $this->sensor->id)
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
            ->where('triggerable_type', SmellySensor::class)
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
        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('now -30 minutes', config('app.timezone'));
        $rawData   = 0;

        for ($i = 0; $i < 4; $i++) {
            switch ($i) {
                case 0:
                case 1:
                    $rawData = $this->sensor->critical_value;
                    break;
                case 2:
                    $rawData = $this->sensor->max_value;
                    break;
                case 3:
                    $rawData = bcsub($this->sensor->critical_value, 0.1, 2);
                    break;
            }

            $log = SmellyLog::create([
                'smelly_sensor_id' => $this->sensor->id,
                'raw_data'         => $rawData,
                'created_at'       => $createdAt,
                'updated_at'       => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            // action
            $job = new ProcessSmellyLog($log);
            $job->handle();
        }

        // assertions
        // check logs
        $newLogs   = SmellyLog::whereIn('id', $logs->pluck('id'))->get();
        $latestLog = $newLogs->sortByDesc('created_at')->first();

        $this->assertEquals(0, $log->is_trigger_alert);
        $this->assertEquals(0, $latestLog->is_trigger_notification);
        $this->assertEquals(0, $log->is_trigger_abnormal);
        $this->assertEquals(1, $latestLog->is_trigger_improvement);

        // check report
        $report = SmellyDailyReport
            ::where('smelly_sensor_id', $this->sensor->id)
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
            ->where('triggerable_type', SmellySensor::class)
            ->where('is_improved', '=', 1)
            ->first();

        $this->assertNotNull($abnormal);
        $this->assertEquals($log->created_at, $abnormal->improved_at);
    }
}
