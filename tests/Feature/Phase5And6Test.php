<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Canteen;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Zone;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class Phase5And6Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_phase5_order_status_page_renders_all_elements(): void
    {
        $canteen = Canteen::where('is_open', true)->first();
        $menu = $canteen->menus()->where('is_available', true)->first();
        $zone = Zone::first();

        $cartService = app(CartService::class);
        $cartService->add($menu, 1, 'tanpa cabe');

        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'zone_id' => $zone->id,
            'location_notes' => 'Meja pojok',
        ]);

        $response = $this->get('/pesanan/' . $order->order_code);
        $response->assertStatus(200);
        $response->assertSee('Pesananmu');
        $response->assertSee('Menunggu pembayaran');
        $response->assertSee('Diproses');
        $response->assertSee('Diantar');
        $response->assertSee('Selesai');
        $response->assertSee('Simulasi Bayar');
        $response->assertSee('Hubungi Kantin');
        $response->assertSee($menu->name);
        $response->assertSee('tanpa cabe');
        $response->assertSee('Meja pojok');
    }

    public function test_phase5_json_status_endpoint(): void
    {
        $order = Order::create([
            'order_code' => 'ORD-TEST-1234',
            'order_number' => 'ORD-TEST-1234',
            'canteen_id' => 1,
            'zone_id' => 1,
            'customer_name' => 'Test Customer',
            'subtotal' => 15000,
            'total_amount' => 15000,
            'status' => 'menunggu_bayar',
            'payment_status' => 'belum_bayar',
        ]);

        $response = $this->getJson('/pesanan/' . $order->order_code . '/status');
        $response->assertStatus(200);
        $response->assertJson([
            'order_code' => 'ORD-TEST-1234',
            'status' => 'menunggu_bayar',
            'step' => 1,
            'is_completed' => false,
        ]);
    }

    public function test_phase5_simulated_payment_sets_dibayar_and_diproses(): void
    {
        $order = Order::create([
            'order_code' => 'ORD-TEST-5678',
            'order_number' => 'ORD-TEST-5678',
            'canteen_id' => 1,
            'zone_id' => 1,
            'customer_name' => 'Test Customer',
            'subtotal' => 20000,
            'total_amount' => 20000,
            'status' => 'menunggu_bayar',
            'payment_status' => 'belum_bayar',
        ]);

        $response = $this->post('/pesanan/' . $order->order_code . '/simulasi-bayar');
        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('dibayar', $order->payment_status);
        $this->assertEquals('diproses', $order->status);
    }

    public function test_phase6_admin_login_and_logout(): void
    {
        // View login page
        $this->get('/admin/login')->assertStatus(200)->assertSee('Panel Admin Kantin');

        // Login with correct credentials
        $response = $this->post('/admin/login', [
            'username' => 'kantin1',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('admin.orders'));
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertEquals('kantin1', Auth::guard('admin')->user()->username);

        // Logout
        $logoutResponse = $this->post('/admin/logout');
        $logoutResponse->assertRedirect(route('admin.login'));
        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_phase6_admin_middleware_protects_orders_page(): void
    {
        $response = $this->get('/admin/pesanan');
        $response->assertRedirect(route('admin.login'));
    }

    public function test_phase6_admin_orders_isolation_and_payment_status_rule(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();
        $this->assertNotNull($admin1);

        // Order 1: Kantin 1, payment_status = 'belum_bayar' -> MUST NOT appear
        $orderUnpaid = Order::create([
            'order_code' => 'ORD-UNPAID',
            'order_number' => 'ORD-UNPAID',
            'canteen_id' => 1,
            'zone_id' => 1,
            'customer_name' => 'Unpaid User',
            'subtotal' => 10000,
            'total_amount' => 10000,
            'status' => 'menunggu_bayar',
            'payment_status' => 'belum_bayar',
        ]);

        // Order 2: Kantin 1, payment_status = 'dibayar', status = 'diproses' -> MUST appear in Kantin 1
        $orderPaidKantin1 = Order::create([
            'order_code' => 'ORD-PAID-K1',
            'order_number' => 'ORD-PAID-K1',
            'canteen_id' => 1,
            'zone_id' => 1,
            'customer_name' => 'Paid User Kantin 1',
            'subtotal' => 15000,
            'total_amount' => 15000,
            'status' => 'diproses',
            'payment_status' => 'dibayar',
            'processed_at' => null, // Tab Baru
        ]);

        // Order 3: Kantin 2, payment_status = 'dibayar' -> MUST NOT appear in Kantin 1
        $orderPaidKantin2 = Order::create([
            'order_code' => 'ORD-PAID-K2',
            'order_number' => 'ORD-PAID-K2',
            'canteen_id' => 2,
            'zone_id' => 1,
            'customer_name' => 'User Kantin 2',
            'subtotal' => 18000,
            'total_amount' => 18000,
            'status' => 'diproses',
            'payment_status' => 'dibayar',
        ]);

        $response = $this->actingAs($admin1, 'admin')->get('/admin/pesanan?tab=baru');
        $response->assertStatus(200);
        $response->assertSee('ORD-PAID-K1');
        $response->assertSee('Paid User Kantin 1');
        $response->assertDontSee('ORD-UNPAID');
        $response->assertDontSee('ORD-PAID-K2');
    }

    public function test_phase6_order_status_transitions_and_automatic_timestamps(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();

        $order = Order::create([
            'order_code' => 'ORD-FLOW-01',
            'order_number' => 'ORD-FLOW-01',
            'canteen_id' => 1,
            'zone_id' => 1,
            'customer_name' => 'Flow User',
            'subtotal' => 25000,
            'total_amount' => 25000,
            'status' => 'diproses',
            'payment_status' => 'dibayar',
            'processed_at' => null,
        ]);

        // 1. Process order: sets processed_at
        $this->actingAs($admin1, 'admin')->post("/admin/pesanan/{$order->id}/process");
        $order->refresh();
        $this->assertNotNull($order->processed_at);
        $this->assertEquals('diproses', $order->status);

        // 2. Deliver order: sets delivered_at
        $this->actingAs($admin1, 'admin')->post("/admin/pesanan/{$order->id}/deliver");
        $order->refresh();
        $this->assertNotNull($order->delivered_at);
        $this->assertEquals('diantar', $order->status);

        // 3. Complete order: sets completed_at
        $this->actingAs($admin1, 'admin')->post("/admin/pesanan/{$order->id}/complete");
        $order->refresh();
        $this->assertNotNull($order->completed_at);
        $this->assertEquals('selesai', $order->status);
    }

    public function test_phase6_admin_canteen_toggle_open_close(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();
        $canteen = $admin1->canteen;
        $initialState = $canteen->is_open;

        $response = $this->actingAs($admin1, 'admin')->postJson('/admin/kantin/toggle-status');
        $response->assertStatus(200);
        $response->assertJson(['is_open' => !$initialState]);

        $canteen->refresh();
        $this->assertEquals(!$initialState, $canteen->is_open);
    }
}
