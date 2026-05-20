<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->char('nationality', 2)->nullable();
            $table->string('id_type', 30)->nullable();
            $table->string('id_number', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->json('preferences')->nullable();
            $table->unsignedSmallInteger('vip_level')->default(0);
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
