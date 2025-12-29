<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\SystemSettings;

class CertificateService
{
    public function issueForEnrollment(Enrollment $enrollment): ?Certificate
    {
        $enrollment->loadMissing(['user','course','course.translations']);

        $course = $enrollment->course;
        if (!$course || !(bool)$course->certificate_enabled) {
            return null;
        }

        // Certificate requires course completed
        if (!$this->isEnrollmentCompleted($enrollment)) {
            return null;
        }

        $cert = Certificate::where('enrollment_id', $enrollment->id)->first();
        if (!$cert) {
            $cert = Certificate::create([
                'course_id' => $course->id,
                'user_id' => $enrollment->user_id,
                'enrollment_id' => $enrollment->id,
                'certificate_no' => $this->generateCertificateNo(),
                'issued_at' => now(),
                'expires_at' => null,
                'status' => 'issued',
                'pdf_path' => null,
                'verification_token' => Str::random(48),
            ]);
        }

        // Generate PDF if missing (or file missing)
        if (!$cert->pdf_path || !$this->pdfExists($cert->pdf_path)) {
            $relativePath = "certificates/{$cert->certificate_no}.pdf";

            $pdf = Pdf::loadView('pdf.certificate_phase1', [
                'cert' => $cert,
                'user' => $enrollment->user,
                'course' => $course,
                'companyName' => $this->branding()['company_name'] ?? 'LMS',
                'logoPath' => $this->branding()['logo_path'] ?? null,
                'primaryColor' => $this->branding()['primary_color'] ?? '#2563eb',
                'verifyUrl' => url('/verify/'.$cert->verification_token),
            ])->setPaper('a4', 'landscape');

            Storage::disk('public')->put($relativePath, $pdf->output());
            $cert->update(['pdf_path' => "/storage/{$relativePath}"]);
        }

        return $cert;
    }

    private function branding(): array
    {
        /** @var SystemSettings $settings */
        $settings = app(SystemSettings::class);
        return $settings->get('branding.config', [
            'portal_name' => 'LMS',
            'company_name' => 'LMS',
            'primary_color' => '#2563eb',
            'logo_path' => null,
        ]);
    }

    private function isEnrollmentCompleted(Enrollment $enrollment): bool
    {
        return \App\Models\CourseCompletion::where('enrollment_id', $enrollment->id)->exists()
            || $enrollment->status === 'completed';
    }

    private function generateCertificateNo(): string
    {
        return 'LMS-' . date('Y') . '-' . strtoupper(Str::random(8));
    }

    private function pdfExists(string $pdfPath): bool
    {
        // pdfPath stored as /storage/...
        $relative = ltrim(str_replace('/storage/', '', $pdfPath), '/');
        return Storage::disk('public')->exists($relative);
    }
}
