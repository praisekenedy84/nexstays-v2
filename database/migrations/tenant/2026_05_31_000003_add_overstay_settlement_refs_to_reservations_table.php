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
            $table->foreignUuid('overstay_settlement_transaction_id')
                ->nullable()
                ->after('overstay_charge_transaction_id')
                ->constrained('folio_transactions')
                ->nullOnDelete();
            $table->foreignUuid('overstay_settlement_payment_id')
                ->nullable()
                ->after('overstay_settlement_transaction_id')
                ->constrained('payments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('overstay_settlement_payment_id');
            $table->dropConstrainedForeignId('overstay_settlement_transaction_id');
        });
    }
};
