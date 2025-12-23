<?php

namespace Database\Seeders;

use App\Models\TemperatureLog;
use App\Models\TemperatureSensor;
use Illuminate\Database\Seeder;

class TemperatureLogSeeder extends Seeder
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
                TemperatureLog::factory(3)->create([
                    'temperature_sensor_id' => $sensor->id,
                ]);
            });
    }
}
