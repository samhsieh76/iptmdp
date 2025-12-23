<?php

namespace Database\Seeders;

use App\Models\SmellySensor;
use App\Models\Toilet;
use Illuminate\Database\Seeder;

class SmellySensorSeeder extends Seeder
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
                SmellySensor::factory(1)->create([
                    'toilet_id' => $toilet->id,
                ]);
            });
    }
}
