<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Test data
     *
     * @return void
     */
    public function run() {
        $this->call([
            LocationSeeder::class,
            ToiletSeeder::class,
            // hand lotion
            HandLotionSensorSeeder::class,
            HandLotionLogSeeder::class,
            // human traffic
            HumanTrafficSensorSeeder::class,
            HumanTrafficLogSeeder::class,
            // smelly
            SmellySensorSeeder::class,
            SmellyLogSeeder::class,
            // toilet paper
            ToiletPaperSensorSeeder::class,
            ToiletPaperLogSeeder::class,
            // temperature
            TemperatureSensorSeeder::class,
            TemperatureLogSeeder::class,
            // relative humidity
            // RelativeHumiditySensorSeeder::class,
            RelativeHumidityLogSeeder::class,

            // grant permissions of location and supplier
            LocationAuditRecordSeeder::class,
        ]);
    }
}