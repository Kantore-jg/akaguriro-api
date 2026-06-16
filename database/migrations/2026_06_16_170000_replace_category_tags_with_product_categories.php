<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_product_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['market_id', 'product_category_id']);
        });

        if (Schema::hasTable('market_category_tag') && Schema::hasTable('category_tags')) {
            $rows = DB::table('market_category_tag')
                ->join('category_tags', 'category_tags.id', '=', 'market_category_tag.category_tag_id')
                ->select('market_category_tag.market_id', 'category_tags.name')
                ->get();

            foreach ($rows as $row) {
                $categoryId = DB::table('product_categories')->where('name', $row->name)->value('id');
                if (! $categoryId) {
                    continue;
                }

                DB::table('market_product_category')->insertOrIgnore([
                    'market_id' => $row->market_id,
                    'product_category_id' => $categoryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::dropIfExists('market_category_tag');
        Schema::dropIfExists('category_tags');
    }

    public function down(): void
    {
        Schema::dropIfExists('market_product_category');
    }
};