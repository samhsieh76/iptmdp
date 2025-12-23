<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessHumanTrafficLog;
use App\Models\HumanTrafficDailyReport;
use App\Models\HumanTrafficLog;
use App\Models\HumanTrafficSensor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessHumanTrafficLogTest extends TestCase
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
            'Database\\Seeders\\HumanTrafficSensorSeeder',
        ]);

        $this->sensor = HumanTrafficSensor::all()->random();
        $this->date   = Carbon::parse('today', config('app.timezone'));

        // To test filter
        $this->sensor->min_value = 3;
        $this->sensor->save();
    }

    private function calcValue($raw)
    {
        $min = $this->sensor->min_value;

        $value = ($raw > $min) ? $raw : 0;
        $value = ceil(($value / 2));

        return $value;
    }

    /**
     * Calculate value by sensor logs
     *
     * @return void
     */
    public function test_calculate_value()
    {
        $logs      = new Collection();
        $createdAt = Carbon::parse('now -30 minutes', config('app.timezone'));

        // preprae data
        for ($i = 0; $i < 2; $i ++) {
            $log = HumanTrafficLog::create([
                'human_traffic_sensor_id' => $this->sensor->id,
                'raw_data'                => $this->sensor->min_value + 1,
                'created_at'              => $createdAt,
                'updated_at'              => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            // action
            $job = new ProcessHumanTrafficLog($log);
            $job->handle();
        }

        // check logs
        $newLogs = HumanTrafficLog::whereIn('id', $logs->pluck('id'))->orderBy('created_at')->get();
        foreach ($newLogs as $log) {
            $value = $this->calcValue($log->raw_data);

            $this->assertEquals($value, $log->value);
        }

        // check report
        $summary = $newLogs->sum('value');

        $report = HumanTrafficDailyReport
            ::where('human_traffic_sensor_id', $this->sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertEquals($summary, $report->summary_value);

        // check sensor latest value
        $log = $newLogs->pop();
        $this->sensor->refresh();

        $this->assertEquals($log->raw_data, $this->sensor->latest_raw_data);
        $this->assertEquals($log->value, $this->sensor->latest_value);
        $this->assertEquals($log->created_at, $this->sensor->latest_updated_at);
    }

    /**
     * Calculate value by sensor logs
     * Input value less equal than min, expect rocord as 0
     *
     * @return void
     */
    public function test_calculate_value_less_than_min()
    {
        $logs      = new Collection();
        $createdAt = Carbon::parse('now -30 minutes', config('app.timezone'));

        // preprae data
        for ($i = 0; $i < 2; $i ++) {
            $log = HumanTrafficLog::create([
                'human_traffic_sensor_id' => $this->sensor->id,
                'raw_data'                => $this->sensor->min_value,
                'created_at'              => $createdAt,
                'updated_at'              => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            // action
            $job = new ProcessHumanTrafficLog($log);
            $job->handle();
        }

        // check logs
        $newLogs = HumanTrafficLog::whereIn('id', $logs->pluck('id'))->orderBy('created_at')->get();
        foreach ($newLogs as $log) {
            $value = $this->calcValue($log->raw_data);

            $this->assertEquals($value, $log->value);
        }

        // check report
        $summary = $newLogs->sum('value');

        $report = HumanTrafficDailyReport
            ::where('human_traffic_sensor_id', $this->sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertEquals($summary, $report->summary_value);

        // check sensor latest value
        $log = $newLogs->pop();
        $this->sensor->refresh();

        $this->assertEquals($log->raw_data, $this->sensor->latest_raw_data);
        $this->assertEquals(0, $this->sensor->latest_value);
        $this->assertEquals($log->created_at, $this->sensor->latest_updated_at);
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
        $rawData   = $this->sensor->critical_value;

        for ($i = 0; $i < 4; $i++) {
            $log = HumanTrafficLog::create([
                'human_traffic_sensor_id' => $this->sensor->id,
                'raw_data'                => $rawData,
                'created_at'              => $createdAt,
                'updated_at'              => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            // action
            $job = new ProcessHumanTrafficLog($log);
            $job->handle();
        }

        // check logs
        $newLogs = HumanTrafficLog::whereIn('id', $logs->pluck('id'))->get();
        foreach ($newLogs as $key => $log) {
            $value = $this->calcValue($log->raw_data);

            $this->assertEquals($value, $log->value);
            $this->assertEquals(($key % 2), $log->is_trigger_notification);
        }

        // check report
        $report = HumanTrafficDailyReport
            ::where('human_traffic_sensor_id', $this->sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertEquals(2, $report->notification_times);
        $this->assertEquals(1, $report->is_under_notification);

        // check notification
        Queue::assertPushed(SendQueuedNotifications::class);
    }
}
