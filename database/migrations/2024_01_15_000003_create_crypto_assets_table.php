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
        Schema::create('crypto_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->string('symbol', 10);
            $table->string('name', 50);
            $table->decimal('balance', 20, 8)->default(0.00000000);
            $table->decimal('balance_usd', 15, 2)->default(0.00);
            $table->decimal('current_rate_usd', 15, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['user_id', 'symbol']);
            $table->index(['user_id', 'symbol']);
            $table->index(['wallet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_assets');
    }
};
