<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemperatureDailyReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temperature_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temperature_sensor_id');
            $table->date('date');
            $table->decimal('average_value', 5, 2)->default(0.0)->comment('溫度平均值');
            $table->boolean('is_active')->default(1)->comment('感測器運作狀況');
            $table->timestamps();

            $table->unique(['temperature_sensor_id', 'date'], 'temperature_daily_reports_sensor_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('temperature_daily_reports');
    }
}
