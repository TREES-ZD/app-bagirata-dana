<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZendeskGroupIdColumnToAdminUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->json("zendesk_assignee_ids")->nullable();
            $table->json("zendesk_group_ids")->nullable();
            $table->json("zendesk_custom_field_ids")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn("zendesk_assignee_ids");
            $table->dropColumn("zendesk_group_ids");
            $table->dropColumn("zendesk_custom_field_ids");
        });
    }
}
