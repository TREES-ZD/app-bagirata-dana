<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('priority');
            $table->boolean('status');
            $table->string('agent_id');
            $table->string('agent_name');
            $table->string('group_id');
            $table->string('group_name');
            $table->string('custom_field');
            $table->string('limit');
            $table->timestamps();

            $table->index(['agent_id', 'group_id', 'agent_name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agents');
    }
}
