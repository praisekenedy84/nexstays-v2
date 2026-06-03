<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignUuid('overstay_charge_transaction_id')
                ->nullable()
                ->after('overstay_charge')
                ->constrained('folio_transactions')
                ->nullOnDelete();
            $table->text('overstay_waiver_reason')->nullable()->after('overstay_notes');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('overstay_charge_transaction_id');
            $table->dropColumn('overstay_waiver_reason');
        });
    }
};
