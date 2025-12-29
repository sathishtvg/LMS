<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
          // Used by web UI layout + certificate PDF
          'branding.config' => [
            'portal_name' => 'LMS',
            'company_name' => 'LMS',
            'primary_color' => '#2563eb',
            'logo_path' => null,
          ],

          // Used by upload endpoint enforcement + admin settings UI
          'uploads.rules' => [
            'max_mb' => 50,
            'allowed_mimes' => [
              'pdf' => ['application/pdf'],
              'video' => ['video/mp4','video/quicktime','video/x-matroska'],
              'ppt' => [
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
              ],
              'image' => ['image/jpeg','image/png','image/webp'],
              'other' => ['application/zip','text/plain'],
            ],
          ],

          // Local-only in Phase 1–2
          'storage.config' => ['mode' => 'local', 'private_assets' => false],

          // Informational settings (future phases)
          'languages.supported' => ['en'=>true,'ms'=>true,'ta'=>true,'zh'=>true],
          'timezone.default' => 'Asia/Singapore',
          'date_formats' => ['date'=>'d/m/Y','datetime'=>'d/m/Y h:i A'],
          'auth.login_mode' => ['email'=>true,'phone'=>true,'otp_sms'=>false],
          'email.config' => [
            'enabled' => false,
            'from_name' => 'LMS',
            'from_email' => null,
            'templates' => [
              'enrollment_assigned' => [
                'subject' => '[LMS] You have been enrolled: {{course_title}}',
                'body' => "Hello {{learner_name}},\n\nYou have been enrolled in: {{course_title}}.\nDue date: {{due_date}}\n\nLogin: {{portal_url}}\n\nRegards,\n{{company_name}}"
              ],
              'course_completed' => [
                'subject' => '[LMS] Course completed: {{course_title}}',
                'body' => "Hello {{learner_name}},\n\nYou have completed: {{course_title}}.\nYou can download your certificate here: {{certificate_url}}\n\nRegards,\n{{company_name}}"
              ],
            ],
          ],
        ];

        foreach ($defaults as $key=>$value) {
          DB::table('system_settings')->updateOrInsert(
            ['key'=>$key],
            ['value_json'=>json_encode($value),'updated_by'=>null,'updated_at'=>now()]
          );
        }
    }
}
