<?php

namespace Database\Seeders;

use App\Models\Toilet;
use App\Models\HandLotionSensor;
use Illuminate\Database\Seeder;

class HandLotionSensorSeeder extends Seeder
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
                HandLotionSensor::factory(3)->create([
                    'toilet_id' => $toilet->id,
                ]);
            });
    }
}
