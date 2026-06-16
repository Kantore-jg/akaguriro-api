<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedInteger('total_places')->default(0);
            $table->unsignedInteger('occupied_places')->default(0);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('category_tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('visit_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['city', 'is_active']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};