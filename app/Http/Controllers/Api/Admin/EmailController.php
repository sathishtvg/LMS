<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function preview(Request $request, SystemSettings $settings)
    {
        if ($request->user()->role !== 'admin') abort(403);

        $data = $request->validate([
            'template_key' => ['required','in:enrollment_assigned,course_completed,certificate_issued'],
            'vars' => ['nullable','array'],
        ]);

        $cfg = $settings->get('email.config', []);
        $tpl = $cfg['templates'][$data['template_key']] ?? ['subject'=>'','body'=>''];

        $vars = $data['vars'] ?? [];
        $branding = $settings->get('branding.config', ['portal_name'=>'LMS','company_name'=>'LMS']);
        $vars = array_merge([
            'portal_url' => config('app.url'),
            'company_name' => $branding['company_name'] ?? 'LMS',
        ], $vars);

        return response()->json([
            'subject' => $this->render((string)($tpl['subject'] ?? ''), $vars),
            'body' => $this->render((string)($tpl['body'] ?? ''), $vars),
        ]);
    }

    public function sendTest(Request $request, SystemSettings $settings)
    {
        if ($request->user()->role !== 'admin') abort(403);

        $data = $request->validate([
            'to' => ['nullable','email'],
            'template_key' => ['nullable','in:enrollment_assigned,course_completed,certificate_issued'],
        ]);

        $cfg = $settings->get('email.config', ['enabled'=>false,'from_name'=>'LMS','from_email'=>null,'templates'=>[]]);
        if (!($cfg['enabled'] ?? false)) {
            return response()->json(['message'=>'Email notifications are disabled'], 422);
        }

        $to = $data['to'] ?: $request->user()->email;
        if (!$to) return response()->json(['message'=>'No recipient email available'], 422);

        $fromName = $cfg['from_name'] ?? 'LMS';
        $fromEmail = $cfg['from_email'] ?? config('mail.from.address');

        $key = $data['template_key'] ?? 'enrollment_assigned';
        $tpl = $cfg['templates'][$key] ?? ['subject'=>'[LMS] Test Email','body'=>'This is a test email from LMS.'];

        $branding = $settings->get('branding.config', ['portal_name'=>'LMS','company_name'=>'LMS']);
        $vars = [
            'learner_name' => 'Test User',
            'course_name' => 'Safety Induction',
            'due_date' => '—',
            'portal_url' => config('app.url'),
            'company_name' => $branding['company_name'] ?? 'LMS',
            'certificate_url' => config('app.url').'/learner/certificates',
        ];

        $subject = $this->render((string)($tpl['subject'] ?? ''), $vars);
        $body = $this->render((string)($tpl['body'] ?? ''), $vars);

        Mail::raw($body, function($m) use ($to, $subject, $fromName, $fromEmail) {
            $m->to($to)->subject($subject ?: '[LMS] Test Email');
            if ($fromEmail) $m->from($fromEmail, $fromName);
        });

        return response()->json(['ok'=>true]);
    }

    private function render(string $text, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $text = str_replace('{{ '.$k.' }}', (string)$v, $text);
            $text = str_replace('{{'.$k.'}}', (string)$v, $text);
        }
        return $text;
    }
}
