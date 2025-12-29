import React, { useEffect, useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/Card';
import EmptyState from '@/Components/EmptyState';
import Toast from '@/Components/Toast';

import { apiGet, apiPut } from '@/lib/apiClient';

function courseTitle(en) {
  return en.course?.translations?.[0]?.title || en.course?.code || `Course ${en.course_id}`;
}

export default function EnrollmentsIndex() {
  const nav = useMemo(() => ([
    { href: '/admin/dashboard', label: 'Dashboard' },
    { href: '/admin/users', label: 'Users' },
    { href: '/admin/courses', label: 'Courses' },
    { href: '/admin/enrollments', label: 'Enrollments' },
    { href: '/admin/settings', label: 'Settings' },
  ]), []);

  const [rows, setRows] = useState([]);
  const [meta, setMeta] = useState(null);
  const [err, setErr] = useState('');
  const [toast, setToast] = useState({msg:'', type:'info'});
  const [filters, setFilters] = useState({ status:'', search:'' });
  const [loading, setLoading] = useState(true);

  async function load() {
    setErr('');
    setLoading(true);
    try {
      const q = new URLSearchParams();
      if (filters.status) q.set('status', filters.status);
      if (filters.search) q.set('search', filters.search);
      const data = await apiGet(`/api/admin/enrollments?${q.toString()}`);
      setRows(data?.data || []);
      setMeta({ total: data?.total, current_page: data?.current_page, last_page: data?.last_page });
    } catch (e) {
      setErr(e.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, []);

  async function update(en, patch) {
    setErr('');
    try {
      await apiPut(`/api/admin/enrollments/${en.id}`, patch);
      await load();
    } catch (e) {
      setErr(e.message);
    }
  }

  return (
    <AppLayout
      title="Enrollments"
      nav={nav}
      right={<button onClick={load} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Refresh</button>}
    >
      {err ? <div className="text-red-600">{err}</div> : null}

      <Card>
        <div className="flex items-start justify-between">
          <div>
            <div className="text-sm font-extrabold text-slate-900">Enrollment List</div>
            <div className="mt-1 text-xs text-slate-500">Assigned learners by course. Update status and due dates.</div>
          </div>
          <div className="text-xs text-slate-500">{meta?.total ? `Total: ${meta.total}` : ''}</div>
        </div>

        <div className="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
          <select value={filters.status} onChange={(e)=>setFilters(f=>({...f,status:e.target.value}))} className="rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            <option value="assigned">Assigned</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="archived">Archived</option>
          </select>
          <input value={filters.search} onChange={(e)=>setFilters(f=>({...f,search:e.target.value}))} className="rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Search learner or course" />
          <button onClick={load} className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Apply</button>
        </div>

        {loading ? <div className="mt-4 text-slate-600">Loading…</div> : null}

        <div className="mt-4 overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="text-xs font-extrabold text-slate-600">
              <tr className="border-b border-slate-200">
                <th className="py-2">Learner</th>
                <th className="py-2">Course</th>
                <th className="py-2">Status</th>
                <th className="py-2">Due</th>
                <th className="py-2"></th>
              </tr>
            </thead>
            <tbody>
              {rows.map((en) => (
                <tr key={en.id} className="border-b border-slate-100">
                  <td className="py-3">
                    <div className="font-semibold text-slate-900">{en.user?.name || `User ${en.user_id}`}</div>
                    <div className="text-xs text-slate-500">{en.user?.email || ''}{en.user?.phone ? ` • ${en.user.phone}` : ''}</div>
                  </td>
                  <td className="py-3">
                    <div className="font-semibold text-slate-900">{courseTitle(en)}</div>
                    <div className="text-xs text-slate-500">ID: {en.course_id}</div>
                  </td>
                  <td className="py-3">
                    <select
                      value={en.status}
                      onChange={(e)=>update(en, { status: e.target.value })}
                      className="rounded-xl border border-slate-200 px-3 py-2 text-sm"
                    >
                      <option value="assigned">Assigned</option>
                      <option value="in_progress">In Progress</option>
                      <option value="completed">Completed</option>
                      <option value="overdue">Overdue</option>
                      <option value="revoked">Revoked</option>
                    </select>
                  </td>
                  <td className="py-3">
                    <input
                      type="date"
                      value={en.due_date || ''}
                      onChange={(e)=>update(en, { due_date: e.target.value || null })}
                      className="rounded-xl border border-slate-200 px-3 py-2 text-sm"
                    />
                  </td>
                  <td className="py-3 text-right">
                    <div className="text-xs text-slate-500">Enrollment #{en.id}</div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {!loading && rows.length === 0 ? <div className="mt-4 text-slate-600">No enrollments yet.</div> : null}
        </div>
      </Card>
    <Toast message={toast.msg} type={toast.type} onClose={()=>setToast({msg:'',type:'info'})} />
</AppLayout>
  );
}