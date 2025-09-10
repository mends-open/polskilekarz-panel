<?php

use App\Models\CloudflareLink;
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
        Schema::create('cloudflare_link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CloudflareLink::class)->constrained();
            $table->text('ray_id')->index();
            $table->jsonb('cloudflare');
            $table->jsonb('headers');
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflare_link_clicks');
    }
};
