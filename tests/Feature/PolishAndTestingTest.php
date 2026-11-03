<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Canteen;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Zone;
use App\Services\CartService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class PolishAndTestingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Requirement: Feature test for cart totals
     */
    public function test_cart_totals_calculation_and_subtotals(): void
    {
        $cartService = app(CartService::class);
        $canteen1 = Canteen::find(1);

        $menus = $canteen1->menus()->where('is_available', true)->take(2)->get();
        $menuA = $menus[0]; // e.g. Rp18.000
        $menuB = $menus[1]; // e.g. Rp15.000

        // Add 2 of Menu A and 3 of Menu B
        $cartService->add($menuA, 2);
        $cartService->add($menuB, 3);

        $expectedTotalA = (int) $menuA->price * 2;
        $expectedTotalB = (int) $menuB->price * 3;
        $expectedGrandTotal = $expectedTotalA + $expectedTotalB;

        $cart = $cartService->get();

        $this->assertEquals(5, $cartService->count());
        $this->assertEquals($expectedGrandTotal, $cartService->total());
        $this->assertEquals($expectedTotalA, $cart['items'][$menuA->id]['subtotal']);
        $this->assertEquals($expectedTotalB, $cart['items'][$menuB->id]['subtotal']);

        // Test Cart Page renders the calculated totals
        $response = $this->get('/keranjang');
        $response->assertStatus(200);
        $response->assertSee(rupiah($expectedGrandTotal));
    }

    /**
     * Requirement: Feature test for order creation (server-side price verification)
     */
    public function test_order_creation_enforces_server_side_pricing_ignoring_manipulated_client_prices(): void
    {
        $canteen = Canteen::where('is_open', true)->first();
        $menu = $canteen->menus()->where('is_available', true)->first();
        $zone = Zone::where('name', 'not like', '%Tidak yakin%')->first();

        $actualDatabasePrice = (int) $menu->price;
        $fakeManipulatedPrice = 100; // Spoofed client attempt (Rp 100)

        // Artificially inject corrupted/tampered price into session cart
        Session::put('cart', [
            'canteen_id' => $canteen->id,
            'canteen_name' => $canteen->name,
            'items' => [
                $menu->id => [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'price' => $fakeManipulatedPrice,
                    'quantity' => 2,
                    'subtotal' => $fakeManipulatedPrice * 2,
                ]
            ],
            'total' => $fakeManipulatedPrice * 2,
            'count' => 2,
        ]);

        $response = $this->post('/pengantaran', [
            'customer_name' => 'Auditor Security',
            'customer_phone' => '081234567890',
            'zone_id' => $zone->id,
            'location_notes' => 'Meja Audit',
        ]);

        $order = Order::where('customer_name', 'Auditor Security')->first();
        $this->assertNotNull($order);

        // Assert that the order IGNORED the fake Rp100 and copied authentic price from database
        $expectedSubtotal = $actualDatabasePrice * 2;
        $this->assertEquals($expectedSubtotal, $order->subtotal);
        $this->assertEquals($expectedSubtotal, $order->total_amount);

        $orderItem = $order->items()->first();
        $this->assertEquals($actualDatabasePrice, $orderItem->unit_price);
        $this->assertEquals($expectedSubtotal, $orderItem->subtotal);
    }

    /**
     * Requirement: Feature test for status changes filling the timestamps
     */
    public function test_status_changes_fill_timestamps_correctly(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();
        $orderService = app(OrderService::class);

        $order = Order::create([
            'order_code' => 'ORD-TIME-TEST-01',
            'order_number' => 'ORD-TIME-TEST-01',
            'canteen_id' => 1,
            'zone_id' => 1,
            'customer_name' => 'Timestamp User',
            'subtotal' => 20000,
            'total_amount' => 20000,
            'status' => 'diproses',
            'payment_status' => 'dibayar',
            'processed_at' => null,
            'delivered_at' => null,
            'completed_at' => null,
        ]);

        // Initially timestamps must be null
        $this->assertNull($order->processed_at);
        $this->assertNull($order->delivered_at);
        $this->assertNull($order->completed_at);

        // 1. Process order -> fills processed_at
        $this->actingAs($admin1, 'admin')->post("/admin/pesanan/{$order->id}/process");
        $order->refresh();
        $this->assertNotNull($order->processed_at);
        $this->assertEquals('diproses', $order->status);
        $this->assertNull($order->delivered_at);
        $this->assertNull($order->completed_at);

        // 2. Deliver order -> fills delivered_at
        $this->actingAs($admin1, 'admin')->post("/admin/pesanan/{$order->id}/deliver");
        $order->refresh();
        $this->assertNotNull($order->delivered_at);
        $this->assertEquals('diantar', $order->status);
        $this->assertNull($order->completed_at);

        // 3. Complete order -> fills completed_at
        $this->actingAs($admin1, 'admin')->post("/admin/pesanan/{$order->id}/complete");
        $order->refresh();
        $this->assertNotNull($order->completed_at);
        $this->assertEquals('selesai', $order->status);
    }

    /**
     * Requirement: Feature test for admin access isolation between canteens
     */
    public function test_admin_access_isolation_between_canteens(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();
        $admin2 = Admin::where('username', 'kantin2')->first();

        // Order belonging to Kantin 2
        $orderK2 = Order::create([
            'order_code' => 'ORD-ISOLATION-K2',
            'order_number' => 'ORD-ISOLATION-K2',
            'canteen_id' => 2,
            'zone_id' => 1,
            'customer_name' => 'Kantin 2 Customer',
            'subtotal' => 30000,
            'total_amount' => 30000,
            'status' => 'diproses',
            'payment_status' => 'dibayar',
        ]);

        // Menu item belonging to Kantin 2
        $menuK2 = Menu::where('canteen_id', 2)->first();

        // Admin 1 tries to view orders list -> Kantin 2 order MUST NOT be visible
        $ordersPage = $this->actingAs($admin1, 'admin')->get('/admin/pesanan?tab=baru');
        $ordersPage->assertStatus(200);
        $ordersPage->assertDontSee('ORD-ISOLATION-K2');

        // Admin 1 tries to process Kantin 2's order -> MUST return 404
        $processAttempt = $this->actingAs($admin1, 'admin')->post("/admin/pesanan/{$orderK2->id}/process");
        $processAttempt->assertStatus(404);

        // Admin 1 tries to deliver Kantin 2's order -> MUST return 404
        $deliverAttempt = $this->actingAs($admin1, 'admin')->post("/admin/pesanan/{$orderK2->id}/deliver");
        $deliverAttempt->assertStatus(404);

        // Admin 1 tries to complete Kantin 2's order -> MUST return 404
        $completeAttempt = $this->actingAs($admin1, 'admin')->post("/admin/pesanan/{$orderK2->id}/complete");
        $completeAttempt->assertStatus(404);

        // Admin 1 tries to edit Kantin 2's menu -> MUST return 404
        $editMenuAttempt = $this->actingAs($admin1, 'admin')->get("/admin/menu/{$menuK2->id}/edit");
        $editMenuAttempt->assertStatus(404);

        // Admin 1 tries to toggle Kantin 2's menu -> MUST return 404
        $toggleMenuAttempt = $this->actingAs($admin1, 'admin')->postJson("/admin/menu/{$menuK2->id}/toggle-availability");
        $toggleMenuAttempt->assertStatus(404);

        // Admin 1 tries to delete Kantin 2's menu -> MUST return 404
        $deleteMenuAttempt = $this->actingAs($admin1, 'admin')->delete("/admin/menu/{$menuK2->id}");
        $deleteMenuAttempt->assertStatus(404);
    }
}

