<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('booking_ref', 20)->unique();
            $table->foreignUuid('guest_id')->constrained('guests')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignUuid('room_id')->nullable()->constrained('rooms')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignUuid('room_type_id')->constrained('room_types')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('status', 30)->default('confirmed');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedSmallInteger('adults')->default(1);
            $table->unsignedSmallInteger('children')->default(0);
            $table->foreignUuid('rate_plan_id')->nullable()->constrained('rate_plans')->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('daily_rate', 12, 2);
            $table->string('source', 50)->nullable();
            $table->string('ota_ref', 100)->nullable();
            $table->text('special_requests')->nullable();
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->index('status');
            $table->index('check_in_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
