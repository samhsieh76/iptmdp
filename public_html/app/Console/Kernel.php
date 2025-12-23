<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $sensorTypes = [
            'ToiletPaper',
            'HandLotion',
            'HumanTraffic',
            'Smelly',
            'Temperature',
            'RelativeHumidity',
        ];

        foreach ($sensorTypes as $sensorType) {
            $command = sprintf("DailyReportProcess:%s", $sensorType);
            // lock for running instance set to 60 mins
            $schedule->command($command)->everyThirtyMinutes()->withoutOverlapping(60);
        }

        $schedule->command('Sensor:ActiveCheck')->everyFifteenMinutes()->withoutOverlapping(30);
        $schedule->command('DailyReportProcess:Location')->everyThirtyMinutes()->withoutOverlapping(60);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
