<?php

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
        Schema::createExtensionIfNotExists('citext');
        Schema::createExtensionIfNotExists('intarray');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropextensionIfExists(['citext', 'intarray']);
    }
};
