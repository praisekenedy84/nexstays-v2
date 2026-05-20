<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('type', 30); // restaurant | bar | lounge
            $table->json('floor_plan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('menu_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->string('name', 100);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('menu_categories')->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('sku', 50)->nullable();
            $table->json('allergens')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_available')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('outlet_tables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->string('table_number', 20);
            $table->unsignedSmallInteger('capacity');
            $table->string('section', 50)->nullable();
            $table->string('status', 30)->default('available');
            $table->decimal('position_x', 8, 2)->nullable();
            $table->decimal('position_y', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['outlet_id', 'table_number']);
            $table->index(['outlet_id', 'status']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets');
            $table->foreignUuid('table_id')->nullable()->constrained('outlet_tables')->nullOnDelete();
            $table->foreignUuid('folio_id')->nullable()->constrained('folios')->nullOnDelete();
            $table->string('order_number', 20)->unique();
            $table->string('status', 30)->default('open');
            $table->unsignedSmallInteger('covers')->default(1);
            $table->foreignUuid('waiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('guest_label', 100)->nullable(); // bar tab / walk-in name
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('menu_item_id')->constrained('menu_items');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->json('modifiers')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('course')->default(1);
            $table->string('status', 30)->default('pending');
            $table->timestamp('sent_to_kds_at')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        Schema::create('till_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->foreignUuid('opened_by')->constrained('users');
            $table->foreignUuid('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('float_amount', 12, 2)->default(0);
            $table->char('currency', 3)->default('TZS');
            $table->string('status', 20)->default('open');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('declared_cash', 12, 2)->nullable();
            $table->decimal('system_cash', 12, 2)->nullable();
            $table->decimal('over_short', 12, 2)->nullable();
            $table->text('manager_notes')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('folio_id')->nullable()->constrained('folios')->nullOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignUuid('till_session_id')->nullable()->constrained('till_sessions')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->string('method', 30);
            $table->string('gateway', 50)->nullable();
            $table->string('gateway_ref', 255)->nullable();
            $table->json('gateway_response')->nullable();
            $table->decimal('cash_tendered', 12, 2)->nullable();
            $table->decimal('cash_change', 12, 2)->nullable();
            $table->foreignUuid('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receipt_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('captured');
            $table->timestamp('fiscalized_at')->nullable();
            $table->string('fiscal_ref', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('till_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('till_session_id')->constrained('till_sessions')->cascadeOnDelete();
            $table->string('movement_type', 30);
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->uuid('reference_id')->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->string('description', 255);
            $table->foreignUuid('performed_by')->constrained('users');
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('stock_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->string('name', 150);
            $table->string('category', 50)->nullable();
            $table->string('unit', 20);
            $table->decimal('reorder_level', 10, 3)->default(0);
            $table->decimal('current_stock', 10, 3)->default(0);
            $table->decimal('cost_per_unit', 12, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->foreignUuid('stock_item_id')->constrained('stock_items')->cascadeOnDelete();
            $table->decimal('quantity', 10, 4);
            $table->string('unit', 20);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_item_id')->constrained('stock_items')->cascadeOnDelete();
            $table->string('movement_type', 30);
            $table->decimal('quantity', 10, 3);
            $table->uuid('reference_id')->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('bar_tabs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignUuid('folio_id')->nullable()->constrained('folios')->nullOnDelete();
            $table->string('guest_label', 100);
            $table->string('status', 20)->default('open');
            $table->foreignUuid('opened_by')->constrained('users');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bar_tabs');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('stock_items');
        Schema::dropIfExists('till_movements');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('till_sessions');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('outlet_tables');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menu_categories');
        Schema::dropIfExists('outlets');
    }
};
