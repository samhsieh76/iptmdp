<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\ProcessToiletPaperLog;
use App\Models\ToiletPaperDailyReport;
use App\Models\ToiletPaperLog;
use App\Models\ToiletPaperSensor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ToiletPaperDailyReportProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected $sensors = null;
    protected $date    = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            'Database\\Seeders\\BaseSeeder',
            'Database\\Seeders\\LocationSeeder',
            'Database\\Seeders\\ToiletSeeder',
            'Database\\Seeders\\ToiletPaperSensorSeeder',
        ]);

        $this->sensors = ToiletPaperSensor::all()->random(3);
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
            $rawData   = bcsub($sensor->max_value, 2.0, 2);

            for ($i = 0; $i < 100; $i++) {
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
        }

        Artisan::call('DailyReportProcess:ToiletPaper', ['--date' => $this->date->format('Y-m-d')]);

        $reports = ToiletPaperDailyReport
            ::whereIn('toilet_paper_sensor_id', $this->sensors->pluck('id'))
            ->where('date', $this->date->format('Y-m-d'))
            ->get();

        $newLogs = ToiletPaperLog::whereIn('id', $logs->pluck('id'))->get();

        $this->assertEquals($this->sensors->count(), $reports->count());

        foreach ($reports as $report) {
            $newLogsBySensor = $newLogs->where('toilet_paper_sensor_id', $report->toilet_paper_sensor_id);

            $averageValue = round($newLogsBySensor->avg('value'), 2);

            $this->assertEquals($averageValue, $report->average_value);

            $this->assertEquals($newLogsBySensor->sum('is_trigger_notification'), $report->notification_times);
            $this->assertEquals($newLogsBySensor->sum('is_trigger_abnormal'), $report->abnormal_times);
            $this->assertEquals($newLogsBySensor->sum('is_trigger_improvement'), $report->improvement_times);
        }
    }
}
