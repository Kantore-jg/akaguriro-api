<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('total_places')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['market_id', 'name']);
            $table->index(['market_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_blocks');
    }
};