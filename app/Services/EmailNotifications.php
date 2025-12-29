<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\User;
use App\Models\Certificate;
use App\Notifications\EnrollmentAssigned;
use App\Notifications\CourseCompleted;
use Illuminate\Support\Arr;

class EmailNotifications
{
    public function __construct(private SystemSettings $settings) {}

    public function enabled(): bool
    {
        $cfg = $this->settings->get('email.config', []);
        return (bool)($cfg['enabled'] ?? false);
    }

    public function sendEnrollmentAssigned(Enrollment $enrollment): void
    {
        if (!$this->enabled()) return;

        $cfg = $this->settings->get('email.config', []);
        $tpl = Arr::get($cfg, 'templates.enrollment_assigned', []);
        $user = $enrollment->user;
        if (!$user?->email) return;

        $payload = $this->renderTemplate($tpl, [
            'learner_name' => $user->name,
            'course_title' => $this->courseTitle($enrollment),
            'course_name' => $this->courseTitle($enrollment),
            'course_name' => $this->courseTitle($enrollment),
            'course_name' => $this->courseTitle($enrollment),
            'due_date' => $enrollment->due_date ? date('d/m/Y', strtotime($enrollment->due_date)) : '—',
            'portal_url' => config('app.url'),
            'company_name' => $this->companyName(),
        ]);

        $user->notify(new EnrollmentAssigned($payload));
    }

    public function sendCourseCompleted(Enrollment $enrollment, ?Certificate $certificate): void
    {
        if (!$this->enabled()) return;

        $cfg = $this->settings->get('email.config', []);
        $tpl = Arr::get($cfg, 'templates.course_completed', []);
        $user = $enrollment->user;
        if (!$user?->email) return;

        $certUrl = $certificate ? (config('app.url')."/api/certificates/{$certificate->id}/download") : null;

        $payload = $this->renderTemplate($tpl, [
            'learner_name' => $user->name,
            'course_title' => $this->courseTitle($enrollment),
            'course_name' => $this->courseTitle($enrollment),
            'portal_url' => config('app.url'),
            'certificate_url' => $certUrl,
            'company_name' => $this->companyName(),
        ]);

        $user->notify(new CourseCompleted($payload));
    }

    private function renderTemplate(array $tpl, array $vars): array
    {
        $subject = (string)($tpl['subject'] ?? 'LMS Notification');
        $body = (string)($tpl['body'] ?? '');

        foreach ($vars as $k=>$v) {
            $subject =  $subject = str_replace('{{'.$k.'}}', (string)$v, $subject);
            $subject = str_replace('{{ '.$k.' }}', (string)$v, $subject);
            $body =  $body = str_replace('{{'.$k.'}}', (string)$v, $body);
            $body = str_replace('{{ '.$k.' }}', (string)$v, $body);
        }
        return [
            'subject' => $subject,
            'body' => $body,
            ...$vars,
        ];
    }

    private function courseTitle(Enrollment $enrollment): string
    {
        $c = $enrollment->course;
        return $c?->translations?->first()?->title ?? $c?->title ?? $c?->code ?? ('Course '.$enrollment->course_id);
    }

    private function companyName(): string
    {
        $branding = $this->settings->get('branding.config', ['company_name'=>'LMS']);
        return (string)($branding['company_name'] ?? 'LMS');
    }
}
