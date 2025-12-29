import React, { useEffect, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/Card';
import { apiFetch } from '@/lib/apiClient';

export default function Completions() {
  const [rows, setRows] = useState([]);
  const [err, setErr] = useState('');
  const [filters, setFilters] = useState({ status: '', from: '', to: '' });

  async function load() {
    setErr('');
    try {
      const qs = new URLSearchParams(filters);
      const res = await apiFetch(`/api/reports/completions?${qs.toString()}`);
      setRows(res?.data || []);
    } catch (e) {
      setErr(e?.message || 'Failed to load report');
    }
  }

  useEffect(() => { load(); }, []);

  const nav = [
    { href: '/admin/dashboard', label: 'Dashboard' },
    { href: '/admin/users', label: 'Users' },
    { href: '/admin/courses', label: 'Courses' },
    { href: '/admin/enrollments', label: 'Enrollments' },
    { href: '/admin/reports/completions', label: 'Reports: Completions' },
    { href: '/admin/reports/assessments', label: 'Reports: Assessments' },
    { href: '/admin/settings', label: 'Settings' },
  ];

  const exportUrl = `/api/reports/completions/export?${new URLSearchParams(filters).toString()}`;

  return (
    <AppLayout
      title="Completion Report"
      nav={nav}
      right={
        <div className="flex gap-2">
          <a href={exportUrl} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold hover:bg-slate-50">
            Export CSV
          </a>
          <button onClick={load} className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Refresh
          </button>
        </div>
      }
    >
      {err ? <div className="text-red-600">{err}</div> : null}

      <Card>
        <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
          <select
            className="rounded-xl border border-slate-200 px-3 py-2 text-sm"
            value={filters.status}
            onChange={(e) => setFilters((f) => ({ ...f, status: e.target.value }))}
          >
            <option value="">All statuses</option>
            <option value="assigned">Assigned</option>
            <option value="in_progress">In progress</option>
            <option value="completed">Completed</option>
          </select>
          <input
            type="date"
            className="rounded-xl border border-slate-200 px-3 py-2 text-sm"
            value={filters.from}
            onChange={(e) => setFilters((f) => ({ ...f, from: e.target.value }))}
          />
          <input
            type="date"
            className="rounded-xl border border-slate-200 px-3 py-2 text-sm"
            value={filters.to}
            onChange={(e) => setFilters((f) => ({ ...f, to: e.target.value }))}
          />
        </div>
      </Card>

      <div className="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-left text-slate-600">
            <tr>
              <th className="px-4 py-3">Learner</th>
              <th className="px-4 py-3">Course</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Assigned</th>
              <th className="px-4 py-3">Due</th>
              <th className="px-4 py-3">Completed</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.enrollment_id} className="border-t border-slate-100">
                <td className="px-4 py-3">
                  <div className="font-semibold text-slate-900">{r.learner_name}</div>
                  <div className="text-xs text-slate-500">{r.email} {r.phone ? `• ${r.phone}` : ''}</div>
                </td>
                <td className="px-4 py-3">{r.course_code}</td>
                <td className="px-4 py-3">
                  <span className="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-slate-800">{r.status}</span>
                </td>
                <td className="px-4 py-3">{r.assigned_at || '-'}</td>
                <td className="px-4 py-3">{r.due_date || '-'}</td>
                <td className="px-4 py-3">{r.completed_at || '-'}</td>
              </tr>
            ))}
          </tbody>
        </table>

        {rows.length === 0 ? <div className="px-4 py-6 text-slate-600">No records found.</div> : null}
      </div>
    </AppLayout>
  );
}
