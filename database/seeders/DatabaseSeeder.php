<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ZoneSeeder::class,
            CanteenSeeder::class,
            UserSeeder::class,
            AdminSeeder::class,
            MenuSeeder::class,
        ]);
    }
}
