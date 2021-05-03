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
            $table->uuid('id')->primary();
            $table->integer('user_id')->nullable()->index();
            $table->string('ip');
            $table->text('url');
            $table->string('method');
            $table->text('input');
            $table->text('headers')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
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
