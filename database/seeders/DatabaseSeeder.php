<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantsSeeder::class,
            SystemSettingsSeeder::class,
            UsersSeeder::class,
            CertificateTemplatesSeeder::class,
            SafetyInductionSeeder::class,
        ]);
    }
}
