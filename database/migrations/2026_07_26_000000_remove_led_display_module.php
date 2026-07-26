<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('led_displays')) {
            Schema::drop('led_displays');
        }

        if (Schema::hasColumn('announcements', 'show_on_led')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropColumn('show_on_led');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('announcements', 'show_on_led')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->boolean('show_on_led')->default(true)->after('content');
            });
        }

        if (! Schema::hasTable('led_displays')) {
            Schema::create('led_displays', function (Blueprint $table) {
                $table->id();
                $table->foreignId('market_id')->constrained()->cascadeOnDelete();
                $table->string('display_type');
                $table->json('payload');
                $table->unsignedInteger('refresh_interval')->default(30);
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_refreshed_at')->nullable();
                $table->timestamps();

                $table->index(['market_id', 'display_type', 'is_active']);
            });
        }
    }
};
