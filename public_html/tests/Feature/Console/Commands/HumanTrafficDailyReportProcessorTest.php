<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\ProcessHumanTrafficLog;
use App\Models\HumanTrafficDailyReport;
use App\Models\HumanTrafficLog;
use App\Models\HumanTrafficSensor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HumanTrafficDailyReportProcessorTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $sensors = null;
    protected $date    = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            'Database\\Seeders\\BaseSeeder',
            'Database\\Seeders\\LocationSeeder',
            'Database\\Seeders\\ToiletSeeder',
            'Database\\Seeders\\HumanTrafficSensorSeeder',
        ]);

        $this->sensors = HumanTrafficSensor::all()->random(3);
        $this->date    = Carbon::parse('today', config('app.timezone'));
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_generate_daily_report()
    {
        // preprae data
        $logs      = new Collection();
        foreach ($this->sensors as $sensor) {
            $createdAt = Carbon::parse('today midnight +3 sec', config('app.timezone'));
            $rawData   = $this->faker->randomNumber(2);

            for ($i = 0; $i < 100; $i++) {
                $log = HumanTrafficLog::create([
                    'human_traffic_sensor_id' => $sensor->id,
                    'raw_data'                => $rawData,
                    'created_at'              => $createdAt,
                    'updated_at'              => $createdAt,
                ]);

                $createdAt->addMinutes(5);
                $rawData = $this->faker->randomNumber(2);

                $logs->push($log);

                $job = new ProcessHumanTrafficLog($log);
                $job->handle();
            }
        }

        Artisan::call('DailyReportProcess:HumanTraffic', ['--date' => $this->date->format('Y-m-d')]);

        $reports = HumanTrafficDailyReport
            ::whereIn('human_traffic_sensor_id', $this->sensors->pluck('id'))
            ->where('date', $this->date->format('Y-m-d'))
            ->get();

        $newLogs = HumanTrafficLog::whereIn('id', $logs->pluck('id'))->get();

        $this->assertEquals($this->sensors->count(), $reports->count());

        foreach ($reports as $report) {
            $newLogsBySensor = $newLogs->where('human_traffic_sensor_id', $report->human_traffic_sensor_id);

            $summaryValue = $newLogsBySensor->sum('value');

            $this->assertEquals($summaryValue, $report->summary_value);

            $this->assertEquals($newLogsBySensor->sum('is_trigger_notification'), $report->notification_times);
        }
    }
}
