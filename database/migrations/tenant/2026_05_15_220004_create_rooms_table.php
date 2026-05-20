<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('room_type_id')->constrained('room_types')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('room_number', 10)->unique();
            $table->unsignedSmallInteger('floor')->nullable();
            $table->string('status', 30)->default('vacant_clean');
            $table->boolean('is_smoking')->default(false);
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
