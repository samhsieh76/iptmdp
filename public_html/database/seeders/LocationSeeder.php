<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();
        $counties = County
            ::all()
            ->each(function ($county) use ($users) {
                Location::factory(2)->create([
                    'county_id'         => $county->id,
                    'administration_id' => $users->random()->id,
                ]);
            });
    }
}
