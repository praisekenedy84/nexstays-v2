<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->unique();

            // Revenue by division
            $table->decimal('rooms', 14, 2)->default(0);
            $table->decimal('restaurant', 14, 2)->default(0);
            $table->decimal('bar', 14, 2)->default(0);
            $table->decimal('ancillary', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // Operational counters
            $table->unsignedSmallInteger('room_nights')->default(0);
            $table->decimal('payments_collected', 14, 2)->default(0);

            $table->timestamps();

            $table->index('snapshot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_snapshots');
    }
};
