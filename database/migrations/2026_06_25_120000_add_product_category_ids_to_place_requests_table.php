<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('place_requests', function (Blueprint $table) {
            $table->json('product_category_ids')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('place_requests', function (Blueprint $table) {
            $table->dropColumn('product_category_ids');
        });
    }
};