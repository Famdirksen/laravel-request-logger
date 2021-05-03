<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRequestLogsTableForRequestDurationAttribute extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_logs', function(Blueprint $table) {
            $table->integer('duration')
                ->nullable()
                ->after('ip');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::hasColumn('request_logs', 'duration')) {
            Schema::table('request_logs', function (Blueprint $table) {
                $table->dropColumn('duration');
            });
        }
    }
}
