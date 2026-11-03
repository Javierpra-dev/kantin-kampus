<?php

namespace Database\Seeders;

use App\Models\Canteen;
use Illuminate\Database\Seeder;

class CanteenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $canteens = [
            [
                'id' => 1,
                'name' => 'Kantin 1',
                'slug' => 'kantin-1',
                'description' => 'Spesialis Aneka Nasi Goreng & Masakan Tradisional Kampus',
                'photo' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=500&auto=format&fit=crop&q=60',
                'is_open' => true,
                'opening_hours' => '07:30 - 16:30',
                'open_time' => '07:30',
                'close_time' => '16:30',
                'phone' => '081234567801',
                'whatsapp' => '081234567801',
            ],
            [
                'id' => 2,
                'name' => 'Kantin 2',
                'slug' => 'kantin-2',
                'description' => 'Pojok Mie Ayam Pangsit & Bakso Sapi Segar',
                'photo' => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=500&auto=format&fit=crop&q=60',
                'is_open' => true,
                'opening_hours' => '08:00 - 17:00',
                'open_time' => '08:00',
                'close_time' => '17:00',
                'phone' => '081234567802',
                'whatsapp' => '081234567802',
            ],
            [
                'id' => 3,
                'name' => 'Kantin 3',
                'slug' => 'kantin-3',
                'description' => 'Ayam Geprek Sambal Bawang & Aneka Masakan Pedas',
                'photo' => 'https://images.unsplash.com/photo-1626777552726-4a6b54c97e46?w=500&auto=format&fit=crop&q=60',
                'is_open' => true,
                'opening_hours' => '08:00 - 18:00',
                'open_time' => '08:00',
                'close_time' => '18:00',
                'phone' => '081234567803',
                'whatsapp' => '081234567803',
            ],
            [
                'id' => 4,
                'name' => 'Kantin 4',
                'slug' => 'kantin-4',
                'description' => 'Jajanan Pasar, Gorengan Hangat & Aneka Minuman Segar',
                'photo' => 'https://images.unsplash.com/photo-1509722747041-616f39b57569?w=500&auto=format&fit=crop&q=60',
                'is_open' => false, // Set to false to test closed canteen notice and disabled ordering
                'opening_hours' => '09:00 - 15:00',
                'open_time' => '09:00',
                'close_time' => '15:00',
                'phone' => '081234567804',
                'whatsapp' => '081234567804',
            ],
        ];

        foreach ($canteens as $canteen) {
            Canteen::updateOrCreate(['id' => $canteen['id']], $canteen);
        }
    }
}
