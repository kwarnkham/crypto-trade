<?php

use App\Enums\DepositStatus;
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
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('wallet_id')->constrained();
            $table->foreignId('transaction_id')->nullable()->constrained();
            $table->integer('amount');
            $table->tinyInteger('status')->default(DepositStatus::PENDING->value);
            $table->tinyInteger('attempts')->default(0);
            $table->timestamps();
            $table->string('agent_transaction_id')->unique()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
