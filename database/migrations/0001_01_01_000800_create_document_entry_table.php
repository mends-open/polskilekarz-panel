<?php

use App\Models\Document;
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
        Schema::create('document_entry', function (Blueprint $table) {
            $table->identity(always: true)->primary();
            $table->foreignIdFor(Document::class)->constrained();
            $table->foreignIdFor(Entry::class)->constrained();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_entry');
    }
};
