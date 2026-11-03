<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Canteen;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_menu_index_shows_canteen_menu_and_title(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();
        $this->assertNotNull($admin1);
        $canteen1 = $admin1->canteen;

        $response = $this->actingAs($admin1, 'admin')->get('/admin/menu');
        $response->assertStatus(200);
        $response->assertSee('Menu ' . $canteen1->name);

        // Check that Kantin 1 menu is visible
        $menuK1 = Menu::where('canteen_id', $canteen1->id)->first();
        $response->assertSee($menuK1->name);

        // Check that another canteen's exclusive menu is NOT visible
        $canteen2 = Canteen::find(2);
        $menuK2 = Menu::where('canteen_id', $canteen2->id)->where('name', 'Mie Ayam Pangsit')->first();
        if ($menuK2) {
            $response->assertDontSee($menuK2->name);
        }
    }

    public function test_admin_menu_unauthenticated_redirects_to_login(): void
    {
        $response = $this->get('/admin/menu');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_toggle_menu_availability(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();
        $menu = Menu::where('canteen_id', $admin1->canteen_id)->first();
        $initialState = $menu->is_available;

        $response = $this->actingAs($admin1, 'admin')
            ->postJson("/admin/menu/{$menu->id}/toggle-availability");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_available' => !$initialState,
        ]);

        $menu->refresh();
        $this->assertEquals(!$initialState, $menu->is_available);
    }

    public function test_admin_cannot_toggle_another_canteens_menu(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();
        $menuK2 = Menu::where('canteen_id', 2)->first();

        $response = $this->actingAs($admin1, 'admin')
            ->postJson("/admin/menu/{$menuK2->id}/toggle-availability");

        $response->assertStatus(404);
    }

    public function test_admin_can_create_menu_with_photo_upload(): void
    {
        Storage::fake('public');

        $admin1 = Admin::where('username', 'kantin1')->first();

        // View create form
        $createPage = $this->actingAs($admin1, 'admin')->get('/admin/menu/create');
        $createPage->assertStatus(200);
        $createPage->assertSee('Tambah Menu Baru');

        $photo = UploadedFile::fake()->image('bakwan.jpg', 600, 600);

        $response = $this->actingAs($admin1, 'admin')->post('/admin/menu', [
            'name' => 'Bakwan Jagung Spesial',
            'description' => 'Bakwan jagung manis renyah khas rumahan',
            'price' => 5000,
            'category' => 'Camilan',
            'photo' => $photo,
        ]);

        $response->assertRedirect('/admin/menu');
        $response->assertSessionHas('success');

        $createdMenu = Menu::where('name', 'Bakwan Jagung Spesial')->first();
        $this->assertNotNull($createdMenu);
        $this->assertEquals($admin1->canteen_id, $createdMenu->canteen_id);
        $this->assertEquals(5000, $createdMenu->price);
        $this->assertEquals('Camilan', $createdMenu->category);
        $this->assertNotNull($createdMenu->photo);

        // Verify photo stored in storage/app/public/menus
        Storage::disk('public')->assertExists($createdMenu->photo);
    }

    public function test_create_menu_validation_rules(): void
    {
        Storage::fake('public');

        $admin1 = Admin::where('username', 'kantin1')->first();

        // Missing required fields
        $responseMissing = $this->actingAs($admin1, 'admin')->post('/admin/menu', []);
        $responseMissing->assertSessionHasErrors(['name', 'price', 'category']);

        // Non-integer price
        $responsePrice = $this->actingAs($admin1, 'admin')->post('/admin/menu', [
            'name' => 'Test Menu',
            'price' => 'bukan-angka',
            'category' => 'Minuman',
        ]);
        $responsePrice->assertSessionHasErrors('price');

        // File too large (> 2 MB = 2048 KB)
        $largePhoto = UploadedFile::fake()->create('large.jpg', 3000); // 3 MB
        $responseLarge = $this->actingAs($admin1, 'admin')->post('/admin/menu', [
            'name' => 'Test Menu',
            'price' => 10000,
            'category' => 'Minuman',
            'photo' => $largePhoto,
        ]);
        $responseLarge->assertSessionHasErrors('photo');
    }

    public function test_admin_can_edit_own_menu(): void
    {
        Storage::fake('public');

        $admin1 = Admin::where('username', 'kantin1')->first();
        $menu = Menu::where('canteen_id', $admin1->canteen_id)->first();

        // View edit page
        $editPage = $this->actingAs($admin1, 'admin')->get("/admin/menu/{$menu->id}/edit");
        $editPage->assertStatus(200);
        $editPage->assertSee($menu->name);

        // Submit edit
        $newPhoto = UploadedFile::fake()->image('updated.jpg', 500, 500);

        $response = $this->actingAs($admin1, 'admin')->put("/admin/menu/{$menu->id}", [
            'name' => 'Nasi Goreng Premium Update',
            'description' => 'Deskripsi baru yang diperbarui',
            'price' => 22000,
            'category' => 'Makanan berat',
            'photo' => $newPhoto,
        ]);

        $response->assertRedirect('/admin/menu');
        $menu->refresh();
        $this->assertEquals('Nasi Goreng Premium Update', $menu->name);
        $this->assertEquals(22000, $menu->price);
        $this->assertEquals('Deskripsi baru yang diperbarui', $menu->description);
        Storage::disk('public')->assertExists($menu->photo);
    }

    public function test_admin_cannot_edit_another_canteens_menu(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();
        $menuK2 = Menu::where('canteen_id', 2)->first();

        $response = $this->actingAs($admin1, 'admin')->get("/admin/menu/{$menuK2->id}/edit");
        $response->assertStatus(404);

        $responseUpdate = $this->actingAs($admin1, 'admin')->put("/admin/menu/{$menuK2->id}", [
            'name' => 'Hacked Menu',
            'price' => 1000,
            'category' => 'Minuman',
        ]);
        $responseUpdate->assertStatus(404);
    }

    public function test_admin_can_delete_own_menu_and_not_others(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();
        $menu = Menu::where('canteen_id', $admin1->canteen_id)->first();
        $menuId = $menu->id;

        // Delete own menu
        $response = $this->actingAs($admin1, 'admin')->delete("/admin/menu/{$menuId}");
        $response->assertRedirect('/admin/menu');
        $this->assertNull(Menu::find($menuId));

        // Try deleting another canteen's menu
        $menuK2 = Menu::where('canteen_id', 2)->first();
        $responseK2 = $this->actingAs($admin1, 'admin')->delete("/admin/menu/{$menuK2->id}");
        $responseK2->assertStatus(404);
        $this->assertNotNull(Menu::find($menuK2->id));
    }
}
