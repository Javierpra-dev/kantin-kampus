<?php

namespace Tests\Feature\Api\V1;

use App\Models\Canteen;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanteenAndSearchApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. GET /api/v1/search returns menus with canteen name and open status.
     */
    public function test_api_search_returns_menus_with_canteen_name_and_status(): void
    {
        $canteen = Canteen::first();
        $canteen->update(['name' => 'Kantin Pusat Bu Siti', 'is_open' => true]);

        Menu::create([
            'canteen_id' => $canteen->id,
            'name' => 'Ayam Penyet Sambal Ijo',
            'price' => 18000,
            'category' => 'Makanan berat',
            'is_available' => true,
        ]);

        $response = $this->getJson('/api/v1/search?q=penyet');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'query',
                'menus' => [
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'effective_price',
                        'is_available',
                        'canteen' => ['id', 'name', 'is_open'],
                    ]
                ],
                'canteens',
                'total',
            ]
        ]);

        $menus = $response->json('data.menus');
        $this->assertNotEmpty($menus);
        $found = collect($menus)->firstWhere('name', 'Ayam Penyet Sambal Ijo');
        $this->assertNotNull($found);
        $this->assertEquals('Kantin Pusat Bu Siti', $found['canteen']['name']);
        $this->assertTrue($found['canteen']['is_open']);
    }

    /**
     * 2. GET /api/v1/canteens returns canteens with popular menu names.
     */
    public function test_get_canteens_list(): void
    {
        $response = $this->getJson('/api/v1/canteens');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'photo_url',
                    'is_open',
                    'popular_menus',
                ]
            ]
        ]);
    }

    /**
     * 3. GET /api/v1/canteens/{id}/menus returns menus with promo and stock.
     */
    public function test_get_canteen_menus(): void
    {
        $canteen = Canteen::first();

        Menu::create([
            'canteen_id' => $canteen->id,
            'name' => 'Es Jeruk Manis',
            'price' => 5000,
            'promo_price' => 4000,
            'promo_start' => '10:00:00',
            'promo_end' => '22:00:00',
            'daily_stock' => 3,
            'category' => 'Minuman',
            'is_available' => true,
        ]);

        $response = $this->getJson("/api/v1/canteens/{$canteen->id}/menus");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'category',
                    'price',
                    'effective_price',
                    'is_available',
                    'daily_stock',
                    'flavor_tags',
                    'photo_url',
                ]
            ]
        ]);
    }

    /**
     * 4. GET /api/v1/menus/popular and GET /api/v1/menus/mood.
     */
    public function test_popular_and_mood_menus(): void
    {
        $popularRes = $this->getJson('/api/v1/menus/popular');
        $popularRes->assertStatus(200);
        $this->assertLessThanOrEqual(8, count($popularRes->json('data')));

        $moodRes = $this->getJson('/api/v1/menus/mood?tag=segar');
        $moodRes->assertStatus(200);
        $this->assertLessThanOrEqual(8, count($moodRes->json('data')));

        $invalidMood = $this->getJson('/api/v1/menus/mood?tag=invalid');
        $invalidMood->assertStatus(422);
    }

    /**
     * 5. GET /api/v1/zones returns active campus delivery zones.
     */
    public function test_get_zones_list(): void
    {
        $response = $this->getJson('/api/v1/zones');
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
        $this->assertArrayHasKey('delivery_fee', $response->json('data.0'));
    }

    /**
     * 6. POST /api/v1/suggestions returns combo suggestions.
     */
    public function test_post_suggestions(): void
    {
        $response = $this->postJson('/api/v1/suggestions', [
            'budget' => 25000,
            'mood' => 'segar',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'canteen',
                    'is_combo',
                    'total',
                    'main',
                ]
            ]
        ]);
    }
}

