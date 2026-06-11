<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('facility_type', 20); // pool | gym
            $table->string('visitor_name', 150);
            $table->foreignUuid('guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->foreignUuid('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->char('currency', 3)->default('TZS');
            $table->string('settlement', 30); // cash | mobile_money | card | folio | complimentary
            $table->foreignUuid('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignUuid('folio_transaction_id')->nullable()->constrained('folio_transactions')->nullOnDelete();
            $table->foreignUuid('till_session_id')->nullable()->constrained('till_sessions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignUuid('recorded_by')->constrained('users');
            $table->timestamp('attended_at')->useCurrent();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['facility_type', 'attended_at']);
            $table->index('settlement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_attendances');
    }
};
