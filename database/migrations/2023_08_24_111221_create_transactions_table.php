<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('from');
            $table->string('to')->index();
            $table->string('transaction_id')->unique();
            $table->string('token_address');
            $table->string('block_timestamp');
            $table->unsignedBigInteger('value');
            $table->string('type');
            $table->unsignedBigInteger('fee')->nullable();
            $table->jsonb('receipt')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
