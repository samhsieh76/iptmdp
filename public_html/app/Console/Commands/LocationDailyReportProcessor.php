<?php

namespace App\Console\Commands;

use App\Models\HandLotionSensor;
use App\Models\HumanTrafficSensor;
use App\Models\Location;
use App\Models\LocationDailyReport;
use App\Models\SmellySensor;
use App\Models\TemperatureSensor;
use App\Models\ToiletPaperSensor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LocationDailyReportProcessor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'DailyReportProcess:Location {--date=today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process data for location daily report.';

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
        $bound = Carbon::parse('today 01:00:00', config('app.timezone'));
        $now   = Carbon::parse('now', config('app.timezone'));

        $dates = [];
        $dates[] = Carbon::parse($this->option('date'), config('app.timezone'));
        if ($now < $bound) {
            $dates[] = Carbon::parse('yesterday');
        }

        foreach ($dates as $date) {
            DB::beginTransaction();

            try {
                $toiletPaperSensorQuery = ToiletPaperSensor
                    ::select([
                        'toilet_paper_sensors.id',
                        'toilet_paper_sensors.toilet_id',
                        'toilet_paper_sensors.name',
                        'R.date',
                        'R.is_active',
                    ])
                    ->leftJoin(
                        'toilet_paper_daily_reports as R',
                        function ($join) use ($date) {
                            $join
                                ->on('R.toilet_paper_sensor_id', '=', 'toilet_paper_sensors.id')
                                ->where('date', $date->format('Y-m-d'));
                        }
                    );

                $handLotionSensorQuery = HandLotionSensor
                    ::select([
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

                $humanTrafficSensorQuery = HumanTrafficSensor
                    ::select([
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

                $smellySensorQuery = SmellySensor
                    ::select([
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

                $temperatureSensorQuery = TemperatureSensor
                    ::select([
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

                $sensorQuery = $toiletPaperSensorQuery
                    ->unionAll($handLotionSensorQuery)
                    ->unionAll($humanTrafficSensorQuery)
                    ->unionAll($smellySensorQuery)
                    ->unionAll($temperatureSensorQuery);

                $logs = Location
                    ::selectRaw('
                        locations.id AS location_id,
                        toilets.id AS toilet_id,
                        "' . $date->format('Y-m-d') . '" as date,
                        IFNULL(SUM(S.is_active), 0) AS acting_sensor_count,
                        COUNT(S.id) AS total_sensor_count,
                        ROUND(
                            IFNULL(
                                ((SUM(S.is_active) / COUNT(S.id)) * 100)
                            , 0)
                        , 2) AS acting_percentage
                    ')
                    ->join('toilets', function ($join) {
                        $join->on('toilets.location_id', '=', 'locations.id');
                    })
                    ->leftJoinSub($sensorQuery, 'S', function ($query) {
                        $query->on('toilets.id', '=', 'S.toilet_id');
                    })
                    ->groupBy([
                        'locations.id',
                        'toilets.id',
                    ])
                    ->get();

                LocationDailyReport::upsert(
                    $logs->toArray(),
                    ['location_id', 'toilet_id', 'date'],
                    [
                        'location_id',
                        'toilet_id',
                        'date',
                        'acting_sensor_count',
                        'total_sensor_count',
                        'acting_percentage',
                    ]
                );

                DB::commit();

                $this->line(sprintf("%s Done. Process %d daily_reports on %s.", __CLASS__, $logs->count(), $date->format('Y-m-d')));
            } catch (\Exception $e) {
                $this->error($e->getMessage());

                DB::rollBack();
            }
        }

        return 0;
    }
}
