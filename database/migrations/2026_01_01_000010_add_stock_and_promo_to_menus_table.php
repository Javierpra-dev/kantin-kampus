<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->unsignedInteger('daily_stock')->nullable()->after('flavor_tags');
            $table->unsignedInteger('promo_price')->nullable()->after('daily_stock');
            $table->time('promo_start')->nullable()->after('promo_price');
            $table->time('promo_end')->nullable()->after('promo_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['daily_stock', 'promo_price', 'promo_start', 'promo_end']);
        });
    }
};

