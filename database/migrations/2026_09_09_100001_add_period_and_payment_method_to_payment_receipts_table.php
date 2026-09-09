<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_receipts', function (Blueprint $table) {
            $table->unsignedSmallInteger('period_year')->nullable()->after('place_id');
            $table->unsignedTinyInteger('period_month')->nullable()->after('period_year');
            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('period_month')
                ->constrained('payment_methods')
                ->nullOnDelete();

            $table->index(['period_year', 'period_month', 'market_id']);
            $table->index(['place_id', 'period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_receipts', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropIndex(['period_year', 'period_month', 'market_id']);
            $table->dropIndex(['place_id', 'period_year', 'period_month']);
            $table->dropColumn(['period_year', 'period_month', 'payment_method_id']);
        });
    }
};
