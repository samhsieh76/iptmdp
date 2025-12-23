<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\ProcessHandLotionLog;
use App\Models\HandLotionDailyReport;
use App\Models\HandLotionLog;
use App\Models\HandLotionSensor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HandLotionDailyReportProcessorTest extends TestCase
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
            'Database\\Seeders\\HandLotionSensorSeeder',
        ]);

        $this->sensors = HandLotionSensor::all()->random(3);
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
            $rawData   = $this->faker->randomElement([0, 1]);

            for ($i = 0; $i < 100; $i++) {
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
        }

        Artisan::call('DailyReportProcess:HandLotion', ['--date' => $this->date->format('Y-m-d')]);

        $reports = HandLotionDailyReport
            ::whereIn('hand_lotion_sensor_id', $this->sensors->pluck('id'))
            ->where('date', $this->date->format('Y-m-d'))
            ->get();

        $newLogs = HandLotionLog::whereIn('id', $logs->pluck('id'))->get();

        $this->assertEquals($this->sensors->count(), $reports->count());

        foreach ($reports as $report) {
            $newLogsBySensor = $newLogs->where('hand_lotion_sensor_id', $report->hand_lotion_sensor_id);

            $latest = $newLogsBySensor->sortByDesc('created_at')->first();

            $this->assertEquals($latest->value, $report->value);

            $this->assertEquals($newLogsBySensor->sum('is_trigger_notification'), $report->notification_times);
            $this->assertEquals($newLogsBySensor->sum('is_trigger_abnormal'), $report->abnormal_times);
            $this->assertEquals($newLogsBySensor->sum('is_trigger_improvement'), $report->improvement_times);
        }
    }
}
