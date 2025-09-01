<?php

use App\Models\Entity;
use App\Models\User;
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
        Schema::create('entity_user', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Entity::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->timestampsTz();
            $table->softDeletesTz();
        });;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_user');
    }
};
