<?php

use App\Models\EmaSubstance;
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
        Schema::create('ema_products', function (Blueprint $table) {
            $table->identity(always: true)->primary();
            $table->foreignIdFor(EmaSubstance::class)->constrained();
            $table->caseInsensitiveText('name');
            $table->integerArray('routes_of_administration');
            $table->integerArray('countries');
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['ema_substance_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ema_products');
    }
};
