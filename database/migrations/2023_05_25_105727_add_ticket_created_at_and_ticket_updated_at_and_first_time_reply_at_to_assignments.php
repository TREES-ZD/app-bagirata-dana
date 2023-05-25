<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->timestamp('zendesk_ticket_created_at')->nullable();
            $table->timestamp('zendesk_ticket_updated_at')->nullable();
            $table->string('zendesk_job_id')->nullable();
            $table->timestamp('first_time_reply_at')->nullable();
            $table->string('response_error')->nullable();
            $table->string('response_details')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['zendesk_ticket_created_at', 'zendesk_ticket_updated_at', 'zendesk_job_id', 'first_time_reply_at', 'response_error', 'response_details']);
        });
    }
};
