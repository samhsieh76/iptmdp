<?php

namespace App\Console\Commands;

use App\Models\HandLotionDailyReport;
use App\Models\HandLotionSensor;
use App\Models\HumanTrafficDailyReport;
use App\Models\HumanTrafficSensor;
use App\Models\RelativeHumidityDailyReport;
use App\Models\SmellyDailyReport;
use App\Models\SmellySensor;
use App\Models\TemperatureDailyReport;
use App\Models\TemperatureSensor;
use App\Models\ToiletPaperDailyReport;
use App\Models\ToiletPaperSensor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SensorActiveCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Sensor:ActiveCheck {--time=now}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all sensors active status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $time = Carbon::parse($this->option('time'), config('app.timezone'));

        $this->toiletPaperSenserCheck($time);

        $this->smellySenserCheck($time);

        $this->humanTrafficSenserCheck($time);

        $this->handLotionSenserCheck($time);

        $this->temperatureSenserCheck($time);

        $this->relativeHumiditySenserCheck($time);

        return 0;
    }

    protected function toiletPaperSenserCheck($time)
    {
        $allSensors = ToiletPaperSensor
            ::with(['dailyReports' => function ($query) use ($time) {
                $query->where('date', $time->format('Y-m-d'));
            }])
            ->get();

        DB::beginTransaction();

        try {
            $inactiveReportIds = new Collection();
            $activeReportIds   = new Collection();

            foreach ($allSensors as $sensor) {
                // retrieve related report
                $report = $sensor->dailyReports->first();
                if (!$report) {
                    // create daily report if not exist
                    $report = ToiletPaperDailyReport::create([
                        'date'                   => $time->format('Y-m-d'),
                        'toilet_paper_sensor_id' => $sensor->id,
                    ]);
                }

                $isActive = intval(
                    !empty($sensor->latest_updated_at) and ($sensor->latest_updated_at >= (clone $time)->subHours(12))
                );

                if ($isActive == 0) {
                    $inactiveReportIds->push($report->id);
                } else {
                    $activeReportIds->push($report->id);
                }
            }

            ToiletPaperDailyReport::whereIn('id', $inactiveReportIds->toArray())->update(['is_active' => 0]);
            ToiletPaperDailyReport::whereIn('id', $activeReportIds->toArray())->update(['is_active' => 1]);

            DB::commit();
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            DB::rollBack();
        }

        $this->line(sprintf("%s Done. Process %d sensors set to inactive on %s.", __FUNCTION__, $inactiveReportIds->count(), $time->format('Y-m-d')));

        return true;
    }

    protected function smellySenserCheck($time)
    {
        $allSensors = SmellySensor
            ::with(['dailyReports' => function ($query) use ($time) {
                $query->where('date', $time->format('Y-m-d'));
            }])
            ->get();

        DB::beginTransaction();

        try {
            $inactiveReportIds = new Collection();
            $activeReportIds   = new Collection();

            foreach ($allSensors as $sensor) {
                // retrieve related report
                $report = $sensor->dailyReports->first();
                if (!$report) {
                    // create daily report if not exist
                    $report = SmellyDailyReport::create([
                        'date'             => $time->format('Y-m-d'),
                        'smelly_sensor_id' => $sensor->id,
                    ]);
                }

                $isActive = intval(
                    !empty($sensor->latest_updated_at) and ($sensor->latest_updated_at >= (clone $time)->subHours(12))
                );

                if ($isActive == 0) {
                    $inactiveReportIds->push($report->id);
                } else {
                    $activeReportIds->push($report->id);
                }
            }

            SmellyDailyReport::whereIn('id', $inactiveReportIds->toArray())->update(['is_active' => 0]);
            SmellyDailyReport::whereIn('id', $activeReportIds->toArray())->update(['is_active' => 1]);

            DB::commit();
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            DB::rollBack();
        }

        $this->line(sprintf("%s Done. Process %d sensors set to inactive on %s.", __FUNCTION__, $inactiveReportIds->count(), $time->format('Y-m-d')));

        return true;
    }

    protected function humanTrafficSenserCheck($time)
    {
        $allSensors = HumanTrafficSensor
            ::with(['dailyReports' => function ($query) use ($time) {
                $query->where('date', $time->format('Y-m-d'));
            }])
            ->get();

        DB::beginTransaction();

        try {
            $inactiveReportIds = new Collection();
            $activeReportIds   = new Collection();

            foreach ($allSensors as $sensor) {
                // retrieve related report
                $report = $sensor->dailyReports->first();
                if (!$report) {
                    // create daily report if not exist
                    $report = HumanTrafficDailyReport::create([
                        'date'                    => $time->format('Y-m-d'),
                        'human_traffic_sensor_id' => $sensor->id,
                    ]);
                }

                $isActive = intval(
                    !empty($sensor->latest_updated_at) and ($sensor->latest_updated_at >= (clone $time)->subHours(12))
                );

                if ($isActive == 0) {
                    $inactiveReportIds->push($report->id);
                } else {
                    $activeReportIds->push($report->id);
                }
            }

            HumanTrafficDailyReport::whereIn('id', $inactiveReportIds->toArray())->update(['is_active' => 0]);
            HumanTrafficDailyReport::whereIn('id', $activeReportIds->toArray())->update(['is_active' => 1]);

            DB::commit();
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            DB::rollBack();
        }

        $this->line(sprintf("%s Done. Process %d sensors set to inactive on %s.", __FUNCTION__, $inactiveReportIds->count(), $time->format('Y-m-d')));

        return true;
    }

    protected function handLotionSenserCheck($time)
    {
        $allSensors = HandLotionSensor
            ::with(['dailyReports' => function ($query) use ($time) {
                $query->where('date', $time->format('Y-m-d'));
            }])
            ->get();

        DB::beginTransaction();

        try {
            $inactiveReportIds = new Collection();
            $activeReportIds   = new Collection();

            foreach ($allSensors as $sensor) {
                // retrieve related report
                $report = $sensor->dailyReports->first();
                if (!$report) {
                    // create daily report if not exist
                    $report = HandLotionDailyReport::create([
                        'date'                  => $time->format('Y-m-d'),
                        'hand_lotion_sensor_id' => $sensor->id,
                    ]);
                }

                $isActive = intval(
                    !empty($sensor->latest_updated_at) and ($sensor->latest_updated_at >= (clone $time)->subHours(12))
                );

                if ($isActive == 0) {
                    $inactiveReportIds->push($report->id);
                } else {
                    $activeReportIds->push($report->id);
                }
            }

            HandLotionDailyReport::whereIn('id', $inactiveReportIds->toArray())->update(['is_active' => 0]);
            HandLotionDailyReport::whereIn('id', $activeReportIds->toArray())->update(['is_active' => 1]);

            DB::commit();
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            DB::rollBack();
        }

        $this->line(sprintf("%s Done. Process %d sensors set to inactive on %s.", __FUNCTION__, $inactiveReportIds->count(), $time->format('Y-m-d')));

        return true;
    }

    protected function temperatureSenserCheck($time)
    {
        $allSensors = TemperatureSensor
            ::with(['dailyReports' => function ($query) use ($time) {
                $query->where('date', $time->format('Y-m-d'));
            }])
            ->get();

        DB::beginTransaction();

        try {
            $inactiveReportIds = new Collection();
            $activeReportIds   = new Collection();

            foreach ($allSensors as $sensor) {
                // retrieve related report
                $report = $sensor->dailyReports->first();
                if (!$report) {
                    // create daily report if not exist
                    $report = TemperatureDailyReport::create([
                        'date'                  => $time->format('Y-m-d'),
                        'temperature_sensor_id' => $sensor->id,
                    ]);
                }

                $isActive = intval(
                    !empty($sensor->latest_updated_at) and ($sensor->latest_updated_at >= (clone $time)->subHours(12))
                );

                if ($isActive == 0) {
                    $inactiveReportIds->push($report->id);
                } else {
                    $activeReportIds->push($report->id);
                }
            }

            TemperatureDailyReport::whereIn('id', $inactiveReportIds->toArray())->update(['is_active' => 0]);
            TemperatureDailyReport::whereIn('id', $activeReportIds->toArray())->update(['is_active' => 1]);

            DB::commit();
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            DB::rollBack();
        }

        $this->line(sprintf("%s Done. Process %d sensors set to inactive on %s.", __FUNCTION__, $inactiveReportIds->count(), $time->format('Y-m-d')));

        return true;
    }

    protected function relativeHumiditySenserCheck($time)
    {
        $allSensors = TemperatureSensor
            ::with(['relativeHumidityDailyReports' => function ($query) use ($time) {
                $query->where('date', $time->format('Y-m-d'));
            }])
            ->get();

        DB::beginTransaction();

        try {
            $inactiveReportIds = new Collection();
            $activeReportIds   = new Collection();

            foreach ($allSensors as $sensor) {
                // retrieve related report
                $report = $sensor->relativeHumidityDailyReports->first();
                if (!$report) {
                    // create daily report if not exist
                    $report = RelativeHumidityDailyReport::create([
                        'date'                        => $time->format('Y-m-d'),
                        'relative_humidity_sensor_id' => $sensor->id,
                    ]);
                }


                $isActive = intval(
                    !empty($sensor->latest_humidity_updated_at) and ($sensor->latest_humidity_updated_at >= (clone $time)->subHours(12))
                );

                if ($isActive == 0) {
                    $inactiveReportIds->push($report->id);
                } else {
                    $activeReportIds->push($report->id);
                }
            }

            RelativeHumidityDailyReport::whereIn('id', $inactiveReportIds->toArray())->update(['is_active' => 0]);
            RelativeHumidityDailyReport::whereIn('id', $activeReportIds->toArray())->update(['is_active' => 1]);

            DB::commit();
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            DB::rollBack();
        }

        $this->line(sprintf("%s Done. Process %d sensors set to inactive on %s.", __FUNCTION__, $inactiveReportIds->count(), $time->format('Y-m-d')));

        return true;
    }
}
