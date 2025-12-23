<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationPeriodToToilet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('toilets', function (Blueprint $table) {
            $table->time('notification_start')->default('09:00')->comment('通報起始時間');
            $table->time('notification_end')->default('18:00')->comment('營運起始時間');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('toilets', function (Blueprint $table) {
            if (Schema::hasColumn('toilets', 'notification_start')) {
                $table->dropColumn('notification_start');
            }
            if (Schema::hasColumn('toilets', 'notification_end')) {
                $table->dropColumn('notification_end');
            }
        });
    }
}
