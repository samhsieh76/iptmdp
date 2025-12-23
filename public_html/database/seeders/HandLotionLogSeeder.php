<?php

namespace Database\Seeders;

use App\Models\HandLotionLog;
use App\Models\HandLotionSensor;
use Illuminate\Database\Seeder;

class HandLotionLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HandLotionSensor
            ::all()
            ->each(function ($sensor) {
                HandLotionLog::factory(5)->create([
                    'hand_lotion_sensor_id' => $sensor->id,
                ]);
            });
    }
}
