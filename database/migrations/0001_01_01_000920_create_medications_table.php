<?php

use App\Models\ActiveSubstance;
use App\Models\MedicinalProduct;
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
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ActiveSubstance::class)->constrained();
            $table->foreignIdFor(MedicinalProduct::class)->constrained();
            $table->smallInteger('country');
            $table->smallInteger('route_of_administration');
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
