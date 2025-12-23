<?php

namespace App\Console\Commands;

use App\Models\HumanTrafficDailyReport;
use App\Models\HumanTrafficLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HumanTrafficDailyReportProcessor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'DailyReportProcess:HumanTraffic  {--date=today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process data for human traffic daily report.';

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
                $logs = HumanTrafficLog
                    ::selectRaw('
                        human_traffic_sensor_id,
                        SUM(value) as summary_value,
                        "' . $date->format('Y-m-d') . '" as date,
                        SUM(is_trigger_notification) as notification_times
                    ')
                    ->whereBetween('created_at', [$date->format('Y-m-d 00:00:00'), $date->format('Y-m-d 23:59:59')])
                    ->groupBy('human_traffic_sensor_id')
                    ->get();

                HumanTrafficDailyReport::upsert(
                    $logs->toArray(),
                    ['human_traffic_sensor_id', 'date'],
                    [
                        'human_traffic_sensor_id',
                        'date',
                        'summary_value',
                        'notification_times',
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
