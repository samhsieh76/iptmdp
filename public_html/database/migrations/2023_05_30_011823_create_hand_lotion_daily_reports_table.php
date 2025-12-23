<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHandLotionDailyReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hand_lotion_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hand_lotion_sensor_id');
            $table->date('date');
            $table->boolean('value')->default(0)->comment('洗手液最後一次更新資料');
            $table->boolean('is_active')->default(1)->comment('感測器運作狀況');
            $table->boolean('is_under_notification')->default(0)->comment('是否已送出 通報');
            $table->integer('alert_times')->default(0)->comment('本日發出 警報 次數');
            $table->integer('notification_times')->default(0)->comment('本日發出 通報 次數');
            $table->integer('abnormal_times')->default(0)->comment('本日發出 異常 次數');
            $table->integer('improvement_times')->default(0)->comment('本日發出 改善 次數');
            $table->timestamps();

            $table->unique(['hand_lotion_sensor_id', 'date'], 'hand_lotion_daily_reports_sensor_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hand_lotion_daily_reports');
    }
}
