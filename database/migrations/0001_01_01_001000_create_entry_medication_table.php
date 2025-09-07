<?php

use App\Models\Entry;
use App\Models\Medication;
use App\Models\User;
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
        Schema::create('entry_medication', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Entry::class);
            $table->foreignIdFor(Medication::class);
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_medication');
    }
};
