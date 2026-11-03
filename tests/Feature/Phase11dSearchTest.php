<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\Menu;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase11dSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test searching "geprek" returns menus containing geprek with their canteen names.
     */
    public function test_searching_geprek_returns_menus_with_canteen_names(): void
    {
        $canteen = Canteen::first();
        $canteen->update(['is_open' => true]);

        Menu::create([
            'canteen_id' => $canteen->id,
            'name' => 'Ayam Geprek Sambal Korek',
            'description' => 'Ayam renyah geprek pedas gurih',
            'price' => 15000,
            'category' => 'Makanan berat',
            'is_available' => true,
        ]);

        $response = $this->getJson('/cari?q=geprek');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'query',
            'menus' => [
                '*' => [
                    'id',
                    'name',
                    'price',
                    'effective_price',
                    'canteen' => ['id', 'name', 'is_open'],
                ]
            ],
            'canteens',
            'total',
        ]);

        $data = $response->json();
        $this->assertGreaterThanOrEqual(1, $data['total']);
        $menuNames = array_column($data['menus'], 'name');
        $this->assertContains('Ayam Geprek Sambal Korek', $menuNames);

        $found = collect($data['menus'])->firstWhere('name', 'Ayam Geprek Sambal Korek');
        $this->assertEquals($canteen->name, $found['canteen']['name']);
    }

    /**
     * Test "minuman dingin" synonym returns drinks / segar items.
     */
    public function test_searching_minuman_dingin_returns_drinks_and_segar_items(): void
    {
        $canteen = Canteen::first();
        $canteen->update(['is_open' => true]);

        // Item 1: drink with 'es' in name
        Menu::create([
            'canteen_id' => $canteen->id,
            'name' => 'Es Teh Manis Segar',
            'price' => 5000,
            'category' => 'Minuman',
            'flavor_tags' => ['segar'],
            'is_available' => true,
        ]);

        $response = $this->getJson('/cari?q=' . urlencode('minuman dingin'));
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertGreaterThanOrEqual(1, $data['total']);

        // All returned items must match drink / cold
        foreach ($data['menus'] as $item) {
            $this->assertEquals('Minuman', $item['category']);
        }
    }

    /**
     * Test that Habis items rank last.
     */
    public function test_habis_items_rank_last(): void
    {
        $canteen = Canteen::first();
        $canteen->update(['is_open' => true]);

        // Available item
        $availableMenu = Menu::create([
            'canteen_id' => $canteen->id,
            'name' => 'Mie Goreng Spesial',
            'price' => 12000,
            'category' => 'Makanan berat',
            'is_available' => true,
            'daily_stock' => 5,
        ]);

        // Habis item (by is_available = false)
        $habisMenu = Menu::create([
            'canteen_id' => $canteen->id,
            'name' => 'Mie Goreng Telur',
            'price' => 10000,
            'category' => 'Makanan berat',
            'is_available' => false,
        ]);

        // Habis item (by daily_stock = 0)
        $stockZeroMenu = Menu::create([
            'canteen_id' => $canteen->id,
            'name' => 'Mie Goreng Baso',
            'price' => 11000,
            'category' => 'Makanan berat',
            'is_available' => true,
            'daily_stock' => 0,
        ]);

        $response = $this->getJson('/cari?q=mie');
        $response->assertStatus(200);

        $menus = $response->json('menus');
        $this->assertGreaterThanOrEqual(3, count($menus));

        $availableIndex = array_search($availableMenu->id, array_column($menus, 'id'));
        $habisIndex = array_search($habisMenu->id, array_column($menus, 'id'));
        $zeroStockIndex = array_search($stockZeroMenu->id, array_column($menus, 'id'));

        $this->assertLessThan($habisIndex, $availableIndex);
        $this->assertLessThan($zeroStockIndex, $availableIndex);
    }

    /**
     * Test that short queries (less than 2 characters) return nothing without filter.
     */
    public function test_short_queries_return_nothing(): void
    {
        $response = $this->getJson('/cari?q=a');
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('total'));
        $this->assertEmpty($response->json('menus'));
        $this->assertEmpty($response->json('canteens'));

        $responseBlank = $this->getJson('/cari?q=' . urlencode('   '));
        $responseBlank->assertStatus(200);
        $this->assertEquals(0, $responseBlank->json('total'));
    }

    /**
     * Test that menus in closed canteens are marked as not orderable (muted).
     */
    public function test_closed_canteens_are_muted(): void
    {
        $closedCanteen = Canteen::where('is_open', false)->first();
        if (!$closedCanteen) {
            $closedCanteen = Canteen::first();
            $closedCanteen->update(['is_open' => false]);
        }

        $menu = Menu::create([
            'canteen_id' => $closedCanteen->id,
            'name' => 'Kwetiau Goreng Seafood',
            'price' => 18000,
            'category' => 'Makanan berat',
            'is_available' => true,
        ]);

        $response = $this->getJson('/cari?q=kwetiau');
        $response->assertStatus(200);

        $menus = $response->json('menus');
        $found = collect($menus)->firstWhere('id', $menu->id);

        $this->assertNotNull($found);
        $this->assertFalse($found['is_orderable']);
        $this->assertFalse($found['canteen']['is_open']);
    }

    /**
     * Test filter chip works with search query and when query is empty.
     */
    public function test_filter_chips_with_and_without_query(): void
    {
        // Filter only: Minuman with empty query
        $resFilterOnly = $this->getJson('/cari?filter=Minuman');
        $resFilterOnly->assertStatus(200);
        $menus = $resFilterOnly->json('menus');
        $this->assertNotEmpty($menus);
        foreach ($menus as $m) {
            $this->assertEquals('Minuman', $m['category']);
        }

        // Filter under 10.000
        $resBudget = $this->getJson('/cari?filter=' . urlencode('Di bawah Rp10.000'));
        $resBudget->assertStatus(200);
        foreach ($resBudget->json('menus') as $m) {
            $this->assertLessThan(10000, $m['effective_price']);
        }
    }
}
