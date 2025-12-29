<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Backward-compatible: reuse existing UserSeeder logic
        $this->call([
            UserSeeder::class,
        ]);
    }
}
