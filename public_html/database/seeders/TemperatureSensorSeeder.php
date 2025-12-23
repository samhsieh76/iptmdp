<?php

namespace Database\Seeders;

use App\Models\TemperatureSensor;
use App\Models\Toilet;
use Illuminate\Database\Seeder;

class TemperatureSensorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Toilet
            ::all()
            ->each(function ($toilet) {
                TemperatureSensor::factory(2)->create([
                    'toilet_id' => $toilet->id,
                ]);
            });
    }
}
