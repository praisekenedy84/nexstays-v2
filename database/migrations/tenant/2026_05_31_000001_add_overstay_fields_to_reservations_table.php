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
            $table->unsignedSmallInteger('overstay_nights')->nullable()->after('checked_out_at');
            $table->decimal('overstay_rate', 12, 2)->nullable()->after('overstay_nights');
            $table->decimal('overstay_charge', 12, 2)->nullable()->after('overstay_rate');
            $table->string('overstay_settlement', 20)->nullable()->after('overstay_charge'); // pending | paid | waived
            $table->text('overstay_notes')->nullable()->after('overstay_settlement');
            $table->foreignUuid('overstay_settled_by')->nullable()->after('overstay_notes')
                ->constrained('users')->nullOnDelete();
            $table->timestampTz('overstay_settled_at')->nullable()->after('overstay_settled_by');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('overstay_settled_by');
            $table->dropColumn([
                'overstay_nights',
                'overstay_rate',
                'overstay_charge',
                'overstay_settlement',
                'overstay_notes',
                'overstay_settled_at',
            ]);
        });
    }
};
