<?php

namespace Database\Seeders;

use App\Models\SmellyLog;
use App\Models\SmellySensor;
use Illuminate\Database\Seeder;

class SmellyLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SmellySensor
            ::all()
            ->each(function ($sensor) {
                SmellyLog::factory(3)->create([
                    'smelly_sensor_id' => $sensor->id,
                ]);
            });
    }
}
