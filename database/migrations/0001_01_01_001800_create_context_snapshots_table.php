<?php

use Illuminate\\Database\\Migrations\\Migration;
use Tpetry\\PostgresqlEnhanced\\Schema\\Blueprint;
use Tpetry\\PostgresqlEnhanced\\Support\\Facades\\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('context_snapshots', function (Blueprint $table) {
            $table->identity(always: true)->primary();
            $table->bigInteger('contextable_id');
            $table->smallInteger('contextable_type');
            $table->jsonb('snapshot');
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index(['contextable_type', 'contextable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('context_snapshots');
    }
};
