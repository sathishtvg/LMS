import React, { useMemo } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/Card';

export default function Dashboard() {
  const nav = useMemo(() => ([
    { href: '/learner/my-courses', label: 'My Courses' },
    { href: '/learner/dashboard', label: 'Dashboard' },
  ]), []);

  return (
    <AppLayout title="Learner Dashboard" nav={nav}>
      <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        <Card>
          <div className="text-sm font-extrabold text-slate-900">My Courses</div>
          <div className="mt-1 text-xs text-slate-500">Assigned courses only. Progress is tracked and locked sequentially.</div>
          <Link href="/learner/my-courses" className="mt-4 inline-block rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Open My Courses</Link>
        </Card>
        <Card>
          <div className="text-sm font-extrabold text-slate-900">Progress Rules</div>
          <ul className="mt-3 list-disc space-y-2 pl-5 text-sm text-slate-700">
            <li>Video: must watch at least configured percentage (default 90%).</li>
            <li>PDF/PPT: must view all pages when enabled.</li>
            <li>Cannot skip lessons: enforced by API (HTTP 423 if locked).</li>
          </ul>
        </Card>
      </div>
    </AppLayout>
  );
}
