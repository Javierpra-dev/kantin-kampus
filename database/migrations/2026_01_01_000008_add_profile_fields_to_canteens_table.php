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
        Schema::table('canteens', function (Blueprint $table) {
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('open_time')->nullable()->after('opening_hours');
            $table->string('close_time')->nullable()->after('open_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('canteens', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'open_time', 'close_time']);
        });
    }
};

