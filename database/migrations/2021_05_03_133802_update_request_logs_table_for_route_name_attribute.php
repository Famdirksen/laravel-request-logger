<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRequestLogsTableForRouteNameAttribute extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_logs', function(Blueprint $table) {
            $table->string('route_name')
                ->nullable()
                ->after('duration');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::hasColumn('request_logs', 'route_name')) {
            Schema::table('request_logs', function (Blueprint $table) {
                $table->dropColumn('route_name');
            });
        }
    }
}
