<?php

namespace Database\Seeders;

use App\Models\Toilet;
use App\Models\ToiletPaperSensor;
use Illuminate\Database\Seeder;

class ToiletPaperSensorSeeder extends Seeder
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
                ToiletPaperSensor::factory(3)->create([
                    'toilet_id' => $toilet->id,
                ]);
            });
    }
}
