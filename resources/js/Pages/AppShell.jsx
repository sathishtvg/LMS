import React from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function AppShell() {
  const user = usePage().props?.auth?.user;
  const apiToken = usePage().props?.auth?.api_token;
  if (apiToken && typeof window !== 'undefined') {
    try { localStorage.setItem('token', apiToken); } catch {}
  }
  return (
    <div className="min-h-screen bg-slate-50">
      <header className="bg-white border-b border-slate-200">
        <div className="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
          <div className="font-semibold">LMS</div>
          <form method="post" action="/logout">
            <input type="hidden" name="_token" value={document.querySelector('meta[name=csrf-token]')?.content || ''} />
            <button className="text-sm text-slate-600 hover:text-slate-900">Logout</button>
          </form>
        </div>
      </header>

      <main className="max-w-6xl mx-auto p-4">
        <div className="rounded-2xl bg-white shadow p-6">
          <h1 className="text-xl font-semibold">Welcome</h1>
          <p className="text-slate-600 mt-1">Use the pages below. Admin pages require Admin role; Learner pages require Learner role.</p>

          <div className="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <a className="rounded-xl border border-slate-200 p-4 hover:bg-slate-50" href="/admin/dashboard">Admin Dashboard</a>
            <a className="rounded-xl border border-slate-200 p-4 hover:bg-slate-50" href="/admin/settings">Admin Settings</a>
            <a className="rounded-xl border border-slate-200 p-4 hover:bg-slate-50" href="/learner/dashboard">Learner Dashboard</a>
            <a className="rounded-xl border border-slate-200 p-4 hover:bg-slate-50" href="/learner/my-courses">Learner My Courses</a>
            <a className="rounded-xl border border-slate-200 p-4 hover:bg-slate-50" href="/api/me">API: /api/me</a>
          </div>
        </div>
      </main>
    </div>
  );
}
