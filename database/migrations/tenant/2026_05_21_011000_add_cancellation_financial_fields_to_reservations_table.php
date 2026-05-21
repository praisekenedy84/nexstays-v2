<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestampTz('cancelled_at')->nullable()->after('status');
            $table->string('cancellation_policy', 30)->nullable()->after('cancelled_at');
            $table->unsignedSmallInteger('cancellation_nights_used')->nullable()->after('cancellation_policy');
            $table->decimal('prepaid_amount_at_cancellation', 12, 2)->nullable()->after('cancellation_nights_used');
            $table->decimal('cancellation_charge_amount', 12, 2)->nullable()->after('prepaid_amount_at_cancellation');
            $table->decimal('cancellation_refund_amount', 12, 2)->nullable()->after('cancellation_charge_amount');
            $table->decimal('cancellation_refund_percentage', 5, 2)->nullable()->after('cancellation_refund_amount');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'cancelled_at',
                'cancellation_policy',
                'cancellation_nights_used',
                'prepaid_amount_at_cancellation',
                'cancellation_charge_amount',
                'cancellation_refund_amount',
                'cancellation_refund_percentage',
            ]);
        });
    }
};
