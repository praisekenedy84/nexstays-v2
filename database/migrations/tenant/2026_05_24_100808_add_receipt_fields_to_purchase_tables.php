<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('supplier_reference', 100)->nullable()->after('supplier_name');
            $table->date('delivery_date_expected')->nullable()->after('ordered_at');
            $table->string('payment_terms', 100)->nullable()->after('delivery_date_expected');
            $table->decimal('received_total', 12, 2)->nullable()->after('total_amount');
        });

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->decimal('qty_received', 10, 3)->nullable()->after('quantity');
            $table->decimal('actual_unit_cost', 10, 4)->nullable()->after('unit_cost');
            $table->string('line_notes', 500)->nullable()->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['supplier_reference', 'delivery_date_expected', 'payment_terms', 'received_total']);
        });

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropColumn(['qty_received', 'actual_unit_cost', 'line_notes']);
        });
    }
};
