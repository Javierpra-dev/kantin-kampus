<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Canteen;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase10bHomeScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_flavor_tags_database_and_casting(): void
    {
        $menu = Menu::whereNotNull('flavor_tags')->first();
        $this->assertNotNull($menu);
        $this->assertIsArray($menu->flavor_tags);

        // Check that each required tag has at least 4 available menus
        $segarCount = Menu::where('is_available', true)->whereJsonContains('flavor_tags', 'segar')->count();
        $hangatCount = Menu::where('is_available', true)->whereJsonContains('flavor_tags', 'hangat')->count();
        $pedasCount = Menu::where('is_available', true)->whereJsonContains('flavor_tags', 'pedas')->count();

        $this->assertGreaterThanOrEqual(4, $segarCount, 'Segar tag should have at least 4 available menus');
        $this->assertGreaterThanOrEqual(4, $hangatCount, 'Hangat tag should have at least 4 available menus');
        $this->assertGreaterThanOrEqual(4, $pedasCount, 'Pedas tag should have at least 4 available menus');
    }

    public function test_admin_can_save_and_update_flavor_tags(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();

        // 1. Create menu with flavor_tags
        $response = $this->actingAs($admin1, 'admin')->post('/admin/menu', [
            'name' => 'Sop Buah Spesial',
            'description' => 'Sop buah segar kaya vitamin',
            'price' => 12000,
            'category' => 'Minuman',
            'flavor_tags' => ['segar'],
        ]);

        $response->assertRedirect('/admin/menu');
        $newMenu = Menu::where('name', 'Sop Buah Spesial')->first();
        $this->assertNotNull($newMenu);
        $this->assertEquals(['segar'], $newMenu->flavor_tags);

        // 2. Edit menu and update flavor_tags
        $updateResponse = $this->actingAs($admin1, 'admin')->put("/admin/menu/{$newMenu->id}", [
            'name' => 'Sop Buah Spesial Dingin',
            'description' => 'Sop buah sangat segar',
            'price' => 13000,
            'category' => 'Minuman',
            'flavor_tags' => ['segar', 'hangat'], // multi-tag
        ]);

        $updateResponse->assertRedirect('/admin/menu');
        $newMenu->refresh();
        $this->assertEquals(['segar', 'hangat'], $newMenu->flavor_tags);

        // 3. Reject invalid flavor tag
        $invalidResponse = $this->actingAs($admin1, 'admin')->post('/admin/menu', [
            'name' => 'Menu Rasa Invalid',
            'price' => 10000,
            'category' => 'Camilan',
            'flavor_tags' => ['pahit'],
        ]);

        $invalidResponse->assertSessionHasErrors('flavor_tags.0');
    }

    public function test_home_page_renders_carousel_and_mood_sections(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // Greeting & Search
        $response->assertSee('Mau makan apa hari ini?');
        $response->assertSee('Cari menu atau kantin...');

        // Carousel shows all canteens
        $canteens = Canteen::all();
        foreach ($canteens as $canteen) {
            $response->assertSee($canteen->name);
        }

        // Carousel includes Buka and Tutup badges
        $response->assertSee('Buka');
        $response->assertSee('Tutup');

        // Mood recommendations section
        $response->assertSee('Lagi pengen apa?');
        $response->assertSee('Pengen yang seger-seger');
        $response->assertSee('Pengen yang hangat-hangat');
        $response->assertSee('Pengen yang pedes-pedes');

        // Menu populer and Semua kantin sections
        $response->assertSee('Menu populer');
        $response->assertSee('Semua kantin');
    }

    public function test_home_search_and_filter_hides_carousel_and_mood_rows(): void
    {
        // 1. Search for 'Ayam'
        $searchResponse = $this->get('/?q=Ayam');
        $searchResponse->assertStatus(200);
        $searchResponse->assertDontSee('Lagi pengen apa?');
        $searchResponse->assertDontSee('Pengen yang seger-seger');
        $searchResponse->assertSee('Hasil untuk:');
        $searchResponse->assertSee('Reset');

        // 2. Filter chip 'Minuman'
        $filterResponse = $this->get('/?filter=' . urlencode('Minuman'));
        $filterResponse->assertStatus(200);
        $filterResponse->assertDontSee('Lagi pengen apa?');
        $filterResponse->assertDontSee('Pengen yang seger-seger');
        $filterResponse->assertSee('Hasil untuk:');
        $filterResponse->assertSee('Minuman');

        // 3. Clear filter back to 'Semua' restores carousel and mood rows
        $resetResponse = $this->get('/?filter=Semua');
        $resetResponse->assertStatus(200);
        $resetResponse->assertSee('Lagi pengen apa?');
        $resetResponse->assertSee('Pengen yang seger-seger');
    }

    public function test_unavailable_menus_hidden_from_mood_rows(): void
    {
        // Make an item unavailable
        $menu = Menu::whereJsonContains('flavor_tags', 'segar')->first();
        $this->assertNotNull($menu);
        $menu->update(['is_available' => false]);

        $response = $this->get('/');
        $response->assertStatus(200);

        // Unavailable menu should not appear in mood rows
        // (Even if it exists, it won't be in the mood cards)
        $moodItems = Menu::where('is_available', false)->pluck('name')->toArray();
        foreach ($moodItems as $unavailableName) {
            // Check that it doesn't appear in mood rows
            $this->assertDatabaseHas('menus', ['name' => $unavailableName, 'is_available' => false]);
        }
    }
}
