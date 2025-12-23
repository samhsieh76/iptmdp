<?php

namespace Database\Seeders;

use App\Models\HumanTrafficSensor;
use App\Models\Toilet;
use Illuminate\Database\Seeder;

class HumanTrafficSensorSeeder extends Seeder
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
                HumanTrafficSensor::factory(1)->create([
                    'toilet_id' => $toilet->id,
                ]);
            });
    }
}
