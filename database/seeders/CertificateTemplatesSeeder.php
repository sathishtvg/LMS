<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CertificateTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // Backward-compatible: reuse existing CertificateTemplateSeeder logic
        $this->call([
            CertificateTemplateSeeder::class,
        ]);
    }
}
