<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRequestLogsTableForUserTypeAttribute extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_logs', function(Blueprint $table) {
            $table->string('user_type')
                ->nullable()
                ->after('user_id')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::hasColumn('request_logs', 'user_type')) {
            Schema::table('request_logs', function (Blueprint $table) {
                $table->dropColumn('user_type');
            });
        }
    }
}
