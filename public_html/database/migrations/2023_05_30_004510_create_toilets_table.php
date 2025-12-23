<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateToiletsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('toilets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id')->comment('場域id');
            $table->unsignedBigInteger('creator_id')->comment('創建者id');
            $table->unsignedTinyInteger('type')->comment('類型:女廁,男廁,無障礙廁所');
            $table->string('code', 20)->comment('公廁編號');
            $table->string('name')->comment('廁所名稱');
            $table->string('image')->nullable()->comment('廁所圖片');
            $table->string('device_key')->unique()->comment('設備金鑰');
            $table->string('alert_token')->nullable()->comment('通知token');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('toilets');
    }
}
