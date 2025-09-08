<?php

use App\Models\EmaActiveSubstance;
use App\Models\EmaMedicinalProduct;
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
        Schema::create('ema_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(EmaActiveSubstance::class)->constrained('ema_active_substances');
            $table->foreignIdFor(EmaMedicinalProduct::class)->constrained('ema_medicinal_products');
            $table->smallInteger('countries')->array();
            $table->smallInteger('routes_of_administration')->array();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique([
                'active_substance_id',
                'medicinal_product_id',
            ], 'ema_medications_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ema_medications');
    }
};
