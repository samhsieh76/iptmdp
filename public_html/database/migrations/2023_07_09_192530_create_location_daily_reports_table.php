<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationDailyReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id');
            $table->foreignId('toilet_id');
            $table->date('date');
            $table->integer('acting_sensor_count')->default(0)->comment('正常運作的感測器數');
            $table->integer('total_sensor_count')->default(0)->comment('總感測器數');
            $table->decimal('acting_percentage', 5, 2)->default(0.0)->comment('區域營運分數');
            $table->timestamps();

            $table->unique(['location_id', 'toilet_id', 'date'], 'location_daily_reports_location_toilet_date_unique');
            $table->index(['location_id', 'date'], 'location_daily_reports_location_date_index');
            $table->index(['toilet_id', 'date'], 'location_daily_reports_toilet_date_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_daily_reports');
    }
}

