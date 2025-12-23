<?php

namespace Database\Seeders;

use App\Models\HumanTrafficLog;
use App\Models\HumanTrafficSensor;
use Illuminate\Database\Seeder;

class HumanTrafficLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HumanTrafficSensor
            ::all()
            ->each(function ($sensor) {
                HumanTrafficLog::factory(3)->create([
                    'human_traffic_sensor_id' => $sensor->id,
                ]);
            });
    }
}
