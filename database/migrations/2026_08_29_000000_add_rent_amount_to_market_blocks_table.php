<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_blocks', function (Blueprint $table) {
            $table->unsignedInteger('rent_amount')->default(0)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('market_blocks', function (Blueprint $table) {
            $table->dropColumn('rent_amount');
        });
    }
};
