import React, { useEffect, useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/Card';
import { apiGet, apiPost, apiPut } from '@/lib/apiClient';

function Badge({ children }) {
  return (
    <span className="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
      {children}
    </span>
  );
}

export default function UsersIndex() {
  const nav = useMemo(() => ([
    { href: '/admin/dashboard', label: 'Dashboard' },
    { href: '/admin/users', label: 'Users' },
    { href: '/admin/courses', label: 'Courses' },
    { href: '/admin/enrollments', label: 'Enrollments' },
    { href: '/admin/settings', label: 'Settings' },
  ]), []);

  const [rows, setRows] = useState([]);
  const [selectedIds, setSelectedIds] = useState([]);
  const [enrollOpen, setEnrollOpen] = useState(false);
  const [courses, setCourses] = useState([]);
  const [enrollCourseId, setEnrollCourseId] = useState('');
  const [enrollDueDate, setEnrollDueDate] = useState('');
  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState('');
  const [msg, setMsg] = useState('');

  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState({
    name: '',
    email: '',
    phone: '',
    role: 'learner',
    language: 'en',
    status: 'active',
    password: 'Password123!',
  });

  async function load() {
    setErr('');
    setMsg('');
    setLoading(true);
    try {
      const data = await apiGet('/api/admin/users');
      setRows(data?.data || []);
    } catch (e) {
      setErr(e.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, []);

  function openCreate() {
    setEditing(null);
    setForm({
      name: '', email: '', phone: '', role: 'learner', language: 'en', status: 'active', password: 'Password123!',
    });
    setOpen(true);
  }

  function toggleSelect(id, checked) {
    setSelectedIds(prev => {
      const set = new Set(prev);
      if (checked) set.add(id); else set.delete(id);
      return Array.from(set);
    });
  }

  async function openEnroll() {
    setErr(''); setMsg('');
    try {
      const cs = await apiGet('/api/admin/courses');
      const list = cs?.data || cs || [];
      setCourses(list);
      setEnrollCourseId(list?.[0]?.id ? String(list[0].id) : '');
      setEnrollOpen(true);
    } catch (e) {
      setErr(e.message);
    }
  }

  async function doEnroll() {
    setErr(''); setMsg('');
    try {
      if (!enrollCourseId) throw new Error('Select a course');
      if (!selectedIds.length) throw new Error('Select at least one learner');
      await apiPost(`/api/admin/courses/${enrollCourseId}/enrollments`, {
        user_ids: selectedIds,
        due_date: enrollDueDate || null,
      });
      setMsg(`Enrolled ${selectedIds.length} learner(s).`);
      setEnrollOpen(false);
      setSelectedIds([]);
    } catch (e) {
      setErr(e.message);
    }
  }

  function openEdit(u) {
    setEditing(u);
    setForm({
      name: u.name || '',
      email: u.email || '',
      phone: u.phone || '',
      role: u.role || 'learner',
      language: u.language || 'en',
      status: u.status || 'active',
      password: 'Password123!',
    });
    setOpen(true);
  }

  async function save() {
    setErr('');
    setMsg('');
    try {
      const payload = {
        name: form.name.trim(),
        email: form.email.trim() || null,
        phone: form.phone.trim() || null,
        role: form.role,
        language: form.language,
        status: form.status,
        ...(editing ? {} : { password: form.password }),
      };
      if (!payload.name) throw new Error('Name is required');
      if (!payload.email && !payload.phone) throw new Error('Email or phone is required');

      if (editing) {
        await apiPut(`/api/admin/users/${editing.id}`, payload);
        setMsg('User updated.');
      } else {
        await apiPost('/api/admin/users', payload);
        setMsg('User created.');
      }
      setOpen(false);
      await load();
    } catch (e) {
      setErr(e.message);
    }
  }

  async function resetPassword(u) {
    if (!confirm(`Reset password for ${u.name}?`)) return;
    setErr('');
    setMsg('');
    try {
      const res = await apiPost(`/api/admin/users/${u.id}/reset-password`, { password: 'Password123!' });
      setMsg(res?.message || 'Password reset to Password123!');
    } catch (e) {
      setErr(e.message);
    }
  }

  return (
    <AppLayout
      title="Users"
      nav={nav}
      right={
        <div className="flex gap-2">
          <button onClick={load} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Refresh</button>
          {selectedIds.length ? (
            <button onClick={openEnroll} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold hover:bg-slate-50">
              Enroll ({selectedIds.length})
            </button>
          ) : null}
          <button onClick={openCreate} className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Create User</button>
        </div>
      }
    >
      {err ? <div className="mb-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{err}</div> : null}
      {msg ? <div className="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{msg}</div> : null}

      <Card>
        <div className="flex items-start justify-between">
          <div>
            <div className="text-sm font-extrabold text-slate-900">User Management</div>
            <div className="mt-1 text-xs text-slate-500">Admin-controlled users. Login supports email or phone + password.</div>
          </div>
          <div className="text-xs text-slate-500">Total: {rows.length}</div>
        </div>

        {loading ? <div className="mt-4 text-slate-600">Loading…</div> : null}

        <div className="mt-4 overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="text-xs text-slate-500">
              <tr>
                <th className="py-2 w-10"><input type="checkbox" onChange={(e)=>{ const c=e.target.checked; setSelectedIds(c? rows.filter(x=>x.role==='learner').map(x=>x.id): []); }} /></th>
                <th className="py-2">Name</th>
                <th className="py-2">Email</th>
                <th className="py-2">Phone</th>
                <th className="py-2">Role</th>
                <th className="py-2">Status</th>
                <th className="py-2"></th>
              </tr>
            </thead>
            <tbody>
              {rows.map((u) => (
                <tr key={u.id} className="border-t border-slate-100">
                  <td className="py-3"><input type="checkbox" checked={selectedIds.includes(u.id)} disabled={u.role!=='learner'} onChange={(e)=>toggleSelect(u.id,e.target.checked)} /></td>
                  <td className="py-3 font-semibold text-slate-900">{u.name}</td>
                  <td className="py-3 text-slate-700">{u.email || '-'}</td>
                  <td className="py-3 text-slate-700">{u.phone || '-'}</td>
                  <td className="py-3"><Badge>{(u.role || '').toUpperCase()}</Badge></td>
                  <td className="py-3">{u.status === 'active' ? <Badge>ACTIVE</Badge> : <Badge>INACTIVE</Badge>}</td>
                  <td className="py-3 text-right">
                    <div className="flex justify-end gap-2">
                      <button onClick={() => openEdit(u)} className="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</button>
                      <button onClick={() => resetPassword(u)} className="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Reset PW</button>
                    </div>
                  </td>
                </tr>
              ))}

              {!loading && rows.length === 0 ? (
                <tr>
                  <td colSpan={6} className="py-8 text-center text-slate-500">No users found.</td>
                </tr>
              ) : null}
            </tbody>
          </table>
        </div>
      </Card>

      {open ? (
        <div className="fixed inset-0 z-50 grid place-items-center bg-black/30 p-4">
          <div className="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
            <div className="flex items-start justify-between">
              <div>
                <div className="text-base font-extrabold text-slate-900">{editing ? 'Edit User' : 'Create User'}</div>
                <div className="mt-1 text-xs text-slate-500">Default password: <span className="font-semibold">Password123!</span></div>
              </div>
              <button onClick={() => setOpen(false)} className="rounded-lg border border-slate-200 px-2 py-1 text-sm text-slate-700 hover:bg-slate-50">Close</button>
            </div>

            <div className="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
              <div className="md:col-span-2">
                <div className="text-xs font-semibold text-slate-600">Name</div>
                <input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
              </div>

              <div>
                <div className="text-xs font-semibold text-slate-600">Email (optional)</div>
                <input value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
              </div>
              <div>
                <div className="text-xs font-semibold text-slate-600">Phone (optional)</div>
                <input value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
              </div>

              <div>
                <div className="text-xs font-semibold text-slate-600">Role</div>
                <select value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                  <option value="admin">Admin</option>
                  <option value="trainer">Trainer</option>
                  <option value="learner">Learner</option>
                </select>
              </div>
              <div>
                <div className="text-xs font-semibold text-slate-600">Status</div>
                <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>

              <div>
                <div className="text-xs font-semibold text-slate-600">Language</div>
                <select value={form.language} onChange={(e) => setForm({ ...form, language: e.target.value })} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                  <option value="en">EN</option>
                  <option value="ms">MS</option>
                  <option value="ta">TA</option>
                  <option value="zh">ZH</option>
                </select>
              </div>

              {!editing ? (
                <div>
                  <div className="text-xs font-semibold text-slate-600">Password</div>
                  <input value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                </div>
              ) : (
                <div className="text-xs text-slate-500 md:col-span-1">Password changes via Reset PW.</div>
              )}
            </div>

            <div className="mt-6 flex items-center justify-end gap-2">
              <button onClick={() => setOpen(false)} className="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
              <button onClick={save} className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save</button>
            </div>
          </div>
        </div>
      ) : null}
          {enrollOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4">
          <div className="w-full max-w-lg rounded-2xl bg-white shadow-xl">
            <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
              <div className="text-sm font-extrabold text-slate-900">Enroll learners</div>
              <button onClick={() => setEnrollOpen(false)} className="rounded-lg px-2 py-1 text-sm hover:bg-slate-50">Close</button>
            </div>
            <div className="px-4 py-4">
              <div className="text-xs font-semibold text-slate-600">Course</div>
              <select value={enrollCourseId} onChange={(e)=>setEnrollCourseId(e.target.value)} className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                {courses.map(c => <option key={c.id} value={String(c.id)}>{c.title || c.code}</option>)}
              </select>

              <div className="mt-4 text-xs font-semibold text-slate-600">Due date (optional)</div>
              <input type="date" value={enrollDueDate} onChange={(e)=>setEnrollDueDate(e.target.value)} className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />

              <div className="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                Selected learners: <span className="font-semibold">{selectedIds.length}</span>
              </div>

              <div className="mt-4 flex justify-end gap-2">
                <button onClick={() => setEnrollOpen(false)} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold hover:bg-slate-50">Cancel</button>
                <button onClick={doEnroll} className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Enroll</button>
              </div>
            </div>
          </div>
        </div>
      ) : null}
    </AppLayout>
  );
}
