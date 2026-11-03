<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Zone;
use App\Services\CartService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase11bStockAndPromoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test effective price returns promo price when inside promo window, normal price otherwise.
     */
    public function test_effective_price_inside_and_outside_promo_window(): void
    {
        $canteen = Canteen::first();

        // 1. Promo active: 14:00 to 18:00, test at 15:00
        Carbon::setTestNow(Carbon::parse('2026-11-02 15:00:00', 'Asia/Jakarta'));

        $menu = Menu::create([
            'canteen_id' => $canteen->id,
            'name' => 'Paket Hemat Sore',
            'price' => 20000,
            'promo_price' => 15000,
            'promo_start' => '14:00:00',
            'promo_end' => '18:00:00',
            'category' => 'Makanan berat',
            'is_available' => true,
        ]);

        $this->assertTrue($menu->isPromoActive());
        $this->assertEquals(15000, $menu->effectivePrice());

        // 2. Outside promo window: test at 19:00
        Carbon::setTestNow(Carbon::parse('2026-11-02 19:00:00', 'Asia/Jakarta'));

        $this->assertFalse($menu->isPromoActive());
        $this->assertEquals(20000, $menu->effectivePrice());

        Carbon::setTestNow(); // clear mock
    }

    /**
     * Test stock limits and error message when quantity exceeds remaining stock.
     */
    public function test_order_decreases_stock_and_rejects_when_exceeding(): void
    {
        $canteen = Canteen::where('is_open', true)->first();
        $zone = Zone::first();

        $menu = Menu::create([
            'canteen_id' => $canteen->id,
            'name' => 'Nasi Goreng Terbatas',
            'price' => 15000,
            'daily_stock' => 2,
            'category' => 'Makanan berat',
            'is_available' => true,
        ]);

        $cartService = app(CartService::class);
        $cartService->clear();

        // Attempt to add 3 portions when only 2 remain
        $addResult = $cartService->add($menu, 3);
        $this->assertFalse($addResult['success']);
        $this->assertStringContainsString('Sisa porsi Nasi Goreng Terbatas tinggal 2', $addResult['message']);

        // Order 2 portions (valid)
        $cartService->clear();
        $cartService->add($menu, 2);

        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'customer_name' => 'Budi',
            'zone_id' => $zone->id,
        ]);

        $menu->refresh();
        $this->assertEquals(0, $menu->daily_stock);
        $this->assertTrue($menu->isOutOfStock());

        // Another buyer tries to order
        $cartService->clear();
        $addOut = $cartService->add($menu, 1);
        $this->assertFalse($addOut['success']);
    }

    /**
     * Test stock is restored when payment becomes kedaluwarsa.
     */
    public function test_stock_restored_when_payment_becomes_kedaluwarsa(): void
    {
        $canteen = Canteen::where('is_open', true)->first();
        $zone = Zone::first();

        $menu = Menu::create([
            'canteen_id' => $canteen->id,
            'name' => 'Ayam Bakar Spesial',
            'price' => 20000,
            'daily_stock' => 5,
            'category' => 'Makanan berat',
            'is_available' => true,
        ]);

        $cartService = app(CartService::class);
        $cartService->clear();
        $cartService->add($menu, 2);

        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'customer_name' => 'Siti',
            'zone_id' => $zone->id,
        ]);

        $menu->refresh();
        $this->assertEquals(3, $menu->daily_stock);

        // Mark order as expired
        $orderService->markPaymentExpired($order);

        $order->refresh();
        $this->assertEquals('kedaluwarsa', $order->payment_status);

        $menu->refresh();
        $this->assertEquals(5, $menu->daily_stock);
    }

    /**
     * Test menus:reset-stock scheduled command sets daily_stock to null.
     */
    public function test_reset_menu_stock_command(): void
    {
        $canteen = Canteen::first();

        $menu = Menu::create([
            'canteen_id' => $canteen->id,
            'name' => 'Soto Ayam',
            'price' => 12000,
            'daily_stock' => 4,
            'category' => 'Makanan berat',
            'is_available' => true,
        ]);

        $this->artisan('menus:reset-stock')
            ->expectsOutputToContain('Berhasil mereset sisa porsi harian')
            ->assertExitCode(0);

        $menu->refresh();
        $this->assertNull($menu->daily_stock);
    }
}

