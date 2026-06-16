<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('market_category_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['market_id', 'category_tag_id']);
        });

        if (Schema::hasColumn('markets', 'category_tags')) {
            $markets = DB::table('markets')->select('id', 'category_tags')->get();

            foreach ($markets as $market) {
                $names = json_decode($market->category_tags, true) ?? [];
                if (! is_array($names)) {
                    continue;
                }

                foreach ($names as $name) {
                    $name = trim((string) $name);
                    if ($name === '') {
                        continue;
                    }

                    $slug = Str::slug($name);
                    $tagId = DB::table('category_tags')->where('slug', $slug)->value('id');

                    if (! $tagId) {
                        $tagId = DB::table('category_tags')->insertGetId([
                            'name' => $name,
                            'slug' => $slug,
                            'is_active' => true,
                            'sort_order' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('market_category_tag')->insertOrIgnore([
                        'market_id' => $market->id,
                        'category_tag_id' => $tagId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::table('markets', function (Blueprint $table) {
                $table->dropColumn('category_tags');
            });
        }
    }

    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            if (! Schema::hasColumn('markets', 'category_tags')) {
                $table->json('category_tags')->nullable()->after('longitude');
            }
        });

        $pivots = DB::table('market_category_tag')
            ->join('category_tags', 'category_tags.id', '=', 'market_category_tag.category_tag_id')
            ->select('market_category_tag.market_id', 'category_tags.name')
            ->get()
            ->groupBy('market_id');

        foreach ($pivots as $marketId => $rows) {
            DB::table('markets')->where('id', $marketId)->update([
                'category_tags' => json_encode($rows->pluck('name')->values()->all()),
            ]);
        }

        Schema::dropIfExists('market_category_tag');
        Schema::dropIfExists('category_tags');
    }
};