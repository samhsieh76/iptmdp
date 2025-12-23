<?php

namespace Database\Seeders;

use App\Models\RelativeHumiditySensor;
use App\Models\Toilet;
use Illuminate\Database\Seeder;

class RelativeHumiditySensorSeeder extends Seeder
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
                RelativeHumiditySensor::factory(2)->create([
                    'toilet_id' => $toilet->id,
                ]);
            });
    }
}
