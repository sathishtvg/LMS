<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;

class TenantsSeeder extends Seeder
{
  public function run(): void
  {
    Tenant::updateOrCreate(['id'=>1], [
      'code' => 'acme',
      'name' => 'ACME Pte Ltd',
      'subdomain' => 'acme',
      'status' => 'active',
      'branding_json' => [
        'portal_name' => 'LMS',
        'company_name' => 'ACME Pte Ltd',
        'primary_color' => '#2563eb',
      ],
    ]);

    Tenant::updateOrCreate(['id'=>2], [
      'code' => 'beta',
      'name' => 'Beta Services Pte Ltd',
      'subdomain' => 'beta',
      'status' => 'active',
      'branding_json' => [
        'portal_name' => 'LMS',
        'company_name' => 'Beta Services Pte Ltd',
        'primary_color' => '#2563eb',
      ],
    ]);
  }
}
