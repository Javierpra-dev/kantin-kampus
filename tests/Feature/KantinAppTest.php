<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\Menu;
use App\Models\User;
use App\Models\Zone;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KantinAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_seeders_create_required_entities(): void
    {
        $this->assertEquals(4, Canteen::count());
        $this->assertEquals(8, Zone::count());
        $this->assertEquals(4, User::where('role', 'canteen_admin')->count());
        $this->assertGreaterThanOrEqual(24, Menu::count());

        // Verify admin credentials
        for ($i = 1; $i <= 4; $i++) {
            $user = User::where('username', "kantin{$i}")->first();
            $this->assertNotNull($user);
            $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $user->password));
        }
    }

    public function test_rupiah_helper_formats_currency(): void
    {
        $this->assertEquals('Rp15.000', rupiah(15000));
        $this->assertEquals('Rp27.000', rupiah(27000));
        $this->assertEquals('Rp0', rupiah(0));
    }

    public function test_home_page_renders_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Mau makan apa hari ini?');
        $response->assertSee('Cari menu atau kantin');
        $response->assertSee('Kantin 1');
        $response->assertSee('Kantin 2');
        $response->assertSee('Buka');
        $response->assertSee('Tutup');
    }

    public function test_home_page_search_works(): void
    {
        $response = $this->get('/?q=Ayam');
        $response->assertStatus(200);
        $response->assertSee('Kantin');
    }

    public function test_home_page_filter_chips_work(): void
    {
        $response = $this->get('/?filter=' . urlencode('Di bawah Rp10.000'));
        $response->assertStatus(200);
    }

    public function test_canteen_menu_page_renders_items(): void
    {
        $canteen = Canteen::where('is_open', true)->first();
        $response = $this->get('/kantin/' . $canteen->id);

        $response->assertStatus(200);
        $response->assertSee($canteen->name);
        $response->assertSee('Buka');
    }

    public function test_closed_canteen_shows_notice(): void
    {
        $closedCanteen = Canteen::where('is_open', false)->first();
        $this->assertNotNull($closedCanteen);

        $response = $this->get('/kantin/' . $closedCanteen->id);
        $response->assertStatus(200);
        $response->assertSee('Kantin Sedang Tutup');
        $response->assertSee('Tutup');
    }

    public function test_cart_service_basic_lifecycle(): void
    {
        $cartService = app(CartService::class);
        $canteen1 = Canteen::find(1);
        $menu1 = $canteen1->menus()->where('is_available', true)->first();

        // Add
        $res = $cartService->add($menu1, 2, 'pedas manis');
        $this->assertTrue($res['success']);
        $this->assertEquals(2, $cartService->count());
        $this->assertEquals($menu1->price * 2, $cartService->total());

        // Increase
        $cartService->increase($menu1->id);
        $this->assertEquals(3, $cartService->count());

        // Note
        $cartService->setNote($menu1->id, 'tanpa es');
        $cart = $cartService->get();
        $this->assertEquals('tanpa es', $cart['items'][$menu1->id]['note']);

        // Decrease
        $cartService->decrease($menu1->id);
        $this->assertEquals(2, $cartService->count());

        // Clear
        $cartService->clear();
        $this->assertEquals(0, $cartService->count());
    }

    public function test_cart_enforces_single_canteen_rule(): void
    {
        $cartService = app(CartService::class);
        $canteen1 = Canteen::find(1);
        $canteen2 = Canteen::find(2);

        $menuCanteen1 = $canteen1->menus()->where('is_available', true)->first();
        $menuCanteen2 = $canteen2->menus()->where('is_available', true)->first();

        // Add from Canteen 1
        $res1 = $cartService->add($menuCanteen1, 1);
        $this->assertTrue($res1['success']);

        // Try adding from Canteen 2 -> Conflict!
        $res2 = $cartService->add($menuCanteen2, 1);
        $this->assertFalse($res2['success']);
        $this->assertTrue($res2['conflict']);

        // Force replace
        $res3 = $cartService->add($menuCanteen2, 1, null, true);
        $this->assertTrue($res3['success']);
        $this->assertEquals(2, $cartService->get()['canteen_id']);
    }

    public function test_cart_page_empty_and_filled(): void
    {
        // Empty state
        $response = $this->get('/keranjang');
        $response->assertStatus(200);
        $response->assertSee('Keranjangmu masih kosong');

        // Add item
        $menu = Menu::where('is_available', true)->whereHas('canteen', fn($q) => $q->where('is_open', true))->first();
        $this->post('/cart/add', ['menu_id' => $menu->id, 'quantity' => 2]);

        $responseFilled = $this->get('/keranjang');
        $responseFilled->assertStatus(200);
        $responseFilled->assertSee($menu->name);
        $responseFilled->assertSee('Lanjut');
        $responseFilled->assertSee('Catatan, mis. tanpa es');
    }
}
