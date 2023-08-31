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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('balance')->default(0);
            $table->unsignedBigInteger('trx')->default(0);
            $table->unsignedBigInteger('staked_for_energy')->default(0);
            $table->unsignedBigInteger('staked_for_bandwidth')->default(0);
            $table->jsonb('resource')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->string('base58_check')->index();
            $table->string('public_key');
            $table->string('hex_address');
            $table->string('base64');
            $table->text('private_key');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
