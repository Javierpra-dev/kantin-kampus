<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Zone;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function createTestOrder(string $paymentStatus = 'belum_bayar', string $status = 'menunggu_bayar'): Order
    {
        return Order::create([
            'order_code' => 'ORD-MIDTRANS-' . rand(1000, 9999),
            'order_number' => 'ORD-MIDTRANS-NUM',
            'canteen_id' => 1,
            'zone_id' => 1,
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'location_notes' => 'Meja pojok',
            'subtotal' => 25000,
            'delivery_fee' => 0,
            'total_amount' => 25000,
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }

    public function test_payment_page_renders_with_qris_elements(): void
    {
        $order = $this->createTestOrder();

        $response = $this->get('/pembayaran/' . $order->order_code);
        $response->assertStatus(200);
        $response->assertSee('Pembayaran');
        $response->assertSee('Rp25.000');
        $response->assertSee('Bayar dalam');
        $response->assertSee('Scan QR dengan e-wallet atau m-banking kamu');
        $response->assertSee('Ringkasan pesanan');
        $response->assertSee('Simpan QR');
    }

    public function test_payment_page_redirects_if_already_paid(): void
    {
        $order = $this->createTestOrder('dibayar', 'diproses');

        $response = $this->get('/pembayaran/' . $order->order_code);
        $response->assertRedirect(route('order.show', $order->order_code));
    }

    public function test_payment_status_json_endpoint(): void
    {
        $order = $this->createTestOrder();

        $response = $this->getJson('/pembayaran/' . $order->order_code . '/status');
        $response->assertStatus(200);
        $response->assertJson([
            'order_code' => $order->order_code,
            'payment_status' => 'belum_bayar',
            'is_paid' => false,
            'is_expired' => false,
        ]);

        // When paid
        $order->update(['payment_status' => 'dibayar']);
        $responsePaid = $this->getJson('/pembayaran/' . $order->order_code . '/status');
        $responsePaid->assertJson([
            'is_paid' => true,
        ]);
    }

    public function test_webhook_verifies_signature_and_updates_order(): void
    {
        $order = $this->createTestOrder();
        $serverKey = config('midtrans.server_key');
        $statusCode = '200';
        $grossAmount = '25000.00';

        // Valid signature: SHA512(order_id + status_code + gross_amount + ServerKey)
        $validSignature = hash('sha512', $order->order_code . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $order->order_code,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $validSignature,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
        ];

        // Midtrans Webhook POST without CSRF
        $response = $this->postJson('/midtrans/webhook', $payload);
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $order->refresh();
        $this->assertEquals('dibayar', $order->payment_status);
        $this->assertEquals('diproses', $order->status);
        $this->assertNotNull($order->processed_at);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $order = $this->createTestOrder();

        $payload = [
            'order_id' => $order->order_code,
            'status_code' => '200',
            'gross_amount' => '25000.00',
            'signature_key' => 'invalid-fake-signature',
            'transaction_status' => 'settlement',
        ];

        $response = $this->postJson('/midtrans/webhook', $payload);
        $response->assertStatus(401);

        $order->refresh();
        $this->assertEquals('belum_bayar', $order->payment_status);
    }

    public function test_webhook_handles_expire(): void
    {
        $order = $this->createTestOrder();
        $serverKey = config('midtrans.server_key');
        $statusCode = '200';
        $grossAmount = '25000.00';

        $validSignature = hash('sha512', $order->order_code . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $order->order_code,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $validSignature,
            'transaction_status' => 'expire',
        ];

        $response = $this->postJson('/midtrans/webhook', $payload);
        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('kedaluwarsa', $order->payment_status);
    }

    public function test_payment_page_expired_shows_retry(): void
    {
        $order = $this->createTestOrder();
        $order->update([
            'payment_status' => 'kedaluwarsa',
            'expires_at' => Carbon::now()->subMinutes(5),
        ]);

        $response = $this->get('/pembayaran/' . $order->order_code);
        $response->assertStatus(200);
        $response->assertSee('Pembayaran kedaluwarsa');
        $response->assertSee('Buat QRIS Baru dan Coba Lagi');

        // Test retry action
        $retryResponse = $this->post('/pembayaran/' . $order->order_code . '/retry');
        $retryResponse->assertRedirect(route('payment.show', $order->order_code));

        $order->refresh();
        $this->assertEquals('belum_bayar', $order->payment_status);
    }

    public function test_simulated_payment_sets_dibayar_and_diproses(): void
    {
        $order = $this->createTestOrder();

        $response = $this->post('/pembayaran/' . $order->order_code . '/simulasi');
        $response->assertRedirect(route('order.show', $order->order_code));

        $order->refresh();
        $this->assertEquals('dibayar', $order->payment_status);
        $this->assertEquals('diproses', $order->status);
    }
}
