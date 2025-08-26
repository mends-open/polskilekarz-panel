<?php

use App\Models\Patient;
use App\Models\Phone;
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
        Schema::create('patient_phone', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patient::class)->constrained();
            $table->foreignIdFor(Phone::class)->constrained();
            $table->timestamp('primary_since')->default(false);
            $table->timestamp('call_consent_since')->nullable();
            $table->timestamp('sms_consent_since')->nullable();
            $table->timestamp('whatsapp_consent_since')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['patient_id', 'phone_id']);
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
