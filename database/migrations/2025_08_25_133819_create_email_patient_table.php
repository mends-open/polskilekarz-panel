<?php

use App\Models\Email;
use App\Models\Patient;
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
        Schema::create('email_patient', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patient::class)->constrained();
            $table->foreignIdFor(Email::class)->constrained();
            $table->timestamp('primary_since')->default(false);
            $table->timestamp('message_consent_since')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['patient_id', 'email_id']);
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
