<?php

use Illuminate\Database\Migrations\Migration;
use Tpetry\PostgresqlEnhanced\Schema\Blueprint;
use Tpetry\PostgresqlEnhanced\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chatwoot_contexts', function (Blueprint $table) {
            $table->identity(always: true)->primary();
            $table->bigInteger('contextable_id');
            $table->smallInteger('contextable_type');
            $table->bigInteger('chatwoot_account_id');
            $table->bigInteger('chatwoot_conversation_id');
            $table->bigInteger('chatwoot_contact_id');
            $table->bigInteger('chatwoot_user_id');
            $table->bigInteger('chatwoot_message_id');
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatwoot_contexts');
    }
};
