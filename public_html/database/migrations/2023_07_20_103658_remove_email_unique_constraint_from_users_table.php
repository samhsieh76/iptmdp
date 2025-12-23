<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveEmailUniqueConstraintFromUsersTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // 移除 users 資料表中 email 欄位的 unique 屬性
        $isEmailUnique = DB::select("SHOW INDEXES FROM users WHERE Column_name = 'email' AND Non_unique = 0");

        if ($isEmailUnique) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        // 恢復 users 資料表中 email 欄位的 unique 屬性
        /* Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        }); */
    }
}
