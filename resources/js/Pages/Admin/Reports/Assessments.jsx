import React, { useEffect, useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/Card';
import Button from '@/Components/Button';
import { apiFetch } from '@/lib/apiClient';

export default function AssessmentsReport() {
  const [data, setData] = useState({ summary: null, rows: [] });
  const [err, setErr] = useState('');
  const [loading, setLoading] = useState(true);

  const [filters, setFilters] = useState({
    course_id: '',
    assessment_id: '',
    passed: '',
    from: '',
    to: '',
    search: '',
  });

  async function load() {
    setErr('');
    setLoading(true);
    try {
      const qs = new URLSearchParams(Object.entries(filters).filter(([,v]) => v !== '' && v !== null));
      const resp = await apiFetch(`/api/reports/assessments?${qs.toString()}`);
      setData(resp || { summary: null, rows: [] });
    } catch (e) {
      setErr(e.message || 'Failed to load');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, []);

  const exportUrl = useMemo(() => {
    const qs = new URLSearchParams(Object.entries(filters).filter(([,v]) => v !== '' && v !== null));
    return `/api/reports/assessments/export?${qs.toString()}`;
  }, [filters]);

  const nav = [
    { href: '/admin/dashboard', label: 'Dashboard' },
    { href: '/admin/users', label: 'Users' },
    { href: '/admin/courses', label: 'Courses' },
    { href: '/admin/enrollments', label: 'Enrollments' },
    { href: '/admin/certificates', label: 'Certificates' },
    { href: '/admin/assessments', label: 'Assessments' },
    { href: '/admin/reports/completions', label: 'Reports: Completions' },
    { href: '/admin/reports/assessments', label: 'Reports: Assessments' },
    { href: '/admin/settings', label: 'Settings' },
  ];

  const s = data?.summary;

  return (
    <AppLayout title="Assessment Reports" nav={nav} headerRight={
      <div className="flex gap-2">
        <a href={exportUrl} className="rounded-xl border border-gray-200 px-3 py-2 text-sm font-semibold hover:bg-gray-50">
          Export CSV
        </a>
        <Button onClick={load}>Refresh</Button>
      </div>
    }>
      {err ? <div className="text-red-600">{err}</div> : null}

      <Card>
        <div className="grid grid-cols-1 gap-3 md:grid-cols-5">
          <input
            className="rounded-xl border border-gray-200 px-3 py-2 text-sm"
            placeholder="Search learner / course / assessment"
            value={filters.search}
            onChange={(e) => setFilters(f => ({ ...f, search: e.target.value }))}
          />
          <select
            className="rounded-xl border border-gray-200 px-3 py-2 text-sm"
            value={filters.passed}
            onChange={(e) => setFilters(f => ({ ...f, passed: e.target.value }))}
          >
            <option value="">All results</option>
            <option value="1">Passed</option>
            <option value="0">Failed</option>
          </select>
          <input type="date" className="rounded-xl border border-gray-200 px-3 py-2 text-sm"
            value={filters.from}
            onChange={(e) => setFilters(f => ({ ...f, from: e.target.value }))}
          />
          <input type="date" className="rounded-xl border border-gray-200 px-3 py-2 text-sm"
            value={filters.to}
            onChange={(e) => setFilters(f => ({ ...f, to: e.target.value }))}
          />
          <Button variant="secondary" onClick={() => { setFilters({ course_id:'', assessment_id:'', passed:'', from:'', to:'', search:''}); }}>
            Clear
          </Button>
        </div>

        <div className="mt-4 grid grid-cols-2 gap-3 md:grid-cols-5">
          <div className="rounded-xl border border-gray-200 p-3">
            <div className="text-xs text-gray-500">Attempts</div>
            <div className="text-lg font-extrabold text-gray-900">{s?.attempts ?? '-'}</div>
          </div>
          <div className="rounded-xl border border-gray-200 p-3">
            <div className="text-xs text-gray-500">Passes</div>
            <div className="text-lg font-extrabold text-gray-900">{s?.passes ?? '-'}</div>
          </div>
          <div className="rounded-xl border border-gray-200 p-3">
            <div className="text-xs text-gray-500">Fails</div>
            <div className="text-lg font-extrabold text-gray-900">{s?.fails ?? '-'}</div>
          </div>
          <div className="rounded-xl border border-gray-200 p-3">
            <div className="text-xs text-gray-500">Pass Rate</div>
            <div className="text-lg font-extrabold text-gray-900">{s?.pass_rate != null ? `${s.pass_rate}%` : '-'}</div>
          </div>
          <div className="rounded-xl border border-gray-200 p-3">
            <div className="text-xs text-gray-500">Avg Score</div>
            <div className="text-lg font-extrabold text-gray-900">{s?.avg_score != null ? `${s.avg_score}%` : '-'}</div>
          </div>
        </div>
      </Card>

      <div className="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 text-left text-gray-600">
            <tr>
              <th className="px-4 py-3">Learner</th>
              <th className="px-4 py-3">Course</th>
              <th className="px-4 py-3">Assessment</th>
              <th className="px-4 py-3">Started</th>
              <th className="px-4 py-3">Submitted</th>
              <th className="px-4 py-3">Score</th>
              <th className="px-4 py-3">Result</th>
              <th className="px-4 py-3">Critical Wrong</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr><td className="px-4 py-6 text-gray-600" colSpan="8">Loading…</td></tr>
            ) : null}
            {!loading && (data?.rows?.length ?? 0) === 0 ? (
              <tr><td className="px-4 py-6 text-gray-600" colSpan="8">No attempts found.</td></tr>
            ) : null}
            {(data?.rows || []).map((r) => (
              <tr key={r.attempt_id} className="border-t border-gray-100">
                <td className="px-4 py-3">
                  <div className="font-semibold text-gray-900">{r.learner_name}</div>
                  <div className="text-xs text-gray-500">{r.email}{r.phone ? ` • ${r.phone}` : ''}</div>
                </td>
                <td className="px-4 py-3">{r.course}</td>
                <td className="px-4 py-3">{r.assessment}</td>
                <td className="px-4 py-3">{r.started_at || '-'}</td>
                <td className="px-4 py-3">{r.submitted_at || '-'}</td>
                <td className="px-4 py-3">{r.score_percent != null ? `${r.score_percent}%` : '-'}</td>
                <td className="px-4 py-3">
                  <span className={`rounded-lg px-2 py-1 text-xs font-bold ${r.passed ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                    {r.passed ? 'PASS' : 'FAIL'}
                  </span>
                </td>
                <td className="px-4 py-3">{r.critical_wrong_count ?? 0}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </AppLayout>
  );
}
