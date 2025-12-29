import React, { useMemo } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/Card';
import Button from '@/Components/Button';

export default function Dashboard() {
  const nav = useMemo(() => ([
    { href: '/admin/dashboard', label: 'Dashboard' },
    { href: '/admin/users', label: 'Users' },
    { href: '/admin/courses', label: 'Courses' },
    { href: '/admin/enrollments', label: 'Enrollments' },
    { href: '/admin/certificates', label: 'Certificates' },
    { href: '/admin/reports/completions', label: 'Reports' },
    { href: '/admin/settings', label: 'Settings' },
  ]), []);

  return (
    <AppLayout title="Admin Dashboard" nav={nav}>
      <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
        <Card>
          <div className="text-sm font-extrabold text-slate-900">Users</div>
          <div className="mt-1 text-xs text-slate-500">Create Admin/Trainer/Learner users and manage access.</div>
          <Link href="/admin/users" className="mt-4 inline-flex"><Button>Open Users</Button></Link>
        </Card>
        <Card>
          <div className="text-sm font-extrabold text-slate-900">Courses</div>
          <div className="mt-1 text-xs text-slate-500">Create and manage course content and assets.</div>
          <Link href="/admin/courses" className="mt-4 inline-flex"><Button>Open Courses</Button></Link>
        </Card>
        <Card>
          <div className="text-sm font-extrabold text-slate-900">Enrollments</div>
          <div className="mt-1 text-xs text-slate-500">Assign learners and manage due dates/status.</div>
          <Link href="/admin/enrollments" className="mt-4 inline-flex"><Button>Open Enrollments</Button></Link>
        </Card>
        <Card>
          <div className="text-sm font-extrabold text-slate-900">Settings</div>
          <div className="mt-1 text-xs text-slate-500">Upload rules, branding, and portal configuration.</div>
          <Link href="/admin/settings" className="mt-4 inline-flex"><Button>Open Settings</Button></Link>
        </Card>
      </div>

      <div className="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        <Card>
          <div className="text-base font-extrabold text-slate-900">Production-grade behavior included</div>
          <ul className="mt-3 list-disc space-y-2 pl-5 text-sm text-slate-700">
            <li>Sanctum API auth using email or phone + password.</li>
            <li>Sequential lock enforced server-side (cannot skip lessons).</li>
            <li>Asset upload endpoint with config-driven limits and MIME validation.</li>
          </ul>
        </Card>
        <Card>
          <div className="text-base font-extrabold text-slate-900">Next modules (already planned)</div>
          <ul className="mt-3 list-disc space-y-2 pl-5 text-sm text-slate-700">
            <li>Assessments builder with timer (soft warning) and critical questions.</li>
            <li>Certificate templates pack with QR verification.</li>
            <li>Docker deployment pack on request (later phase).</li>
          </ul>
        </Card>
      </div>
    </AppLayout>
  );
}
