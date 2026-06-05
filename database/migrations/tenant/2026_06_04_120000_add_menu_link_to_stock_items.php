<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->foreignUuid('menu_item_id')->nullable()->after('outlet_id')->constrained('menu_items')->nullOnDelete();
            $table->boolean('awaiting_stock')->default(false)->after('current_stock');
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('menu_item_id');
            $table->dropColumn('awaiting_stock');
        });
    }
};
