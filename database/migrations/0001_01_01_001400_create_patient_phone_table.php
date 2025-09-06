<?php

use App\Models\Patient;
use App\Models\Phone;
use Illuminate\Database\Migrations\Migration;
use Tpetry\PostgresqlEnhanced\Query\Builder;
use Tpetry\PostgresqlEnhanced\Schema\Blueprint;
use Tpetry\PostgresqlEnhanced\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patient_phone', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patient::class)->constrained();
            $table->foreignIdFor(Phone::class)->constrained();
            $table->timestampTz('primary_since')->nullable();
            $table->timestampTz('call_consent_since')->nullable();
            $table->timestampTz('sms_consent_since')->nullable();
            $table->timestampTz('whatsapp_consent_since')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['patient_id', 'phone_id']);

            $table->uniqueIndex(['patient_id'])
                ->where(fn (Builder $condition) => $condition->whereNotNull('primary_since')
                );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_phone');
    }
};
