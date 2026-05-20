<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reservation_id')->constrained('reservations')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('folio_number', 20)->unique();
            $table->string('status', 20)->default('open');
            $table->char('currency', 3)->default('TZS');
            $table->decimal('pre_auth_amount', 12, 2)->default(0);
            $table->decimal('settled_amount', 12, 2)->default(0);
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folios');
    }
};
