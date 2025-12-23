<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfigurationsTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->string('display_name')->comment('顯示名稱');
            $table->string('key')->comment('鍵');
            $table->text('value')->comment('值');
            $table->text('details')->comment('信息');
            $table->string('type')->comment('類型');
            $table->unsignedInteger('order')->comment('順序');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('configurations');
    }
}