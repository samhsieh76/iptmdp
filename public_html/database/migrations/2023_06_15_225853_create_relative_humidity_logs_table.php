<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRelativeHumidityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('relative_humidity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('relative_humidity_sensor_id');
            $table->decimal('raw_data', 5, 2);
            $table->decimal('value', 5, 2)->default(-1.0);
            $table->timestamps();

            $table->index('relative_humidity_sensor_id');
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
        Schema::dropIfExists('relative_humidity_logs');
    }
}
