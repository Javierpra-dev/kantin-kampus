<?php

namespace Tests\Feature\Api\V1;

use App\Models\Canteen;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. Creating an order via API ignores client prices and computes on server.
     */
    public function test_creating_order_via_api_ignores_client_prices(): void
    {
        $canteen = Canteen::where('is_open', true)->first();
        $zone = Zone::first();

        $menu = Menu::create([
            'canteen_id' => $canteen->id,
            'name' => 'Nasi Padang Komplit',
            'price' => 25000,
            'category' => 'Makanan berat',
            'is_available' => true,
        ]);

        $payload = [
            'canteen_id' => $canteen->id,
            'customer_name' => 'Andi',
            'customer_whatsapp' => '081234567890',
            'zone_id' => $zone->id,
            'location_note' => 'Meja nomor 12',
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => 2,
                    'price' => 100, // Manipulated price from client - MUST BE IGNORED
                    'note' => 'Sambal banyak',
                ]
            ],
        ];

        $response = $this->postJson('/api/v1/orders', $payload);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'order_code',
                'order_token',
                'status',
                'payment_status',
                'total',
                'items' => [
                    '*' => ['menu_id', 'name', 'unit_price', 'quantity', 'subtotal']
                ],
            ]
        ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data['order_token']);
        $this->assertEquals(40, strlen($data['order_token']));

        // Expected subtotal: 25000 * 2 = 50000 + delivery_fee
        $expectedTotal = (25000 * 2) + (int) $zone->delivery_fee;
        $this->assertEquals($expectedTotal, $data['total']);
        $this->assertEquals(25000, $data['items'][0]['unit_price']);
        $this->assertEquals(50000, $data['items'][0]['subtotal']);

        // Check DB
        $order = Order::where('order_code', $data['order_code'])->first();
        $this->assertNotNull($order);
        $this->assertEquals($data['order_token'], $order->access_token);
        $this->assertEquals($expectedTotal, $order->total_amount);
    }

    /**
     * 2. Order for a closed canteen is rejected with an error.
     */
    public function test_order_creation_fails_if_canteen_is_closed(): void
    {
        $canteen = Canteen::where('is_open', false)->first();
        if (!$canteen) {
            $canteen = Canteen::first();
            $canteen->update(['is_open' => false]);
        }

        $zone = Zone::first();
        $menu = Menu::where('canteen_id', $canteen->id)->first() ?? Menu::create([
            'canteen_id' => $canteen->id,
            'name' => 'Menu Kantin Tutup',
            'price' => 10000,
            'category' => 'Makanan berat',
            'is_available' => true,
        ]);

        $payload = [
            'canteen_id' => $canteen->id,
            'customer_name' => 'Budi',
            'zone_id' => $zone->id,
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 1]
            ],
        ];

        $response = $this->postJson('/api/v1/orders', $payload);
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => "Kantin '{$canteen->name}' sedang tutup dan tidak dapat menerima pesanan.",
        ]);
    }

    /**
     * 3. Order status requires the correct token (403 if invalid or missing).
     */
    public function test_order_status_requires_correct_order_token(): void
    {
        $canteen = Canteen::where('is_open', true)->first();
        $zone = Zone::first();
        $menu = Menu::where('canteen_id', $canteen->id)->first();

        $createRes = $this->postJson('/api/v1/orders', [
            'canteen_id' => $canteen->id,
            'customer_name' => 'Citra',
            'zone_id' => $zone->id,
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 1]
            ],
        ]);
        $orderCode = $createRes->json('data.order_code');
        $validToken = $createRes->json('data.order_token');

        // Request with missing token -> 403
        $noTokenRes = $this->getJson("/api/v1/orders/{$orderCode}");
        $noTokenRes->assertStatus(403);

        // Request with invalid token -> 403
        $invalidTokenRes = $this->withHeader('X-Order-Token', 'invalid_token_12345')
            ->getJson("/api/v1/orders/{$orderCode}");
        $invalidTokenRes->assertStatus(403);

        // Request with valid token -> 200
        $validRes = $this->withHeader('X-Order-Token', $validToken)
            ->getJson("/api/v1/orders/{$orderCode}");
        $validRes->assertStatus(200);
        $validRes->assertJsonPath('data.order_code', $orderCode);
        $validRes->assertJsonPath('data.customer_name', 'Citra');
    }

    /**
     * 4. Payment simulation endpoint is unavailable (404) when PAYMENT_SIMULATION is false.
     */
    public function test_payment_simulation_endpoint_returns_404_when_simulation_disabled(): void
    {
        $canteen = Canteen::where('is_open', true)->first();
        $zone = Zone::first();
        $menu = Menu::where('canteen_id', $canteen->id)->first();

        $createRes = $this->postJson('/api/v1/orders', [
            'canteen_id' => $canteen->id,
            'customer_name' => 'Dedi',
            'zone_id' => $zone->id,
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 1]
            ],
        ]);
        $orderCode = $createRes->json('data.order_code');
        $token = $createRes->json('data.order_token');

        // Turn off simulation
        Config::set('midtrans.payment_simulation', false);

        $response = $this->withHeader('X-Order-Token', $token)
            ->postJson("/api/v1/orders/{$orderCode}/simulate-pay");

        $response->assertStatus(404);
        $response->assertJsonFragment([
            'message' => 'Endpoint simulasi pembayaran tidak aktif di lingkungan ini.',
        ]);

        // Turn on simulation -> 200
        Config::set('midtrans.payment_simulation', true);
        $responseOk = $this->withHeader('X-Order-Token', $token)
            ->postJson("/api/v1/orders/{$orderCode}/simulate-pay");

        $responseOk->assertStatus(200);
        $responseOk->assertJsonPath('data.payment_status', 'dibayar');
    }

    /**
     * 5. Location photo upload via multipart request is re-encoded and saved safely.
     */
    public function test_order_creation_with_location_photo_multipart(): void
    {
        Storage::fake('local');
        $canteen = Canteen::where('is_open', true)->first();
        $zone = Zone::first();
        $menu = Menu::where('canteen_id', $canteen->id)->first();

        $photo = UploadedFile::fake()->image('meja.jpg', 600, 600);

        $response = $this->post('/api/v1/orders', [
            'canteen_id' => $canteen->id,
            'customer_name' => 'Eka',
            'zone_id' => $zone->id,
            'location_photo' => $photo,
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 1]
            ],
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $orderCode = $response->json('data.order_code');
        $order = Order::where('order_code', $orderCode)->first();

        $this->assertNotNull($order->location_photo);
        $this->assertTrue($order->had_location_photo);
        Storage::disk('local')->assertExists($order->location_photo);
    }
}

