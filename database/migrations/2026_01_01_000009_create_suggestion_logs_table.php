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
        Schema::create('suggestion_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('budget');
            $table->string('mood')->default('bebas');
            $table->integer('results_count')->default(0);
            $table->boolean('chosen')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestion_logs');
    }
};

