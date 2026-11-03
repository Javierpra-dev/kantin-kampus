<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Zone;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_delivery_page_redirects_if_cart_empty(): void
    {
        $response = $this->get('/pengantaran');
        $response->assertRedirect('/keranjang');
    }

    public function test_delivery_page_renders_with_zones_and_title(): void
    {
        $menu = Menu::where('is_available', true)->whereHas('canteen', fn($q) => $q->where('is_open', true))->first();
        $this->post('/cart/add', ['menu_id' => $menu->id, 'quantity' => 1]);

        $response = $this->get('/pengantaran');
        $response->assertStatus(200);
        $response->assertSee('Kamu duduk di mana?');
        $response->assertSee('Pilih Lokasi Duduk');
        $response->assertSee('Nama Pemesan');
        $response->assertSee('Nomor WhatsApp');
        $response->assertSee('Lanjut ke pembayaran');
        $response->assertSee('Tidak yakin / lainnya');
    }

    public function test_order_creation_validation_requires_name_and_zone(): void
    {
        $menu = Menu::where('is_available', true)->whereHas('canteen', fn($q) => $q->where('is_open', true))->first();
        $this->post('/cart/add', ['menu_id' => $menu->id, 'quantity' => 1]);

        $response = $this->post('/pengantaran', []);
        $response->assertSessionHasErrors(['customer_name', 'zone_id']);
    }

    public function test_order_creation_validates_indonesian_phone_format(): void
    {
        $menu = Menu::where('is_available', true)->whereHas('canteen', fn($q) => $q->where('is_open', true))->first();
        $this->post('/cart/add', ['menu_id' => $menu->id, 'quantity' => 1]);
        $zone = Zone::first();

        // Invalid phone
        $responseInvalid = $this->post('/pengantaran', [
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '12345',
            'zone_id' => $zone->id,
        ]);
        $responseInvalid->assertSessionHasErrors('customer_phone');

        // Valid phone with 08...
        $responseValid = $this->post('/pengantaran', [
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'zone_id' => $zone->id,
        ]);
        $responseValid->assertSessionHasNoErrors();
    }

    public function test_order_creation_requires_notes_if_zone_is_tidak_yakin(): void
    {
        $menu = Menu::where('is_available', true)->whereHas('canteen', fn($q) => $q->where('is_open', true))->first();
        $this->post('/cart/add', ['menu_id' => $menu->id, 'quantity' => 1]);
        $otherZone = Zone::where('name', 'like', '%Tidak yakin%')->first();
        $this->assertNotNull($otherZone);

        // Missing location_notes when selecting "Tidak yakin / lainnya"
        $responseMissing = $this->post('/pengantaran', [
            'customer_name' => 'Rina',
            'zone_id' => $otherZone->id,
            'location_notes' => '',
        ]);
        $responseMissing->assertSessionHasErrors('location_notes');

        // Filled location_notes
        $responseFilled = $this->post('/pengantaran', [
            'customer_name' => 'Rina',
            'zone_id' => $otherZone->id,
            'location_notes' => 'Meja samping tiang, jaket hitam',
        ]);
        $responseFilled->assertSessionHasNoErrors();
    }

    public function test_successful_order_creation_inside_db_transaction(): void
    {
        $canteen = Canteen::where('is_open', true)->first();
        $menu1 = $canteen->menus()->where('is_available', true)->first();
        $zone = Zone::where('name', 'not like', '%Tidak yakin%')->first();

        $cartService = app(CartService::class);
        $cartService->add($menu1, 2, 'kurang manis');

        $response = $this->post('/pengantaran', [
            'customer_name' => 'Siti Aisyah',
            'customer_phone' => '085712345678',
            'zone_id' => $zone->id,
            'location_notes' => 'Lantai 2 dekat tangga',
        ]);

        $order = Order::where('customer_name', 'Siti Aisyah')->first();
        $this->assertNotNull($order);
        $this->assertNotNull($order->order_code);
        $this->assertEquals('menunggu_bayar', $order->status);
        $this->assertEquals('belum_bayar', $order->payment_status);
        $this->assertEquals($menu1->price * 2, $order->subtotal);

        // Verify order items unit_price copied from database
        $item = $order->items()->first();
        $this->assertNotNull($item);
        $this->assertEquals($menu1->price, $item->unit_price);
        $this->assertEquals(2, $item->quantity);
        $this->assertEquals('kurang manis', $item->note);

        // Verify cart is cleared
        $this->assertEquals(0, $cartService->count());

        // Verify redirect to /pesanan/{order_code}
        $response->assertRedirect('/pesanan/' . $order->order_code);

        // Verify order page renders
        $orderPage = $this->get('/pesanan/' . $order->order_code);
        $orderPage->assertStatus(200);
        $orderPage->assertSee($order->order_code);
        $orderPage->assertSee('Menunggu Pembayaran');
    }

    public function test_order_creation_fails_if_canteen_is_closed(): void
    {
        $closedCanteen = Canteen::where('is_open', false)->first();
        $menu = $closedCanteen->menus()->first();
        $zone = Zone::first();

        // Artificially put in session
        session(['cart' => [
            'canteen_id' => $closedCanteen->id,
            'canteen_name' => $closedCanteen->name,
            'items' => [
                $menu->id => [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'price' => $menu->price,
                    'quantity' => 1,
                    'subtotal' => $menu->price,
                ]
            ],
            'total' => $menu->price,
            'count' => 1,
        ]]);

        $response = $this->post('/pengantaran', [
            'customer_name' => 'Andi',
            'zone_id' => $zone->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, Order::count());
    }

    public function test_order_creation_fails_if_item_becomes_unavailable(): void
    {
        $canteen = Canteen::where('is_open', true)->first();
        $menu = $canteen->menus()->first();
        $menu->update(['is_available' => false]);
        $zone = Zone::first();

        session(['cart' => [
            'canteen_id' => $canteen->id,
            'canteen_name' => $canteen->name,
            'items' => [
                $menu->id => [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'price' => $menu->price,
                    'quantity' => 1,
                    'subtotal' => $menu->price,
                ]
            ],
            'total' => $menu->price,
            'count' => 1,
        ]]);

        $response = $this->post('/pengantaran', [
            'customer_name' => 'Andi',
            'zone_id' => $zone->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, Order::count());
    }
}
