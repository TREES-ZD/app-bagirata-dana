<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailabilityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('availability_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('status', ["Available", "Unavailable"]);
            $table->string('agent_id', 60)->nullable();
            $table->string('causer_id', 60)->nullable();
            $table->string('causer_type')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('availability_logs');
    }
}
