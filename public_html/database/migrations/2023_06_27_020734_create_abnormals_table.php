<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAbnormalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('abnormals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('toilet_id');
            $table->integer('triggerable_id');
            $table->string('triggerable_type');
            $table->boolean('is_improved')->default(0)->comment('是否已改善');
            $table->timestamp('improved_at')->nullable(true);
            $table->integer('improve_efficient')->default(0)->comment('改善效率');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('abnormals');
    }
}
