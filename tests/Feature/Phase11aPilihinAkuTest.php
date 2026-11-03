<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\Menu;
use App\Models\SuggestionLog;
use App\Services\CartService;
use App\Services\SuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase11aPilihinAkuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. Entry point button appears on buyer home page.
     */
    public function test_entry_point_button_appears_on_home_page(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Bingung mau makan apa? Pilihin aku');
        $response->assertSee(url('/pilihin-aku'));
    }

    /**
     * 2. /pilihin-aku renders the questionnaire questions, budget chips, mood chips, and Cariin button.
     */
    public function test_pilihin_aku_page_renders_questionnaire(): void
    {
        $response = $this->get('/pilihin-aku');
        $response->assertStatus(200);
        $response->assertSee('Budget kamu berapa?');
        $response->assertSee('Rp10.000', false);
        $response->assertSee('Rp15.000', false);
        $response->assertSee('Rp20.000', false);
        $response->assertSee('Rp25.000', false);
        $response->assertSee('Lagi pengen yang gimana?');
        $response->assertSee('Segar');
        $response->assertSee('Hangat');
        $response->assertSee('Pedas');
        $response->assertSee('Bebas');
        $response->assertSee('Cariin');
    }

    /**
     * 3. Suggestion combos never exceed the given budget.
     */
    public function test_suggestion_service_never_exceeds_budget(): void
    {
        $service = app(SuggestionService::class);

        foreach ([12000, 15000, 18000, 22000, 30000] as $budget) {
            $suggestions = $service->getSuggestions($budget, 'bebas');
            foreach ($suggestions as $combo) {
                $this->assertLessThanOrEqual(
                    $budget,
                    $combo['total_price'],
                    "Combo total price {$combo['total_price']} exceeds budget {$budget}"
                );
            }
        }
    }

    /**
     * 4. Suggestion service uses only open canteens and available menus.
     */
    public function test_suggestion_service_uses_only_open_canteens_and_available_menus(): void
    {
        // Close one canteen
        $closedCanteen = Canteen::first();
        $closedCanteen->update(['is_open' => false]);

        // Make one specific menu unavailable in an open canteen
        $openCanteen = Canteen::where('is_open', true)->first();
        $unavailableMenu = $openCanteen->menus()->first();
        $unavailableMenu->update(['is_available' => false]);

        $service = app(SuggestionService::class);
        $suggestions = $service->getSuggestions(50000, 'bebas');

        $this->assertNotEmpty($suggestions);

        foreach ($suggestions as $combo) {
            $this->assertNotEquals(
                $closedCanteen->id,
                $combo['canteen']->id,
                'Closed canteen should never be suggested'
            );
            $this->assertNotEquals(
                $unavailableMenu->id,
                $combo['main']->id,
                'Unavailable menu should never be suggested as main'
            );
            if ($combo['drink']) {
                $this->assertNotEquals(
                    $unavailableMenu->id,
                    $combo['drink']->id,
                    'Unavailable menu should never be suggested as drink'
                );
            }
        }
    }

    /**
     * 5. Mood tag filter works: at least one item in the combo has the requested mood tag.
     */
    public function test_suggestion_service_filters_by_mood_tag(): void
    {
        $service = app(SuggestionService::class);

        foreach (['pedas', 'segar', 'hangat'] as $mood) {
            $suggestions = $service->getSuggestions(30000, $mood);

            $this->assertNotEmpty($suggestions, "Suggestions should not be empty for mood: {$mood}");

            foreach ($suggestions as $combo) {
                $mainTags = array_map('strtolower', $combo['main']->flavor_tags ?? []);
                $drinkTags = $combo['drink'] ? array_map('strtolower', $combo['drink']->flavor_tags ?? []) : [];

                $hasMood = in_array($mood, $mainTags) || in_array($mood, $drinkTags);
                $this->assertTrue($hasMood, "At least one item in combo must have mood: {$mood}");
            }
        }
    }

    /**
     * 6. Suggestion service falls back to single main item when no combo fits budget.
     */
    public function test_suggestion_service_falls_back_to_single_main_when_no_combo_fits(): void
    {
        // Temporarily make drinks expensive so no combo fits a very tight budget
        Menu::where('category', 'Minuman')->update(['price' => 50000]);

        $service = app(SuggestionService::class);
        // Budget 15.000: Drinks are 50.000, but some mains are <= 15.000
        $suggestions = $service->getSuggestions(15000, 'bebas');

        $this->assertNotEmpty($suggestions);
        foreach ($suggestions as $item) {
            $this->assertFalse($item['is_combo']);
            $this->assertNull($item['drink']);
            $this->assertLessThanOrEqual(15000, $item['total_price']);
            $this->assertEquals('Makanan berat', $item['main']->category);
        }
    }

    /**
     * 7. Ordering a suggestion puts items into cart through CartService and redirects to /keranjang.
     */
    public function test_ordering_suggestion_populates_cart_and_redirects(): void
    {
        $openCanteen = Canteen::where('is_open', true)->first();
        $main = $openCanteen->menus()->where('category', 'Makanan berat')->where('is_available', true)->first();
        $drink = $openCanteen->menus()->where('category', 'Minuman')->where('is_available', true)->first();

        $log = SuggestionLog::create([
            'budget' => 20000,
            'mood' => 'bebas',
            'results_count' => 1,
            'chosen' => false,
        ]);

        $response = $this->post('/pilihin-aku/pesan', [
            'menu_ids' => [$main->id, $drink->id],
            'log_id' => $log->id,
        ]);

        $response->assertRedirect('/keranjang');

        $cartService = app(CartService::class);
        $cart = $cartService->get();

        $this->assertEquals($openCanteen->id, $cart['canteen_id']);
        $this->assertArrayHasKey($main->id, $cart['items']);
        $this->assertArrayHasKey($drink->id, $cart['items']);

        // Check research data: chosen = true
        $this->assertTrue($log->fresh()->chosen);
    }

    /**
     * 8. Research data: suggestion_logs records search queries and tracks results_count.
     */
    public function test_suggestion_logs_records_search(): void
    {
        $this->assertEquals(0, SuggestionLog::count());

        $response = $this->get('/pilihin-aku?budget=25000&mood=pedas');
        $response->assertStatus(200);

        $this->assertEquals(1, SuggestionLog::count());
        $log = SuggestionLog::first();

        $this->assertEquals(25000, $log->budget);
        $this->assertEquals('pedas', $log->mood);
        $this->assertGreaterThan(0, $log->results_count);
        $this->assertFalse($log->chosen);
    }

    /**
     * 9. Cart conflict handling: returns conflict if cart contains items from another canteen.
     */
    public function test_cart_conflict_asks_to_empty_cart(): void
    {
        $canteens = Canteen::where('is_open', true)->take(2)->get();
        $this->assertCount(2, $canteens);

        $canteenA = $canteens[0];
        $canteenB = $canteens[1];

        // Put Canteen A item into cart
        $cartService = app(CartService::class);
        $menuA = $canteenA->menus()->first();
        $cartService->add($menuA);

        $menuB = $canteenB->menus()->first();

        // Try to order from Canteen B without force via JSON
        $response = $this->postJson('/pilihin-aku/pesan', [
            'menu_ids' => [$menuB->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'conflict' => true,
        ]);

        // Cart still belongs to Canteen A
        $this->assertEquals($canteenA->id, $cartService->get()['canteen_id']);

        // Now order with force = true
        $responseForce = $this->postJson('/pilihin-aku/pesan', [
            'menu_ids' => [$menuB->id],
            'force' => true,
        ]);

        $responseForce->assertStatus(200);
        $responseForce->assertJson([
            'success' => true,
            'redirect' => route('cart.index'),
        ]);

        // Cart now belongs to Canteen B
        $this->assertEquals($canteenB->id, $cartService->get()['canteen_id']);
    }
}

