<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('po_number', 30)->unique();
            $table->foreignUuid('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->string('department', 30); // kitchen | bar
            $table->string('supplier_name', 150);
            $table->string('status', 20)->default('draft'); // draft | ordered | received | cancelled
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->char('currency', 3)->default('TZS');
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department', 'status']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignUuid('stock_item_id')->constrained('stock_items');
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_cost', 12, 4);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::create('expenditures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('category', 50); // utilities | salaries | maintenance | supplies | other
            $table->foreignUuid('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('TZS');
            $table->string('description', 255);
            $table->string('reference', 100)->nullable();
            $table->date('expense_date');
            $table->foreignUuid('recorded_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('expense_date');
            $table->index('category');
        });

        Schema::create('ancillary_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('default_price', 12, 2);
            $table->char('currency', 3)->default('TZS');
            $table->string('charge_type', 30)->default('misc');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('room_damages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('room_id')->constrained('rooms');
            $table->foreignUuid('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->string('description', 500);
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->char('currency', 3)->default('TZS');
            $table->string('status', 20)->default('reported'); // reported | invoiced | resolved
            $table->foreignUuid('reported_by')->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['room_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_damages');
        Schema::dropIfExists('ancillary_services');
        Schema::dropIfExists('expenditures');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
