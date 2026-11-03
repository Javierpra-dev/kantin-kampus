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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->string('order_number')->nullable();
            $table->foreignId('canteen_id')->constrained('canteens')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->text('location_notes')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->integer('subtotal')->default(0);
            $table->integer('delivery_fee')->default(0);
            $table->integer('total_amount')->default(0);
            $table->string('status')->default('menunggu_bayar');
            $table->string('payment_method')->default('qris');
            $table->string('payment_status')->default('belum_bayar');
            $table->text('qr_string')->nullable();
            $table->text('qr_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('midtrans_transaction_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
