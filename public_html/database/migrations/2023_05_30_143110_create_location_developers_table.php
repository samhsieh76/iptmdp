<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationDevelopersTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // 場域開發者
        Schema::create('location_suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id')->comment('場域');
            $table->unsignedBigInteger('supplier_id')->comment('開發商');
            $table->tinyInteger('status')->default(0)->comment('狀態'); // 0 未被授權, 1 授權
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 場域開發者申請紀錄
        Schema::create('location_audit_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id')->comment('場域');
            $table->unsignedBigInteger('supplier_id')->comment('開發商');
            $table->string('token', 32)->unique();
            $table->unsignedTinyInteger('status')->default(0)->comment('狀態'); // 0 等待授權, 1 已授權, 2 已拒絕
            $table->unsignedBigInteger('auditor_id')->nullable()->comment('審核人員');
            $table->timestamps();

            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('location_developers');
        Schema::dropIfExists('location_suppliers');
        Schema::dropIfExists('location_audit_records');
    }
}
