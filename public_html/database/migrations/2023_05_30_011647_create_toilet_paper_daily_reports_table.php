<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateToiletPaperDailyReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('toilet_paper_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('toilet_paper_sensor_id');
            $table->date('date');
            $table->decimal('average_value', 5, 2)->default(0.0)->comment('廁紙可用量平均值');
            $table->boolean('is_active')->default(1)->comment('感測器運作狀況');
            $table->boolean('is_under_notification')->default(0)->comment('是否已送出 通報');
            $table->integer('alert_times')->default(0)->comment('本日發出 警報 次數');
            $table->integer('notification_times')->default(0)->comment('本日發出 通報 次數');
            $table->integer('abnormal_times')->default(0)->comment('本日發出 異常 次數');
            $table->integer('improvement_times')->default(0)->comment('本日發出 改善 次數');
            $table->timestamps();

            $table->unique(['toilet_paper_sensor_id', 'date'], 'toilet_paper_daily_reports_sensor_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('toilet_paper_daily_reports');
    }
}
