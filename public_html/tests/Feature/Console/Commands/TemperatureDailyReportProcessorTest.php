<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\ProcessTemperatureLog;
use App\Models\TemperatureDailyReport;
use App\Models\TemperatureLog;
use App\Models\TemperatureSensor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TemperatureDailyReportProcessorTest extends TestCase
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
            'Database\\Seeders\\TemperatureSensorSeeder',
        ]);

        $this->sensors = TemperatureSensor::all()->random(3);
        $this->date    = Carbon::parse('today');
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
            $createdAt = Carbon::parse('today midnight +3 sec');
            $rawData   = $this->faker->randomFloat(2, 0.0, 100.0);

            for ($i = 0; $i < 100; $i++) {
                $log = TemperatureLog::create([
                    'temperature_sensor_id' => $sensor->id,
                    'raw_data'              => $rawData,
                    'created_at'            => $createdAt,
                    'updated_at'            => $createdAt,
                ]);

                $createdAt->addMinutes(5);
                $rawData = $this->faker->randomFloat(2, 0.0, 100.0);

                $logs->push($log);

                $job = new ProcessTemperatureLog($log);
                $job->handle();
            }
        }

        Artisan::call('DailyReportProcess:Temperature', ['--date' => $this->date->format('Y-m-d')]);

        $reports = TemperatureDailyReport
            ::whereIn('temperature_sensor_id', $this->sensors->pluck('id'))
            ->where('date', $this->date->format('Y-m-d'))
            ->get();

        $newLogs = TemperatureLog::whereIn('id', $logs->pluck('id'))->get();

        $this->assertEquals($this->sensors->count(), $reports->count());

        foreach ($reports as $report) {
            $newLogsBySensor = $newLogs->where('temperature_sensor_id', $report->temperature_sensor_id);

            $averageValue = round($newLogsBySensor->avg('value'), 2);

            $this->assertEquals($averageValue, $report->average_value);
        }
    }
}
