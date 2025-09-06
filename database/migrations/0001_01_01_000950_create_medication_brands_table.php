<?php

use App\Models\Medication;
use Illuminate\Database\Migrations\Migration;
use Tpetry\PostgresqlEnhanced\Schema\Blueprint;
use Tpetry\PostgresqlEnhanced\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_brands', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Medication::class);
            $table->string('country');
            $table->string('brand');
            $table->string('administration');
            $table->string('form')->nullable();
            $table->string('strength')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_brands');
    }
};
