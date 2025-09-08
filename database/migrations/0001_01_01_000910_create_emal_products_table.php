<?php

use App\Models\EMASubstance;
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
            $table->id();
            $table->foreignIdFor(EMASubstance::class)->constrained();
            $table->caseInsensitiveText('name')->unique();
            $table->integerArray('routes_of_administration');
            $table->integerArray('countries');
            $table->timestampsTz();
            $table->softDeletesTz();
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
