<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\ProcessHandLotionLog;
use App\Jobs\ProcessHumanTrafficLog;
use App\Jobs\ProcessRelativeHumidityLog;
use App\Jobs\ProcessSmellyLog;
use App\Jobs\ProcessTemperatureLog;
use App\Jobs\ProcessToiletPaperLog;
use App\Models\HandLotionDailyReport;
use App\Models\HandLotionLog;
use App\Models\HandLotionSensor;
use App\Models\HumanTrafficDailyReport;
use App\Models\HumanTrafficLog;
use App\Models\HumanTrafficSensor;
use App\Models\RelativeHumidityDailyReport;
use App\Models\RelativeHumidityLog;
use App\Models\SmellyDailyReport;
use App\Models\SmellyLog;
use App\Models\SmellySensor;
use App\Models\TemperatureDailyReport;
use App\Models\TemperatureLog;
use App\Models\TemperatureSensor;
use App\Models\ToiletPaperDailyReport;
use App\Models\ToiletPaperLog;
use App\Models\ToiletPaperSensor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SensorActiveCheckTest extends TestCase
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
            'Database\\Seeders\\ToiletPaperSensorSeeder',
            'Database\\Seeders\\SmellySensorSeeder',
            'Database\\Seeders\\HumanTrafficSensorSeeder',
            'Database\\Seeders\\HandLotionSensorSeeder',
            'Database\\Seeders\\TemperatureSensorSeeder',
        ]);

        $this->date = Carbon::parse('today', config('app.timezone'));
    }

    /**
     * ToiletPaperSensor active in 30 mins.
     *
     * @return void
     */
    public function test_toilet_paper_sensor_is_active()
    {
        $sensor = ToiletPaperSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -12 hours', config('app.timezone'));
        $rawData   = bcsub($sensor->max_value, 2.0, 2);

        for ($i = 0; $i < 3; $i++) {
            $log = ToiletPaperLog::create([
                'toilet_paper_sensor_id' => $sensor->id,
                'raw_data'               => $rawData,
                'created_at'             => $createdAt,
                'updated_at'             => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = bcadd($rawData, 0.3, 2);
            if ($rawData >= $sensor->max_value) {
                $rawData = bcadd($sensor->min_value, 0.2, 2);
            }

            $logs->push($log);

            $job = new ProcessToiletPaperLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = ToiletPaperDailyReport
            ::where('toilet_paper_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(1, $report->is_active);
    }

    /**
     * ToiletPaperSensor. Last update exceed 30 mins.
     *
     * @return void
     */
    public function test_toilet_paper_sensor_is_inactive()
    {
        $sensor = ToiletPaperSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -13 hours', config('app.timezone'));
        $rawData   = bcsub($sensor->max_value, 2.0, 2);

        for ($i = 0; $i < 3; $i++) {
            $log = ToiletPaperLog::create([
                'toilet_paper_sensor_id' => $sensor->id,
                'raw_data'               => $rawData,
                'created_at'             => $createdAt,
                'updated_at'             => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = bcadd($rawData, 0.3, 2);
            if ($rawData >= $sensor->max_value) {
                $rawData = bcadd($sensor->min_value, 0.2, 2);
            }

            $logs->push($log);

            $job = new ProcessToiletPaperLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = ToiletPaperDailyReport
            ::where('toilet_paper_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0, $report->is_active);
    }

    /**
     * ToiletPaperSensor. Last update exceed 30 mins.
     * Recover from inactive.
     *
     * @return void
     */
    public function test_toilet_paper_recover_from_inactive()
    {
        $sensor = ToiletPaperSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -13 hours', config('app.timezone'));
        $rawData   = bcsub($sensor->max_value, 2.0, 2);

        for ($i = 0; $i < 3; $i++) {
            $log = ToiletPaperLog::create([
                'toilet_paper_sensor_id' => $sensor->id,
                'raw_data'               => $rawData,
                'created_at'             => $createdAt,
                'updated_at'             => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = bcadd($rawData, 0.3, 2);
            if ($rawData >= $sensor->max_value) {
                $rawData = bcadd($sensor->min_value, 0.2, 2);
            }

            $logs->push($log);

            $job = new ProcessToiletPaperLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = ToiletPaperDailyReport
            ::where('toilet_paper_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0, $report->is_active);

        $createdAt = Carbon::parse('today 12:00:00 -12 hours', config('app.timezone'));

        for ($i = 0; $i < 3; $i++) {
            $log = ToiletPaperLog::create([
                'toilet_paper_sensor_id' => $sensor->id,
                'raw_data'               => $rawData,
                'created_at'             => $createdAt,
                'updated_at'             => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = bcadd($rawData, 0.3, 2);
            if ($rawData >= $sensor->max_value) {
                $rawData = bcadd($sensor->min_value, 0.2, 2);
            }

            $logs->push($log);

            $job = new ProcessToiletPaperLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report->refresh();

        $this->assertEquals(1, $report->is_active);
    }

    /**
     * SmellySensor active in 30 mins.
     *
     * @return void
     */
    public function test_smelly_sensor_is_active()
    {
        $sensor = SmellySensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -12 hours', config('app.timezone'));
        $rawData   = $this->faker->randomFloat(2, 0.0, 30.0);

        for ($i = 0; $i < 3; $i++) {
            $log = SmellyLog::create([
                'smelly_sensor_id' => $sensor->id,
                'raw_data'         => $rawData,
                'created_at'       => $createdAt,
                'updated_at'       => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = $this->faker->randomFloat(2, 0.0, 30.0);

            $logs->push($log);

            $job = new ProcessSmellyLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = SmellyDailyReport
            ::where('smelly_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(1, $report->is_active);
    }

    /**
     * SmellySensor. Last update exceed 30 mins.
     *
     * @return void
     */
    public function test_smelly_sensor_is_inactive()
    {
        $sensor = SmellySensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -13 hours', config('app.timezone'));
        $rawData   = $this->faker->randomFloat(2, 0.0, 30.0);

        for ($i = 0; $i < 3; $i++) {
            $log = SmellyLog::create([
                'smelly_sensor_id' => $sensor->id,
                'raw_data'         => $rawData,
                'created_at'       => $createdAt,
                'updated_at'       => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = $this->faker->randomFloat(2, 0.0, 30.0);

            $logs->push($log);

            $job = new ProcessSmellyLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = SmellyDailyReport
            ::where('smelly_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0, $report->is_active);
    }

    /**
     * SmellySensor. Last update exceed 30 mins.
     * Recover from inactive.
     *
     * @return void
     */
    public function test_smelly_sensor_recover_from_inactive()
    {
        $sensor = SmellySensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -13 hours', config('app.timezone'));
        $rawData   = $this->faker->randomFloat(2, 0.0, 30.0);

        for ($i = 0; $i < 3; $i++) {
            $log = SmellyLog::create([
                'smelly_sensor_id' => $sensor->id,
                'raw_data'         => $rawData,
                'created_at'       => $createdAt,
                'updated_at'       => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = $this->faker->randomFloat(2, 0.0, 30.0);

            $logs->push($log);

            $job = new ProcessSmellyLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = SmellyDailyReport
            ::where('smelly_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0, $report->is_active);

        $createdAt = Carbon::parse('today 12:00:00 -12 hours', config('app.timezone'));

        for ($i = 0; $i < 3; $i++) {
            $log = SmellyLog::create([
                'smelly_sensor_id' => $sensor->id,
                'raw_data'         => $rawData,
                'created_at'       => $createdAt,
                'updated_at'       => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = $this->faker->randomFloat(2, 0.0, 30.0);

            $logs->push($log);

            $job = new ProcessSmellyLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = SmellyDailyReport
            ::where('smelly_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(1, $report->is_active);
    }

    /**
     * HumanTrafficSensor active in 30 mins.
     *
     * @return void
     */
    public function test_human_traffic_sensor_is_active()
    {
        $sensor = HumanTrafficSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -12 hours', config('app.timezone'));
        $rawData   = $sensor->critical_value;

        for ($i = 0; $i < 3; $i++) {
            $log = HumanTrafficLog::create([
                'human_traffic_sensor_id' => $sensor->id,
                'raw_data'                => $rawData,
                'created_at'              => $createdAt,
                'updated_at'              => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            $job = new ProcessHumanTrafficLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = HumanTrafficDailyReport
            ::where('human_traffic_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(1, $report->is_active);
    }

    /**
     * HumanTrafficSensor. Last update exceed 30 mins.
     *
     * @return void
     */
    public function test_human_traffic_sensor_is_inactive()
    {
        $sensor = HumanTrafficSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -13 hours', config('app.timezone'));
        $rawData   = $sensor->critical_value;

        for ($i = 0; $i < 3; $i++) {
            $log = HumanTrafficLog::create([
                'human_traffic_sensor_id' => $sensor->id,
                'raw_data'                => $rawData,
                'created_at'              => $createdAt,
                'updated_at'              => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            $job = new ProcessHumanTrafficLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = HumanTrafficDailyReport
            ::where('human_traffic_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0, $report->is_active);
    }

    /**
     * HumanTrafficSensor. Last update exceed 30 mins.
     * Recover from inactive.
     *
     * @return void
     */
    public function test_human_traffic_sensor_recover_from_inactive()
    {
        $sensor = HumanTrafficSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -13 hours', config('app.timezone'));
        $rawData   = $sensor->critical_value;

        for ($i = 0; $i < 3; $i++) {
            $log = HumanTrafficLog::create([
                'human_traffic_sensor_id' => $sensor->id,
                'raw_data'                => $rawData,
                'created_at'              => $createdAt,
                'updated_at'              => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            $job = new ProcessHumanTrafficLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = HumanTrafficDailyReport
            ::where('human_traffic_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0, $report->is_active);

        $createdAt = Carbon::parse('today 12:00:00 -12 hours', config('app.timezone'));

        for ($i = 0; $i < 3; $i++) {
            $log = HumanTrafficLog::create([
                'human_traffic_sensor_id' => $sensor->id,
                'raw_data'                => $rawData,
                'created_at'              => $createdAt,
                'updated_at'              => $createdAt,
            ]);

            $createdAt->addMinutes(5);

            $logs->push($log);

            $job = new ProcessHumanTrafficLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report->refresh();

        $this->assertEquals(1, $report->is_active);
    }

    /**
     * HandLotionSensor active in 30 mins.
     *
     * @return void
     */
    public function test_hand_lotion_sensor_is_active()
    {
        $sensor = HandLotionSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -12 hours', config('app.timezone'));
        $rawData   = $this->faker->randomElement([0, 1]);

        for ($i = 0; $i < 3; $i++) {
            $log = HandLotionLog::create([
                'hand_lotion_sensor_id' => $sensor->id,
                'raw_data'              => $rawData,
                'created_at'            => $createdAt,
                'updated_at'            => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = $this->faker->randomElement([0, 1]);

            $logs->push($log);

            $job = new ProcessHandLotionLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = HandLotionDailyReport
            ::where('hand_lotion_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(1, $report->is_active);
    }

    /**
     * HandLotionSensor. Last update exceed 30 mins.
     *
     * @return void
     */
    public function test_hand_lotion_sensor_is_inactive()
    {
        $sensor = HandLotionSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -13 hours', config('app.timezone'));
        $rawData   = $this->faker->randomElement([0, 1]);

        for ($i = 0; $i < 3; $i++) {
            $log = HandLotionLog::create([
                'hand_lotion_sensor_id' => $sensor->id,
                'raw_data'                => $rawData,
                'created_at'              => $createdAt,
                'updated_at'              => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData   = $this->faker->randomElement([0, 1]);

            $logs->push($log);

            $job = new ProcessHandLotionLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = HandLotionDailyReport
            ::where('hand_lotion_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0, $report->is_active);
    }

    /**
     * HandLotionSensor. Last update exceed 30 mins.
     * Recover from inactive.
     *
     * @return void
     */
    public function test_hand_lotion_sensor_recover_from_inactive()
    {
        $sensor = HandLotionSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -13 hours', config('app.timezone'));
        $rawData   = $this->faker->randomElement([0, 1]);

        for ($i = 0; $i < 3; $i++) {
            $log = HandLotionLog::create([
                'hand_lotion_sensor_id' => $sensor->id,
                'raw_data'                => $rawData,
                'created_at'              => $createdAt,
                'updated_at'              => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData   = $this->faker->randomElement([0, 1]);

            $logs->push($log);

            $job = new ProcessHandLotionLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = HandLotionDailyReport
            ::where('hand_lotion_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0, $report->is_active);

        $createdAt = Carbon::parse('today 12:00:00 -12 hours', config('app.timezone'));

        for ($i = 0; $i < 3; $i++) {
            $log = HandLotionLog::create([
                'hand_lotion_sensor_id' => $sensor->id,
                'raw_data'              => $rawData,
                'created_at'            => $createdAt,
                'updated_at'            => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = $this->faker->randomElement([0, 1]);

            $logs->push($log);

            $job = new ProcessHandLotionLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report->refresh();

        $this->assertEquals(1, $report->is_active);
    }

    /**
     * TemperatureSensor active in 30 mins.
     *
     * @return void
     */
    public function test_temperature_sensor_is_active()
    {
        $sensor = TemperatureSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -12 hours', config('app.timezone'));
        $rawData   = $this->faker->randomFloat(2, 0.0, 100.0);

        for ($i = 0; $i < 3; $i++) {
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

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = TemperatureDailyReport
            ::where('temperature_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(1, $report->is_active);
    }

    /**
     * TemperatureSensor. Last update exceed 30 mins.
     *
     * @return void
     */
    public function test_temperature_sensor_is_inactive()
    {
        $sensor = TemperatureSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -13 hours', config('app.timezone'));
        $rawData   = $this->faker->randomFloat(2, 0.0, 100.0);

        for ($i = 0; $i < 3; $i++) {
            $log = TemperatureLog::create([
                'temperature_sensor_id' => $sensor->id,
                'raw_data'              => $rawData,
                'created_at'            => $createdAt,
                'updated_at'            => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData   = $this->faker->randomFloat(2, 0.0, 100.0);

            $logs->push($log);

            $job = new ProcessTemperatureLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = TemperatureDailyReport
            ::where('temperature_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0, $report->is_active);
    }

    /**
     * TemperatureSensor. Last update exceed 30 mins.
     * Recover from inactive.
     *
     * @return void
     */
    public function test_temperature_sensor_recover_from_inactive()
    {
        $sensor = TemperatureSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -13 hours', config('app.timezone'));
        $rawData   = $this->faker->randomFloat(2, 0.0, 100.0);

        for ($i = 0; $i < 3; $i++) {
            $log = TemperatureLog::create([
                'temperature_sensor_id' => $sensor->id,
                'raw_data'              => $rawData,
                'created_at'            => $createdAt,
                'updated_at'            => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData   = $this->faker->randomFloat(2, 0.0, 100.0);

            $logs->push($log);

            $job = new ProcessTemperatureLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = TemperatureDailyReport
            ::where('temperature_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0, $report->is_active);

        $createdAt = Carbon::parse('today 12:00:00 -12 hours', config('app.timezone'));

        for ($i = 0; $i < 3; $i++) {
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

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report->refresh();

        $this->assertEquals(1, $report->is_active);
    }

    /**
     * RelativeHumiditySensor active in 30 mins.
     *
     * @return void
     */
    public function test_relative_humidity_sensor_is_active()
    {
        $sensor = TemperatureSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -12 hours', config('app.timezone'));
        $rawData   = $this->faker->randomFloat(2, 0.0, 100.0);

        for ($i = 0; $i < 3; $i++) {
            $log = RelativeHumidityLog::create([
                'relative_humidity_sensor_id' => $sensor->id,
                'raw_data'                    => $rawData,
                'created_at'                  => $createdAt,
                'updated_at'                  => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = $this->faker->randomFloat(2, 0.0, 100.0);

            $logs->push($log);

            $job = new ProcessRelativeHumidityLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = RelativeHumidityDailyReport
            ::where('relative_humidity_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(1, $report->is_active);
    }

    /**
     * RelativeHumiditySensor. Last update exceed 30 mins.
     *
     * @return void
     */
    public function test_relative_humidity_sensor_is_inactive()
    {
        $sensor = TemperatureSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -13 hours', config('app.timezone'));
        $rawData   = $this->faker->randomFloat(2, 0.0, 100.0);

        for ($i = 0; $i < 3; $i++) {
            $log = RelativeHumidityLog::create([
                'relative_humidity_sensor_id' => $sensor->id,
                'raw_data'                    => $rawData,
                'created_at'                  => $createdAt,
                'updated_at'                  => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = $this->faker->randomFloat(2, 0.0, 100.0);

            $logs->push($log);

            $job = new ProcessRelativeHumidityLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = RelativeHumidityDailyReport
            ::where('relative_humidity_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0, $report->is_active);
    }

    /**
     * RelativeHumiditySensor. Last update exceed 30 mins.
     * Recover from inactive.
     *
     * @return void
     */
    public function test_relative_humidity_sensor_recover_from_inactive()
    {
        $sensor = TemperatureSensor::all()->random();

        // preprae data
        $logs      = new Collection();
        $createdAt = Carbon::parse('today 12:00:00 -13 hours', config('app.timezone'));
        $rawData   = $this->faker->randomFloat(2, 0.0, 100.0);

        for ($i = 0; $i < 3; $i++) {
            $log = RelativeHumidityLog::create([
                'relative_humidity_sensor_id' => $sensor->id,
                'raw_data'                    => $rawData,
                'created_at'                  => $createdAt,
                'updated_at'                  => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = $this->faker->randomFloat(2, 0.0, 100.0);

            $logs->push($log);

            $job = new ProcessRelativeHumidityLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report = RelativeHumidityDailyReport
            ::where('relative_humidity_sensor_id', $sensor->id)
            ->where('date', $this->date->format('Y-m-d'))
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals(0, $report->is_active);

        $createdAt = Carbon::parse('today 12:00:00 -12 hours', config('app.timezone'));

        for ($i = 0; $i < 3; $i++) {
            $log = RelativeHumidityLog::create([
                'relative_humidity_sensor_id' => $sensor->id,
                'raw_data'                    => $rawData,
                'created_at'                  => $createdAt,
                'updated_at'                  => $createdAt,
            ]);

            $createdAt->addMinutes(5);
            $rawData = $this->faker->randomFloat(2, 0.0, 100.0);

            $logs->push($log);

            $job = new ProcessRelativeHumidityLog($log);
            $job->handle();
        }

        Artisan::call('Sensor:ActiveCheck', ['--time' => '12:00:00']);

        $report->refresh();

        $this->assertEquals(1, $report->is_active);
    }
}
