<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateToiletPaperLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('toilet_paper_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('toilet_paper_sensor_id');
            $table->decimal('raw_data', 5, 2);
            $table->decimal('value', 5, 2)->default(-1.0);
            $table->boolean('is_trigger_alert')->default(0)->comment('處理此資料時，是否觸發 警報');
            $table->boolean('is_trigger_notification')->default(0)->comment('處理此資料時，是否觸發 通報');
            $table->boolean('is_trigger_abnormal')->default(0)->comment('處理此資料時，是否觸發 異常');
            $table->boolean('is_trigger_improvement')->default(0)->comment('處理此資料時，是否觸發 改善');
            $table->timestamps();

            $table->index('toilet_paper_sensor_id');
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
        Schema::dropIfExists('toilet_paper_logs');
    }
}
