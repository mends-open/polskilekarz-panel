<?php

use App\Models\Email;
use App\Models\Patient;
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
        Schema::create('email_patient', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patient::class)->constrained();
            $table->foreignIdFor(Email::class)->constrained();
            $table->timestampTz('primary_since')->nullable();
            $table->timestampTz('message_consent_since')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['patient_id', 'email_id']);

            $table->uniqueIndex(['patient_id'])
                ->where(fn (Builder $condition) =>
                $condition->whereNotNull('primary_since')
                );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_patient');
    }
};
