<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'closed_at'], 'orders_status_closed_at_index');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->index(['status', 'check_in_date', 'check_out_date'], 'reservations_status_stay_dates_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_closed_at_index');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_status_stay_dates_index');
        });
    }
};
