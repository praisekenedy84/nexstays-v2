<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folio_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('folio_id')->constrained('folios')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('transaction_type', 30);
            $table->string('description', 255);
            $table->decimal('amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->string('tax_code', 10)->nullable();
            $table->uuid('reference_id')->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->foreignUuid('posted_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestampTz('posted_at')->useCurrent();
            $table->timestampTz('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::table('folio_transactions', function (Blueprint $table) {
            $table->index('folio_id');
            $table->index('transaction_type');
            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folio_transactions');
    }
};
