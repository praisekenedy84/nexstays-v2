<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_plan_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rate_plan_id')->constrained('rate_plans')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignUuid('room_type_id')->constrained('room_types')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('price', 12, 2);
            $table->timestampsTz();
            $table->unique(['rate_plan_id', 'room_type_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_plan_prices');
    }
};
