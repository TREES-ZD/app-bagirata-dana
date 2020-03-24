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
            // $table->bigIncrements('id');
            $table->string('id', 60)->primary();
            $table->integer('priority');
            $table->string('zendesk_agent_id');
            $table->string('zendesk_agent_name');
            $table->string('zendesk_group_id');
            $table->string('zendesk_group_name');
            $table->string('zendesk_custom_field');
            $table->string('limit');
            $table->boolean('status');
            $table->boolean('reassign');            
            $table->timestamps();

            $table->index(['zendesk_agent_id', 'zendesk_group_id', 'zendesk_custom_field']);
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
