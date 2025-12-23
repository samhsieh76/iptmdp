<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHumanTrafficLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('human_traffic_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('human_traffic_sensor_id');
            $table->integer('raw_data');
            $table->integer('value')->default(-1);
            $table->boolean('is_trigger_notification')->default(0)->comment('處理此資料時，是否觸發 通報');
            $table->timestamps();

            $table->index('human_traffic_sensor_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('human_traffic_logs');
    }
}
