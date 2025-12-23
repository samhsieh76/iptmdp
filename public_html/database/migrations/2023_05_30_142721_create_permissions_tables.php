<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionsTables extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // 權限功能
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->string('display_name', 45);
            $table->timestamps();
        });

        // 權限行為
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->string('display_name', 45);
            $table->timestamps();
        });

        // 權限
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('action_id');
            $table->timestamps();
            $table->foreign('program_id')
                ->references('id')
                ->on('programs')
                ->onDelete('cascade');

            $table->foreign('action_id')
                ->references('id')
                ->on('actions')
                ->onDelete('cascade');

            $table->unique(['program_id', 'action_id']);
        });

        // 角色群組
        // 若為一般使用者 Level 1、2 需有上級，使用此table 判斷可選擇上級
        Schema::create('role_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 45)->unique();
            $table->timestamps();
        });

        // 角色
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 45);
            $table->tinyInteger('level')->default(0);
            $table->unsignedBigInteger('group_id');
            $table->timestamps();
        });

        // 角色的權限
        Schema::create('role_permission', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
            $table->timestamps();
            $table->primary(['permission_id', 'role_id'], 'role_permission_permission_id_role_id_primary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('actions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('role_groups');
    }
}