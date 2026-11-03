<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            [
                'canteen_id' => 1,
                'name' => 'Admin Kantin 1',
                'username' => 'kantin1',
                'email' => 'kantin1@kantin.com',
                'password' => Hash::make('password'),
                'role' => 'canteen_admin',
            ],
            [
                'canteen_id' => 2,
                'name' => 'Admin Kantin 2',
                'username' => 'kantin2',
                'email' => 'kantin2@kantin.com',
                'password' => Hash::make('password'),
                'role' => 'canteen_admin',
            ],
            [
                'canteen_id' => 3,
                'name' => 'Admin Kantin 3',
                'username' => 'kantin3',
                'email' => 'kantin3@kantin.com',
                'password' => Hash::make('password'),
                'role' => 'canteen_admin',
            ],
            [
                'canteen_id' => 4,
                'name' => 'Admin Kantin 4',
                'username' => 'kantin4',
                'email' => 'kantin4@kantin.com',
                'password' => Hash::make('password'),
                'role' => 'canteen_admin',
            ],
        ];

        foreach ($admins as $admin) {
            User::updateOrCreate(['username' => $admin['username']], $admin);
        }
    }
}
