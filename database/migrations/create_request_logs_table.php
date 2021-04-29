<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('referral_account_id')->index();
            $table->morphs('object');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('referrals');
    }
}
