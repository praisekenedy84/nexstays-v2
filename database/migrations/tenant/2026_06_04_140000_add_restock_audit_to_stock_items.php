<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->timestamp('last_restocked_at')->nullable()->after('awaiting_stock');
            $table->foreignUuid('last_restocked_by')->nullable()->after('last_restocked_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_restocked_by');
            $table->dropColumn('last_restocked_at');
        });
    }
};
