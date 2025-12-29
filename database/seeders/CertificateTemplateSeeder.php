<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CertificateTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
          ['code'=>'corporate_classic','name'=>'Corporate Classic'],
          ['code'=>'modern_clean','name'=>'Modern Clean'],
          ['code'=>'gold_seal','name'=>'Gold Seal'],
          ['code'=>'academy_badge','name'=>'Academy Badge'],
          ['code'=>'compliance_safety','name'=>'Compliance/Safety Style'],
          ['code'=>'minimal_white','name'=>'Minimal White'],
        ];

        foreach ($templates as $t) {
          DB::table('certificate_templates')->updateOrInsert(
            ['code'=>$t['code']],
            ['name'=>$t['name'],'layout_json'=>json_encode(['placeholders'=>['name','course','issued_at','expires_at','certificate_no','qr']]),'updated_at'=>now(),'created_at'=>now()]
          );
        }
    }
}
