<?php

use App\Models\Entry;
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
        Schema::create('entry_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Entry::class)->constrained();
            $table->bigInteger('relationable_id');
            $table->smallInteger('relationable_type');
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_relations');
    }
};
