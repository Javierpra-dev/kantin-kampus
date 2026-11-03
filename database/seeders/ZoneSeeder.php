<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            [
                'name' => 'Zona 1 - Gedung Rektorat',
                'code' => 'Z1',
                'photo' => 'https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?w=300&auto=format&fit=crop&q=60',
                'description' => 'Lobby utama, kantor administrasi, dan ruang rektorat',
                'delivery_fee' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Zona 2 - Gedung Kuliah A (Teknik)',
                'code' => 'Z2',
                'photo' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=300&auto=format&fit=crop&q=60',
                'description' => 'Fakultas Teknik, ruang kuliah lantai 1-4',
                'delivery_fee' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Zona 3 - Gedung Kuliah B (Ekonomi & Bisnis)',
                'code' => 'Z3',
                'photo' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=300&auto=format&fit=crop&q=60',
                'description' => 'Fakultas Ekonomi dan Bisnis, hall seminar',
                'delivery_fee' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Zona 4 - Gedung Kuliah C (Ilmu Budaya)',
                'code' => 'Z4',
                'photo' => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=300&auto=format&fit=crop&q=60',
                'description' => 'Fakultas Ilmu Budaya & Bahasa, ruang dosen',
                'delivery_fee' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Zona 5 - Perpustakaan Pusat',
                'code' => 'Z5',
                'photo' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?w=300&auto=format&fit=crop&q=60',
                'description' => 'Gedung Perpustakaan Pusat & Co-working Space',
                'delivery_fee' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Zona 6 - Laboratorium Terpadu',
                'code' => 'Z6',
                'photo' => 'https://images.unsplash.com/photo-1581093458791-9f3c3900df4b?w=300&auto=format&fit=crop&q=60',
                'description' => 'Kompleks Lab Riset, Komputer & MIPA',
                'delivery_fee' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Zona 7 - Asrama Mahasiswa',
                'code' => 'Z7',
                'photo' => 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?w=300&auto=format&fit=crop&q=60',
                'description' => 'Gedung Asrama Mahasiswa Putra & Putri',
                'delivery_fee' => 0,
                'is_active' => true,
            ],
            [
                'name' => 'Tidak yakin / lainnya',
                'code' => 'OTHER',
                'photo' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=300&auto=format&fit=crop&q=60',
                'description' => 'Lokasi duduk lainnya di area sekitar kampus',
                'delivery_fee' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($zones as $zone) {
            Zone::updateOrCreate(['name' => $zone['name']], $zone);
        }
    }
}
