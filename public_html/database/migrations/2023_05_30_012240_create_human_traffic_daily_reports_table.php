<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHumanTrafficDailyReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('human_traffic_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('human_traffic_sensor_id');
            $table->date('date');
            $table->integer('summary_value')->default(0)->comment('本日總計人數');
            $table->boolean('is_active')->default(1)->comment('感測器運作狀況');
            $table->boolean('is_under_notification')->default(0)->comment('是否已送出 通報');
            $table->integer('notification_times')->default(0)->comment('本日發出 通報 次數');
            $table->timestamps();

            $table->unique(['human_traffic_sensor_id', 'date'], 'human_traffic_daily_reports_sensor_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('human_traffic_daily_reports');
    }
}
