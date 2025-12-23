<?php

namespace Database\Seeders;

use App\Models\ToiletPaperLog;
use App\Models\ToiletPaperSensor;
use Illuminate\Database\Seeder;

class ToiletPaperLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ToiletPaperSensor
            ::all()
            ->each(function ($sensor) {
                ToiletPaperLog::factory(5)->create([
                    'toilet_paper_sensor_id' => $sensor->id,
                ]);
            });
    }
}
