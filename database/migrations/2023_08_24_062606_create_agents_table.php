<?php

use App\Enums\AgentStatus;
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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('key');
            $table->text('remark')->nullable();
            $table->tinyInteger('status')->default(AgentStatus::NORMAL->value);
            $table->ipAddress('ip');
            $table->string('deposit_callback')->nullable();
            $table->string('withdraw_callback')->nullable();
            $table->string('aes_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
