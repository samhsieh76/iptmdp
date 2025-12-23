<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessTemperatureLog;
use App\Models\TemperatureLog;
use App\Models\TemperatureSensor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProcessTemperatureLogTest extends TestCase
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
            'Database\\Seeders\\TemperatureSensorSeeder',
        ]);

        $this->sensor = TemperatureSensor::all()->random();
        $this->date   = Carbon::parse('today');
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
        $log = TemperatureLog::create([
            'temperature_sensor_id' => $this->sensor->id,
            'raw_data'              => $this->faker->randomFloat(2, 0.0, 100.0),
        ]);

        // action
        $job = new ProcessTemperatureLog($log);
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
}
