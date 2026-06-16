<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->foreignId('place_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('unit')->default('unit');
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('available')->default(true);
            $table->boolean('is_trending')->default(false);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('search_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['market_id', 'slug']);
            $table->index(['market_id', 'available']);
            $table->index(['user_id', 'available']);
            $table->index('category_id');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};