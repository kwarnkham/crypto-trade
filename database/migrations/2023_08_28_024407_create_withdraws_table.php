<?php

use App\Enums\WithdrawStatus;
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
        Schema::create('withdraws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('wallet_id')->nullable()->constrained();
            $table->string('to');
            $table->double('amount');
            $table->double('fee')->nullable();
            $table->tinyInteger('status')->default(WithdrawStatus::PENDING->value);
            $table->string('txid')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained();
            $table->tinyInteger('attempts')->default(0);
            $table->timestamps();
            $table->string('agent_transaction_id')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdraws');
    }
};
