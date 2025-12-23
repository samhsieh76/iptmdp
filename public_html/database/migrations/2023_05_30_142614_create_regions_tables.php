<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegionsTables extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('counties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('region_id')->comment('區域id');
            $table->string('code')->unique();
            $table->bigInteger('code01')->unique();
            $table->string('name');
            $table->timestamps();

            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
        });

        Schema::create('towns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('county_id')->comment('縣市id');
            $table->bigInteger('code')->unique();
            $table->string('code01')->unique();
            $table->string('name');
            $table->timestamps();

            $table->foreign('county_id')->references('id')->on('counties')->onDelete('cascade');
        });

        // self-referance
        /* Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父級id');
            $table->string('code')->nullable()->unique();
            $table->string('code01')->nullable()->unique();
            $table->string('name')->comment('區域名稱');
            $table->tinyInteger('level')->comment('區域級別');
            $table->unsignedTinyInteger('is_active');
            $table->timestamps();
        }); */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('towns');
        Schema::dropIfExists('counties');
        Schema::dropIfExists('regions');
    }
}