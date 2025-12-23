<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->comment('角色id');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父級id');
            $table->string('username', 16)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('name', 45)->comment('使用者稱號');
            $table->string('password');
            $table->string('phone', 45)->nullable()->comment('電話');
            $table->string('memo')->nullable()->comment('備註');
            $table->string('timezone_name', 45)->default('Asia/Taipei');
            $table->string('timezone_offset', 45)->default('+08:00');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('users');
    }
}