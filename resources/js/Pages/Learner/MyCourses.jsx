import React, { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/Card';
import ProgressBar from '@/Components/ProgressBar';
import { apiGet } from '@/lib/apiClient';

export default function MyCourses() {
  const [rows, setRows] = useState([]);
  const [err, setErr] = useState('');
  const [loading, setLoading] = useState(true);

  const nav = [
    { href: '/learner/my-courses', label: 'My Courses' },
    { href: '/learner/dashboard', label: 'Dashboard' },
  ];

  async function load() {
    setErr('');
    setLoading(true);
    try {
      const data = await apiGet('/api/my-courses');
      if (data) setRows(Array.isArray(data) ? data : (data.data || []));
    } catch (e) {
      setErr(e.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, []);

  return (
    <AppLayout
      title="My Courses"
      nav={nav}
      right={
        <button onClick={load} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
          Refresh
        </button>
      }
    >
      {loading ? <div className="text-slate-600">Loading...</div> : null}
      {err ? <div className="text-red-600">{err}</div> : null}

      <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        {rows.map((enr) => {
          const title = enr.course_title || enr.course?.translations?.[0]?.title || enr.course?.code || `Course ${enr.course_id}`;
          const percent = enr.progress_percent ?? 0;

          return (
            <Card key={enr.id}>
              <div className="flex items-start justify-between gap-4">
                <div>
                  <div className="text-lg font-extrabold text-slate-900">{title}</div>
                  <div className="mt-1 text-sm text-slate-500">
                    Status: <span className="font-semibold text-slate-700">{enr.status}</span>
                    {enr.due_date ? <> • Due: <span className="font-semibold">{enr.due_date}</span></> : null}
                  </div>
                </div>
                <div className="rounded-xl bg-slate-100 px-3 py-1 text-sm font-extrabold text-slate-900">
                  {Math.round(percent)}%
                </div>
              </div>

              <div className="mt-4">
                <ProgressBar value={percent} />
              </div>

              <div className="mt-4 flex items-center justify-between">
                <div className="text-xs text-slate-500">Continue where you left off.</div>
                <Link
                  href={`/learner/player/${enr.id}`}
                  className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                >
                  Open
                </Link>
              </div>
            </Card>
          );
        })}
      </div>

      {!loading && rows.length === 0 ? (
        <div className="mt-6 text-slate-600">No assigned courses yet.</div>
      ) : null}
    </AppLayout>
  );
}
