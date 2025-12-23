<?php

namespace Database\Seeders;

use App\Models\RelativeHumidityLog;
use App\Models\TemperatureSensor;
use Illuminate\Database\Seeder;

class RelativeHumidityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TemperatureSensor
            ::all()
            ->each(function ($sensor) {
                RelativeHumidityLog::factory(3)->create([
                    'relative_humidity_sensor_id' => $sensor->id,
                ]);
            });
    }
}
