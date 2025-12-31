import React, { useEffect, useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/Card';
import Toast from '@/Components/Toast';
import EmptyState from '@/Components/EmptyState';
import ProgressBar from '@/Components/ProgressBar';
import { apiGet, apiPost, apiDelete } from '@/lib/apiClient';

// API helpers are centralized in @/lib/apiClient (handles 401 + HTML responses safely)

export default function CoursesIndex() {
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
  const [loading, setLoading] = useState(true);
  const [creating, setCreating] = useState(false);

  // Enroll learners UI
  const [enrollOpen, setEnrollOpen] = useState(false);
  const [enrollCourse, setEnrollCourse] = useState(null);
  const [learnerSearch, setLearnerSearch] = useState('');
  const [learnerRows, setLearnerRows] = useState([]);
  const [selectedLearners, setSelectedLearners] = useState(() => new Set());
  const [dueDate, setDueDate] = useState('');
  const [enrolling, setEnrolling] = useState(false);

  const [form, setForm] = useState({
    code: '',
    title: 'Safety Induction',
    status: 'draft',
    default_language: 'en',
  });

  async function load() {
    setErr('');
    setLoading(true);
    try {
      const data = await apiGet('/api/admin/courses');
      setRows(data?.data || []);
      setMeta({
        current_page: data?.current_page,
        last_page: data?.last_page,
        total: data?.total,
      });
    } catch (e) {
      setErr(e.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, []);

  async function createCourse() {
    setErr('');
    setCreating(true);
    try {
      const payload = {
        code: form.code.trim(),
        title: form.title.trim(),
        description: '',
        status: form.status,
        default_language: form.default_language,
        available_languages_json: ['en','ms','ta','zh'],
        completion_rules_json: {
          must_complete_all_lessons: true,
          sequential_lock: true,
          minimum_watch_percent: 90,
          must_view_all_slides: true,
        },
        certificate_enabled: true,
        certificate_validity_json: { type: 'days', value: 365 },
        template_id: 1,
      };
      const res = await apiPost('/api/admin/courses', payload);
      await load();
      if (res?.course?.id) {
        window.location.href = `/admin/courses/${res.course.id}/builder`;
      }
    } catch (e) {
      setErr(e.message);
    } finally {
      setCreating(false);
    }
  }

  async function deleteCourse(course) {
    if (!confirm(`Delete course "${course?.translations?.[0]?.title || course.code}"?`)) return;
    setErr('');
    try {
      await apiDelete(`/api/admin/courses/${course.id}`);
      await load();
    } catch (e) {
      setErr(e.message);
    }
  }

  async function loadLearners(search = '') {
    const q = new URLSearchParams();
    q.set('role', 'learner');
    if (search) q.set('search', search);
    const data = await apiGet(`/api/admin/users?${q.toString()}`);
    setLearnerRows(data?.data || []);
  }

  function openEnrollModal(course) {
    setEnrollCourse(course);
    setEnrollOpen(true);
    setLearnerSearch('');
    setSelectedLearners(new Set());
    setDueDate('');
    loadLearners('');
  }

  async function submitEnroll() {
    if (!enrollCourse) return;
    const userIds = Array.from(selectedLearners);
    if (userIds.length === 0) {
      setErr('Please select at least one learner.');
      return;
    }
    setErr('');
    setEnrolling(true);
    try {
      await apiPost(`/api/admin/courses/${enrollCourse.id}/enrollments`, {
        user_ids: userIds,
        due_date: dueDate || null,
      });
      setEnrollOpen(false);
      setToast({msg:`Enrolled ${userIds.length} learner(s) to ${enrollCourse.title || enrollCourse.code}.`, type:'success'});
      // optional: refresh enrollment list page manually from sidebar

    } catch (e) {
      setErr(e.message);
    } finally {
      setEnrolling(false);
    }
  }

  return (
    <AppLayout
      title="Courses"
      nav={nav}
      right={<button onClick={load} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Refresh</button>}
    >
      {err ? <div className="text-red-600">{err}</div> : null}

      <div className="grid grid-cols-1 gap-4 md:grid-cols-12">
        <div className="md:col-span-4">
          <Card>
            <div className="text-sm font-extrabold text-slate-900">Create Course</div>
            <div className="mt-1 text-xs text-slate-500">Creates a new course with best-practice defaults (sequential lock on, watch 90%, must view all PDF pages).</div>

            <div className="mt-4 space-y-3">
              <div>
                <div className="text-xs font-semibold text-slate-600">Course Code</div>
                <input value={form.code} onChange={(e)=>setForm({...form, code:e.target.value})} placeholder="SAFETY-INDUCTION" className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
              </div>
              <div>
                <div className="text-xs font-semibold text-slate-600">Title</div>
                <input value={form.title} onChange={(e)=>setForm({...form, title:e.target.value})} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <div className="text-xs font-semibold text-slate-600">Status</div>
                  <select value={form.status} onChange={(e)=>setForm({...form, status:e.target.value})} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                  </select>
                </div>
                <div>
                  <div className="text-xs font-semibold text-slate-600">Default Language</div>
                  <select value={form.default_language} onChange={(e)=>setForm({...form, default_language:e.target.value})} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="en">EN</option>
                    <option value="ms">MS</option>
                    <option value="ta">TA</option>
                    <option value="zh">ZH</option>
                  </select>
                </div>
              </div>

              <button
                onClick={createCourse}
                disabled={!form.code.trim() || creating}
                className="w-full rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
              >
                {creating ? 'Creating…' : 'Create & Open Builder'}
              </button>
            </div>
          </Card>
        </div>

        <div className="md:col-span-8">
          <Card>
            <div className="flex items-start justify-between">
              <div>
                <div className="text-sm font-extrabold text-slate-900">Course Catalog</div>
                <div className="mt-1 text-xs text-slate-500">Manage courses and open the builder.</div>
              </div>
              <div className="text-xs text-slate-500">{meta?.total ? `Total: ${meta.total}` : ''}</div>
            </div>

            {loading ? <div className="mt-4 text-slate-600">Loading…</div> : null}

            <div className="mt-4 grid grid-cols-1 gap-3">
              {rows.map((c) => {
                const title = c.translations?.[0]?.title || c.code;
                const status = c.status;
                const badge = status === 'published' ? 'bg-emerald-100 text-emerald-800' : status === 'draft' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700';
                return (
                  <div key={c.id} className="rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                    <div className="flex items-start justify-between gap-4">
                      <div>
                        <div className="text-base font-extrabold text-slate-900">{title}</div>
                        <div className="mt-1 text-xs text-slate-500">Code: <span className="font-semibold text-slate-700">{c.code}</span> • Default: {c.default_language?.toUpperCase()}</div>
                      </div>
                      <div className={`rounded-xl px-3 py-1 text-xs font-extrabold ${badge}`}>{status.toUpperCase()}</div>
                    </div>
                    <div className="mt-3 flex items-center justify-between">
                      <div className="w-48"><ProgressBar value={0} /></div>
                      <div className="flex items-center gap-2">
                        <button onClick={() => openEnrollModal(c)} className="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Enroll</button>
                        <Link href={`/admin/courses/${c.id}/builder`} className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Open Builder</Link>
                        <button onClick={() => deleteCourse(c)} className="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
  Delete
</button>

                      </div>
                    </div>
                  </div>
                );
              })}
              {!loading && rows.length === 0 ? <div className="text-slate-600">No courses yet.</div> : null}
            </div>
          </Card>
        </div>
      </div>

      {/* Enroll Learners Modal */}
      {enrollOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
          <div className="w-full max-w-3xl rounded-2xl bg-white p-5 shadow-xl">
            <div className="flex items-start justify-between gap-4">
              <div>
                <div className="text-base font-extrabold text-slate-900">Enroll Learners</div>
                <div className="mt-1 text-xs text-slate-500">Course: <span className="font-semibold text-slate-700">{enrollCourse?.translations?.[0]?.title || enrollCourse?.code}</span></div>
              </div>
              <button onClick={() => setEnrollOpen(false)} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold">Close</button>
            </div>

            <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-12">
              <div className="md:col-span-8">
                <div className="flex items-center justify-between gap-3">
                  <input
                    value={learnerSearch}
                    onChange={(e) => {
                      const v = e.target.value;
                      setLearnerSearch(v);
                      loadLearners(v);
                    }}
                    placeholder="Search learners by name/email/phone"
                    className="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                  />
                </div>

<div className="mt-3 flex items-center justify-between">
  <div className="text-xs text-slate-500">Selected: {selectedLearners.size}</div>
  <div className="flex gap-2">
    <button type="button" onClick={()=>{
      const s = new Set(selectedLearners);
      learnerRows.forEach(u=>s.add(u.id));
      setSelectedLearners(s);
    }} className="rounded-lg border border-slate-200 px-3 py-1 text-xs font-semibold hover:bg-slate-50">
      Select all visible
    </button>
    <button type="button" onClick={()=>{
      const s = new Set(selectedLearners);
      learnerRows.forEach(u=>s.delete(u.id));
      setSelectedLearners(s);
    }} className="rounded-lg border border-slate-200 px-3 py-1 text-xs font-semibold hover:bg-slate-50">
      Clear visible
    </button>
  </div>
</div>
                <div className="mt-3 max-h-80 overflow-auto rounded-2xl border border-slate-200">
                  {learnerRows.map((u) => {
                    const checked = selectedLearners.has(u.id);
                    return (
                      <label key={u.id} className="flex cursor-pointer items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 hover:bg-slate-50">
                        <div>
                          <div className="text-sm font-bold text-slate-900">{u.name}</div>
                          <div className="mt-0.5 text-xs text-slate-500">{u.email || ''}{u.phone ? ` • ${u.phone}` : ''}</div>
                        </div>
                        <input
                          type="checkbox"
                          checked={checked}
                          onChange={(e) => {
                            setSelectedLearners((prev) => {
                              const n = new Set(prev);
                              if (e.target.checked) n.add(u.id);
                              else n.delete(u.id);
                              return n;
                            });
                          }}
                          className="h-5 w-5"
                        />
                      </label>
                    );
                  })}
                  {learnerRows.length === 0 ? <div className="px-4 py-6 text-sm text-slate-600">No learners found.</div> : null}
                </div>
              </div>

              <div className="md:col-span-4">
                <div className="rounded-2xl border border-slate-200 p-4">
                  <div className="text-xs font-semibold text-slate-600">Due date (optional)</div>
                  <input type="date" value={dueDate} onChange={(e)=>setDueDate(e.target.value)} className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />

                  <div className="mt-4 text-xs text-slate-500">Selected: <span className="font-semibold text-slate-700">{selectedLearners.size}</span></div>

                  <button
                    onClick={submitEnroll}
                    disabled={enrolling || selectedLearners.size === 0}
                    className="mt-3 w-full rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                  >
                    {enrolling ? 'Enrolling…' : 'Enroll Selected Learners'}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      ) : null}
      <Toast message={toast.msg} type={toast.type} onClose={()=>setToast({msg:'',type:'info'})} />
</AppLayout>
  );
}
