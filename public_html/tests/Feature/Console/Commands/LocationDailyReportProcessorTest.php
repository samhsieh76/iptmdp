<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\ProcessHandLotionLog;
use App\Jobs\ProcessHumanTrafficLog;
use App\Jobs\ProcessRelativeHumidityLog;
use App\Jobs\ProcessSmellyLog;
use App\Jobs\ProcessTemperatureLog;
use App\Jobs\ProcessToiletPaperLog;
use App\Models\HandLotionLog;
use App\Models\HandLotionSensor;
use App\Models\HumanTrafficLog;
use App\Models\HumanTrafficSensor;
use App\Models\Location;
use App\Models\RelativeHumidityLog;
use App\Models\SmellyLog;
use App\Models\SmellySensor;
use App\Models\TemperatureLog;
use App\Models\TemperatureSensor;
use App\Models\ToiletPaperLog;
use App\Models\ToiletPaperSensor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LocationDailyReportProcessorTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $sensor       = null;
    protected $activeDate   = null;
    protected $inActiveDate = null;

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

        $this->activeDate   = Carbon::parse('now -15 mins', config('app.timezone'));
        $this->inActiveDate = Carbon::parse('now -100 mins', config('app.timezone'));
    }

    public function test_generate_daily_report()
    {
        $now = Carbon::parse('now', config('app.timezone'));

        $sensorTypes = [
            'toiletPaper',
            'smelly',
            'humanTraffic',
            'handLotion',
            'temperature',
            'relativeHumidity',
        ];

        foreach ($sensorTypes as $sensorType) {
            $fnName = sprintf("%sSensorLogSeed", $sensorType);
            $this->$fnName();
        }

        Artisan::call('Sensor:ActiveCheck');
        Artisan::call('DailyReportProcess:Location');

        $locations = Location
            ::with([
                'dailyReports' => function ($query) use ($now) {
                    $query->where('date', $now->format('Y-m-d'));
                },
                'toilets' => function ($query) {
                    $query->select([
                        'id',
                        'location_id',
                        'name',
                    ]);
                },
                'toilets.toiletPaperSensors' => function ($query) use ($now) {
                    $query
                        ->select([
                            'toilet_paper_sensors.id',
                            'toilet_paper_sensors.toilet_id',
                            'toilet_paper_sensors.name',
                            'R.date',
                            'R.is_active',
                        ])
                        ->leftJoin(
                            'toilet_paper_daily_reports as R',
                            function ($join) use ($now) {
                                $join
                                    ->on('R.toilet_paper_sensor_id', '=', 'toilet_paper_sensors.id')
                                    ->where('date', $now->format('Y-m-d'));
                            }
                        );
                },
                'toilets.handLotionSensors' => function ($query) use ($now) {
                    $query
                        ->select([
                            'hand_lotion_sensors.id',
                            'hand_lotion_sensors.toilet_id',
                            'hand_lotion_sensors.name',
                            'R.date',
                            'R.is_active',
                        ])
                        ->leftJoin(
                            'hand_lotion_daily_reports as R',
                            function ($join) use ($now) {
                                $join
                                    ->on('R.hand_lotion_sensor_id', '=', 'hand_lotion_sensors.id')
                                    ->where('date', $now->format('Y-m-d'));
                            }
                        );
                },
                'toilets.humanTrafficSensors' => function ($query) use ($now) {
                    $query
                        ->select([
                            'human_traffic_sensors.id',
                            'human_traffic_sensors.toilet_id',
                            'human_traffic_sensors.name',
                            'R.date',
                            'R.is_active',
                        ])
                        ->leftJoin(
                            'human_traffic_daily_reports as R',
                            function ($join) use ($now) {
                                $join
                                    ->on('R.human_traffic_sensor_id', '=', 'human_traffic_sensors.id')
                                    ->where('date', $now->format('Y-m-d'));
                            }
                        );
                },
                'toilets.smellySensors' => function ($query) use ($now) {
                    $query
                        ->select([
                            'smelly_sensors.id',
                            'smelly_sensors.toilet_id',
                            'smelly_sensors.name',
                            'R.date',
                            'R.is_active',
                        ])
                        ->leftJoin(
                            'smelly_daily_reports as R',
                            function ($join) use ($now) {
                                $join
                                    ->on('R.smelly_sensor_id', '=', 'smelly_sensors.id')
                                    ->where('date', $now->format('Y-m-d'));
                            }
                        );
                },
                // with relative_humidity_sensors
                'toilets.temperatureSensors' => function ($query) use ($now) {
                    $query
                        ->select([
                            'temperature_sensors.id',
                            'temperature_sensors.toilet_id',
                            'temperature_sensors.name',
                            'R.date',
                            'R.is_active',
                        ])
                        ->leftJoin(
                            'temperature_daily_reports as R',
                            function ($join) use ($now) {
                                $join
                                    ->on('R.temperature_sensor_id', '=', 'temperature_sensors.id')
                                    ->where('date', $now->format('Y-m-d'));
                            }
                        );
                },
                // possible other sensors
            ])
            ->select([
                'id',
                'name',
            ])
            ->get();

        foreach ($locations as $location) {
            if ($location->toilets->count() == 0) {
                continue;
            }

            foreach ($location->toilets as $toilet) {
                $report = $location->dailyReports->where('toilet_id', $toilet->id)->first();
                $this->assertNotNull($report);

                $totalSensorCount  = 0;
                $actingSensorCount = 0;
                $actingPercentage  = 0.0;

                foreach ($sensorTypes as $sensorType) {
                    $attrName = sprintf("%sSensors", $sensorType);

                    $sensors = $toilet->$attrName;

                    if (empty($sensors)) {
                        continue;
                    }

                    $totalSensorCount  += $sensors->count();
                    $actingSensorCount += $sensors->filter(function ($value) {
                        return $value->is_active == 1;
                    })->count();
                }

                if ($totalSensorCount > 0) {
                    $actingPercentage = bcmul(bcdiv($actingSensorCount, $totalSensorCount, 4), 100, 2);
                }

                $this->assertEquals($totalSensorCount, $report->total_sensor_count);
                $this->assertEquals($actingSensorCount, $report->acting_sensor_count);
                $this->assertEquals($actingPercentage, $report->acting_percentage);
            }
        }
    }

    protected function toiletPaperSensorLogSeed()
    {
        ToiletPaperSensor
            ::all()
            ->each(function ($sensor) {
                $createdAt = $this->faker->randomElement([$this->activeDate, $this->inActiveDate]);

                $log = ToiletPaperLog::create([
                    'toilet_paper_sensor_id' => $sensor->id,
                    'raw_data'               => $this->faker->randomFloat(2, 3.0, 25.0),
                    'created_at'             => $createdAt,
                    'updated_at'             => $createdAt,
                ]);

                $job = new ProcessToiletPaperLog($log);
                $job->handle();
            });

        return true;
    }

    protected function handLotionSensorLogSeed()
    {
        HandLotionSensor
            ::all()
            ->each(function ($sensor) {
                $createdAt = $this->faker->randomElement([$this->activeDate, $this->inActiveDate]);

                $log = HandLotionLog::create([
                    'hand_lotion_sensor_id' => $sensor->id,
                    'raw_data'              => $this->faker->randomElement([0, 1]),
                    'created_at'            => $createdAt,
                    'updated_at'            => $createdAt,
                ]);

                $job = new ProcessHandLotionLog($log);
                $job->handle();
            });

        return true;
    }

    protected function humanTrafficSensorLogSeed()
    {
        HumanTrafficSensor
            ::all()
            ->each(function ($sensor) {
                $createdAt = $this->faker->randomElement([$this->activeDate, $this->inActiveDate]);

                $log = HumanTrafficLog::create([
                    'human_traffic_sensor_id' => $sensor->id,
                    'raw_data'                => $sensor->critical_value,
                    'created_at'              => $createdAt,
                    'updated_at'              => $createdAt,
                ]);

                $job = new ProcessHumanTrafficLog($log);
                $job->handle();
            });

        return true;
    }

    protected function smellySensorLogSeed()
    {
        SmellySensor
            ::all()
            ->each(function ($sensor) {
                $createdAt = $this->faker->randomElement([$this->activeDate, $this->inActiveDate]);

                $log = SmellyLog::create([
                    'smelly_sensor_id' => $sensor->id,
                    'raw_data'         => $this->faker->randomFloat(2, 3.0, 30.0),
                    'created_at'       => $createdAt,
                    'updated_at'       => $createdAt,
                ]);

                $job = new ProcessSmellyLog($log);
                $job->handle();
            });

        return true;
    }

    protected function temperatureSensorLogSeed()
    {
        TemperatureSensor
            ::all()
            ->each(function ($sensor) {
                $createdAt = $this->faker->randomElement([$this->activeDate, $this->inActiveDate]);

                $log = TemperatureLog::create([
                    'temperature_sensor_id' => $sensor->id,
                    'raw_data'              => $this->faker->randomFloat(2, 0.0, 100.0),
                    'created_at'            => $createdAt,
                    'updated_at'            => $createdAt,
                ]);

                $job = new ProcessTemperatureLog($log);
                $job->handle();
            });

        return true;
    }

    protected function relativeHumiditySensorLogSeed()
    {
        TemperatureSensor
            ::all()
            ->each(function ($sensor) {
                $createdAt = $this->faker->randomElement([$this->activeDate, $this->inActiveDate]);

                $log = RelativeHumidityLog::create([
                    'relative_humidity_sensor_id' => $sensor->id,
                    'raw_data'                    => $this->faker->randomFloat(2, 0.0, 100.0),
                    'created_at'                  => $createdAt,
                    'updated_at'                  => $createdAt,
                ]);

                $job = new ProcessRelativeHumidityLog($log);
                $job->handle();
            });

        return true;
    }
}
