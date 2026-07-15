<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->string('province')->nullable()->after('city');
            $table->string('commune')->nullable()->after('province');
            $table->string('zone')->nullable()->after('commune');
            $table->string('colline')->nullable()->after('zone');
            $table->index(['province', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->dropIndex(['province', 'is_active']);
            $table->dropColumn(['province', 'commune', 'zone', 'colline']);
        });
    }
};
