<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateToiletPaperSensorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('toilet_paper_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('toilet_id');
            $table->uuid('sensor_id')->default(DB::raw('(UUID())'));
            $table->string('name')->comment('名稱 可表述感測器位置');
            $table->decimal('min_value', 5, 2)->default(0.0)->comment('最小值 基準值');
            $table->decimal('max_value', 5, 2)->default(25.0)->comment('最大值');
            $table->decimal('critical_value', 5, 2)->default(22.5)->comment('臨界值');
            $table->boolean('is_alert')->default(1)->comment('是否發出 警報 事件');
            $table->boolean('is_notification')->default(1)->comment('是否發出 通報 事件');
            $table->boolean('is_abnormal')->default(1)->comment('是否發出 異常/改善 事件');
            $table->timestamps();

            $table->decimal('latest_raw_data', 5, 2)->nullable()->comment('最後上傳原始資料');
            $table->decimal('latest_value', 5, 2)->nullable()->comment('最後上傳的值');
            $table->timestamp('latest_updated_at')->nullable()->comment('最後資料上傳時間');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('toilet_paper_sensors');
    }
}
