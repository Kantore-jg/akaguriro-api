<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_searches', function (Blueprint $table) {
            $table->id();
            $table->string('query');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('market_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['query', 'created_at']);
            $table->index('market_id');
        });

        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });

        Schema::create('market_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['market_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_visits');
        Schema::dropIfExists('product_views');
        Schema::dropIfExists('product_searches');
    }
};