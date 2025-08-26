<?php

use App\Models\Entry;
use App\Models\Medication;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->softDeletesTz();
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
