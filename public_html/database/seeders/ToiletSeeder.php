<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Toilet;
use App\Models\User;
use Illuminate\Database\Seeder;

class ToiletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::all()->random();
        $location = Location::all()->random();

        Toilet::factory(10)->create([
            'location_id' => $location->id,
            'creator_id'  => $user->id,
        ]);
    }
}
