<?php

use App\Enums\ExtractStatus;
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
        Schema::create('extracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained();
            $table->foreignId('wallet_id')->constrained();
            $table->string('to');
            $table->double('amount');
            $table->tinyInteger('status')->default(ExtractStatus::PENDING->value);
            $table->tinyInteger('type');
            $table->string('txid')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained();
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
        Schema::dropIfExists('extracts');
    }
};
