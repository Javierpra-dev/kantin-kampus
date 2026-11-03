<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Canteen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase10eCanteenProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_profile_unauthenticated_redirects_to_login(): void
    {
        $response = $this->get('/admin/profil');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_profile_view_displays_canteen_data_and_navigation(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();
        $canteen1 = $admin1->canteen;

        $response = $this->actingAs($admin1, 'admin')->get('/admin/profil');
        $response->assertStatus(200);
        $response->assertSee($canteen1->name);
        $response->assertSee('Profil Kantin');
        $response->assertSee('Simpan perubahan');

        // Navigation links: Pesanan, Menu, Profil
        $response->assertSee('Pesanan');
        $response->assertSee('Menu');
        $response->assertSee('Profil');
    }

    public function test_admin_can_update_canteen_profile_text_fields(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();
        $canteen1 = $admin1->canteen;

        $response = $this->actingAs($admin1, 'admin')->put('/admin/profil', [
            'name' => 'Kantin 1 Barokah',
            'description' => 'Menyediakan masakan nusantara dengan bumbu rempah pilihan kampus.',
            'open_time' => '07:00',
            'close_time' => '16:00',
            'whatsapp' => '081299998888',
            'is_open' => '1',
        ]);

        $response->assertRedirect('/admin/profil');
        $response->assertSessionHas('success', 'Profil kantin diperbarui');

        $canteen1->refresh();
        $this->assertEquals('Kantin 1 Barokah', $canteen1->name);
        $this->assertEquals('Menyediakan masakan nusantara dengan bumbu rempah pilihan kampus.', $canteen1->description);
        $this->assertEquals('07:00', $canteen1->open_time);
        $this->assertEquals('16:00', $canteen1->close_time);
        $this->assertEquals('07:00 - 16:00', $canteen1->opening_hours);
        $this->assertEquals('081299998888', $canteen1->whatsapp);
        $this->assertEquals('081299998888', $canteen1->phone);
        $this->assertTrue($canteen1->is_open);

        // Verify updated values appear on buyer home and canteen menu pages
        $homeResponse = $this->get('/');
        $homeResponse->assertSee('Kantin 1 Barokah');
        $homeResponse->assertSee('Menyediakan masakan nusantara dengan bumbu rempah pilihan kampus.');

        $canteenResponse = $this->get("/kantin/{$canteen1->id}");
        $canteenResponse->assertSee('Kantin 1 Barokah');
        $canteenResponse->assertSee('07:00 - 16:00');
    }

    public function test_admin_profile_validation_rules(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();

        // 1. Missing required name
        $res1 = $this->actingAs($admin1, 'admin')->put('/admin/profil', [
            'name' => '',
            'description' => 'Deskripsi valid',
        ]);
        $res1->assertSessionHasErrors('name');

        // 2. Description exceeds 120 characters
        $res2 = $this->actingAs($admin1, 'admin')->put('/admin/profil', [
            'name' => 'Kantin 1',
            'description' => str_repeat('a', 121),
        ]);
        $res2->assertSessionHasErrors('description');

        // 3. Invalid WhatsApp number format
        $res3 = $this->actingAs($admin1, 'admin')->put('/admin/profil', [
            'name' => 'Kantin 1',
            'whatsapp' => 'invalid-number',
        ]);
        $res3->assertSessionHasErrors('whatsapp');
    }

    public function test_admin_can_upload_and_reencode_canteen_photo(): void
    {
        Storage::fake('public');
        $admin1 = Admin::where('username', 'kantin1')->first();
        $canteen1 = $admin1->canteen;

        // Create fake large photo
        $file = UploadedFile::fake()->image('banner.png', 1800, 1000);

        $response = $this->actingAs($admin1, 'admin')->put('/admin/profil', [
            'name' => 'Kantin 1 Foto Baru',
            'photo' => $file,
            'open_time' => '08:00',
            'close_time' => '17:00',
        ]);

        $response->assertRedirect('/admin/profil');
        $canteen1->refresh();

        $this->assertNotNull($canteen1->photo);
        $this->assertStringStartsWith('canteens/', $canteen1->photo);
        Storage::disk('public')->assertExists($canteen1->photo);

        // Upload another photo to test deleting the old photo
        $oldPhoto = $canteen1->photo;
        $file2 = UploadedFile::fake()->image('banner2.jpg', 1200, 800);

        $response2 = $this->actingAs($admin1, 'admin')->put('/admin/profil', [
            'name' => 'Kantin 1 Foto Kedua',
            'photo' => $file2,
        ]);

        $response2->assertRedirect('/admin/profil');
        $canteen1->refresh();

        Storage::disk('public')->assertMissing($oldPhoto);
        Storage::disk('public')->assertExists($canteen1->photo);
    }

    public function test_admin_set_password_artisan_command(): void
    {
        $admin1 = Admin::where('username', 'kantin1')->first();
        $this->assertNotNull($admin1);

        $this->artisan('admin:set-password', ['username' => 'kantin1'])
            ->expectsQuestion('Masukkan password baru:', 'newSecretPass123')
            ->assertExitCode(0);

        $admin1->refresh();
        $this->assertTrue(Hash::check('newSecretPass123', $admin1->password));

        // Matching User model if exists
        $user1 = User::where('username', 'kantin1')->first();
        if ($user1) {
            $this->assertTrue(Hash::check('newSecretPass123', $user1->password));
        }
    }
}

