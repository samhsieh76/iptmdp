<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // 場域
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('county_id')->nullable()->comment('縣市id');
            $table->unsignedBigInteger('administration_id')->nullable()->comment('管轄者id');
            $table->string('name')->comment('名稱');
            $table->string('auth_code', 10)->unique()->comment('授權碼');
            $table->string('address')->nullable()->comment('地址');
            $table->decimal('longitude', 11, 8)->nullable()->comment('經度');
            $table->decimal('latitude', 10, 8)->nullable()->comment('緯度');
            $table->string('business_hours')->nullable()->comment('營運時間');
            $table->string('image')->nullable()->comment('場域圖片');
            $table->unsignedTinyInteger('is_active')->default(1)->comment('是否啟用');
            $table->timestamps();

            $table->foreign('county_id')->references('id')->on('counties')->onDelete('cascade');
            $table->foreign('administration_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 營運時間目前僅設定為字串
        /* Schema::create('location_business_hours', function (Blueprint $table) {
            $table->id();
            $table->string('day', 100)->nullable()->comment('星期幾');
            $table->time('start_time')->nullable()->comment('開始時間');
            $table->time('end_time')->nullable()->comment('結束時間');
            $table->timestamps();
        }); */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('locations');
    }
}