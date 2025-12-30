import React, { useEffect, useMemo, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/Card';

import { apiGet, apiPost, apiUpload } from '@/lib/apiClient';

function titleOf(obj, fallback) {
  return obj?.translations?.[0]?.title || fallback;
}

export default function CourseBuilder({ courseId }) {
  const nav = useMemo(() => ([
    { href: '/admin/dashboard', label: 'Dashboard' },
    { href: '/admin/courses', label: 'Courses' },
    { href: '/admin/enrollments', label: 'Enrollments' },
    { href: '/admin/settings', label: 'Settings' },
  ]), []);

  const [course, setCourse] = useState(null);
  const [err, setErr] = useState('');
  const [loading, setLoading] = useState(true);

  const [newModuleTitle, setNewModuleTitle] = useState('');
  const [lessonDraft, setLessonDraft] = useState({ moduleId: null, title: '', type: 'video', required: true, min_watch_percent: 90, must_view_all_slides: true });

  // Drag & drop state (no external libs)
  const [dragModuleId, setDragModuleId] = useState(null);
  const [dragLesson, setDragLesson] = useState(null); // { moduleId, lessonId }

  function buildReorderPayload(nextModuleOrderIds, nextLessonOrderByModule = {}) {
    // Backend expects: { modules: [{id, lessons:[{id}]}] }
    const currentModules = (course?.modules || []).slice();
    const byId = new Map(currentModules.map(m => [m.id, m]));

    const moduleIds = nextModuleOrderIds || currentModules
      .slice()
      .sort((a,b)=>a.sort_order-b.sort_order)
      .map(m=>m.id);

    return {
      modules: moduleIds.map((mid) => {
        const mod = byId.get(mid);
        const currentLessonIds = (mod?.lessons || [])
          .slice()
          .sort((a,b)=>a.sort_order-b.sort_order)
          .map(l=>l.id);
        const lessonIds = nextLessonOrderByModule[mid] || currentLessonIds;
        return {
          id: mid,
          lessons: (lessonIds || []).map(id => ({ id }))
        };
      })
    };
  }

  async function load() {
    setErr('');
    setLoading(true);
    try {
      const data = await apiGet(`/api/admin/courses/${courseId}/full`);
      setCourse(data.course);
    } catch (e) {
      setErr(e.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, [courseId]);

  async function addModule() {
    if (!course) return;
    const sort = (course.modules?.length || 0) + 1;
    await apiPost(`/api/admin/courses/${course.id}/modules`, {
      title: newModuleTitle || `Module ${sort}`,
      lang: course.default_language || 'en',
    });
    setNewModuleTitle('');
    await load();
  }

  async function addLesson() {
    if (!lessonDraft.moduleId) return;
    const mod = course.modules.find(m => m.id === lessonDraft.moduleId);
    const sort = (mod.lessons?.length || 0) + 1;
    await apiPost(`/api/admin/modules/${lessonDraft.moduleId}/lessons`, {
      type: lessonDraft.type,
      required: !!lessonDraft.required,
      min_watch_percent: lessonDraft.type === 'video' ? Number(lessonDraft.min_watch_percent || 90) : null,
      must_view_all_slides: (lessonDraft.type === 'pdf' || lessonDraft.type === 'ppt') ? !!lessonDraft.must_view_all_slides : true,
      title: lessonDraft.title || `Lesson ${sort}`,
      lang: course.default_language || 'en',
    });
    setLessonDraft({ ...lessonDraft, title: '' });
    await load();
  }

  async function moveModule(moduleId, dir) {
    const ids = [...course.modules].sort((a,b)=>a.sort_order-b.sort_order).map(m=>m.id);
    const idx = ids.indexOf(moduleId);
    const j = idx + dir;
    if (idx < 0 || j < 0 || j >= ids.length) return;
    [ids[idx], ids[j]] = [ids[j], ids[idx]];
    await apiPost(`/api/admin/courses/${course.id}/reorder`, buildReorderPayload(ids));
    await load();
  }

  async function reorderModulesByDrag(fromModuleId, toModuleId) {
    if (!course) return;
    if (!fromModuleId || !toModuleId || fromModuleId === toModuleId) return;
    const ids = [...course.modules].sort((a,b)=>a.sort_order-b.sort_order).map(m=>m.id);
    const from = ids.indexOf(fromModuleId);
    const to = ids.indexOf(toModuleId);
    if (from < 0 || to < 0) return;
    ids.splice(to, 0, ...ids.splice(from, 1));
    await apiPost(`/api/admin/courses/${course.id}/reorder`, buildReorderPayload(ids));
    await load();
  }

  async function reorderLessonsByDrag(moduleId, fromLessonId, toLessonId) {
    if (!course) return;
    if (!moduleId || !fromLessonId || !toLessonId || fromLessonId === toLessonId) return;
    const mod = course.modules.find(m=>m.id===moduleId);
    if (!mod) return;
    const ids = [...(mod.lessons||[])].sort((a,b)=>a.sort_order-b.sort_order).map(l=>l.id);
    const from = ids.indexOf(fromLessonId);
    const to = ids.indexOf(toLessonId);
    if (from < 0 || to < 0) return;
    ids.splice(to, 0, ...ids.splice(from, 1));
    await apiPost(`/api/admin/courses/${course.id}/reorder`, buildReorderPayload(null, { [moduleId]: ids }));
    await load();
  }

  async function moveLesson(moduleId, lessonId, dir) {
    const mod = course.modules.find(m=>m.id===moduleId);
    const ids = [...mod.lessons].sort((a,b)=>a.sort_order-b.sort_order).map(l=>l.id);
    const idx = ids.indexOf(lessonId);
    const j = idx + dir;
    if (idx < 0 || j < 0 || j >= ids.length) return;
    [ids[idx], ids[j]] = [ids[j], ids[idx]];
    await apiPost(`/api/admin/courses/${course.id}/reorder`, buildReorderPayload(null, { [moduleId]: ids }));
    await load();
  }

  async function uploadAsset(lessonId, assetType, file) {
    const fd = new FormData();
    fd.append('asset_type', assetType);
    fd.append('file', file);
    await apiUpload(`/api/admin/lessons/${lessonId}/assets/upload`, fd);
    await load();
  }

  if (loading) return <AppLayout title="Course Builder" nav={nav}><div className="text-slate-600">Loading…</div></AppLayout>;
  if (err) return <AppLayout title="Course Builder" nav={nav}><div className="text-red-600">{err}</div></AppLayout>;
  if (!course) return <AppLayout title="Course Builder" nav={nav}><div className="text-slate-600">Not found</div></AppLayout>;

  const courseTitle = titleOf(course, course.code);

  return (
    <AppLayout title={`Builder: ${courseTitle}`} nav={nav} right={<button onClick={load} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Refresh</button>}>
      <div className="grid grid-cols-1 gap-4 md:grid-cols-12">
        {/* left: modules/lessons */}
        <div className="md:col-span-8">
          <Card>
            <div className="flex items-start justify-between">
              <div>
                <div className="text-sm font-extrabold text-slate-900">Modules & Lessons</div>
                <div className="mt-1 text-xs text-slate-500">Use the arrows to order content. Learners cannot skip lessons (server enforced).</div>
              </div>
            </div>

            <div className="mt-4 space-y-5">
              {(course.modules || []).map((m) => (
                <div
                  key={m.id}
                  draggable
                  onDragStart={(e) => {
                    setDragModuleId(m.id);
                    e.dataTransfer.setData('text/plain', String(m.id));
                  }}
                  onDragOver={(e) => e.preventDefault()}
                  onDrop={(e) => {
                    e.preventDefault();
                    const from = dragModuleId || Number(e.dataTransfer.getData('text/plain'));
                    reorderModulesByDrag(from, m.id);
                    setDragModuleId(null);
                  }}
                  className={[
                    'rounded-2xl border border-slate-200 p-4',
                    dragModuleId === m.id ? 'ring-2 ring-slate-900' : '',
                  ].join(' ')}
                >
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <div className="text-sm font-extrabold text-slate-900">{titleOf(m, `Module ${m.sort_order}`)}</div>
                      <div className="text-xs text-slate-500">Sort: {m.sort_order}</div>
                    </div>
                    <div className="flex gap-2">
                      <button onClick={() => moveModule(m.id, -1)} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold">Up</button>
                      <button onClick={() => moveModule(m.id, 1)} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold">Down</button>
                      <button onClick={() => setLessonDraft({ ...lessonDraft, moduleId: m.id })} className="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Add Lesson</button>
                    </div>
                  </div>

                  <div className="mt-3 space-y-2">
                    {(m.lessons || []).map((l) => (
                      <div
                        key={l.id}
                        draggable
                        onDragStart={(e) => {
                          setDragLesson({ moduleId: m.id, lessonId: l.id });
                          e.dataTransfer.setData('text/plain', JSON.stringify({ moduleId: m.id, lessonId: l.id }));
                        }}
                        onDragOver={(e) => e.preventDefault()}
                        onDrop={(e) => {
                          e.preventDefault();
                          let from = dragLesson;
                          try { from = from || JSON.parse(e.dataTransfer.getData('text/plain')); } catch {}
                          if (!from || Number(from.moduleId) !== Number(m.id)) return;
                          reorderLessonsByDrag(m.id, Number(from.lessonId), l.id);
                          setDragLesson(null);
                        }}
                        className={[
                          'rounded-xl border border-slate-200 p-3',
                          dragLesson?.lessonId === l.id ? 'ring-2 ring-slate-900' : '',
                        ].join(' ')}
                      >
                        <div className="flex flex-wrap items-start justify-between gap-3">
                          <div>
                            <div className="text-sm font-bold text-slate-900">{titleOf(l, `Lesson ${l.sort_order}`)}</div>
                            <div className="mt-0.5 text-xs text-slate-500">Type: {String(l.type).toUpperCase()} • Required: {l.required ? 'Yes' : 'No'} • Sort: {l.sort_order}</div>
                          </div>
                          <div className="flex gap-2">
                            <button onClick={() => moveLesson(m.id, l.id, -1)} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold">Up</button>
                            <button onClick={() => moveLesson(m.id, l.id, 1)} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold">Down</button>
                          </div>
                        </div>

                        <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                          <div>
                            <div className="text-xs font-semibold text-slate-600">Attach Asset</div>
                            <div className="mt-2 flex items-center gap-2">
                              <select id={`type-${l.id}`} defaultValue={l.type === 'video' ? 'video' : (l.type === 'pdf' ? 'pdf' : (l.type === 'ppt' ? 'ppt' : 'other'))} className="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="video">Video</option>
                                <option value="pdf">PDF</option>
                                <option value="ppt">PPT</option>
                                <option value="image">Image</option>
                                <option value="other">Other</option>
                              </select>
                              <input type="file" className="text-sm" onChange={(e) => {
                                const file = e.target.files?.[0];
                                if (!file) return;
                                const sel = document.getElementById(`type-${l.id}`);
                                const assetType = sel?.value || 'other';
                                uploadAsset(l.id, assetType, file);
                              }} />
                            </div>
                            <div className="mt-2 text-xs text-slate-500">Upload rules are controlled via Admin Settings (max size + allowed types).</div>
                          </div>

                          <div>
                            <div className="text-xs font-semibold text-slate-600">Current Assets</div>
                            <div className="mt-2 space-y-1">
                              {(l.assets || []).length === 0 ? <div className="text-sm text-slate-600">No assets.</div> : null}
                              {(l.assets || []).map((a) => (
                                <div key={a.id} className="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                                  <div className="text-sm font-semibold text-slate-900">{String(a.asset_type).toUpperCase()}</div>
                                  <div className="text-xs text-slate-500">{a.meta_json?.duration_sec ? `Duration: ${a.meta_json.duration_sec}s` : a.meta_json?.pages ? `Pages: ${a.meta_json.pages}` : ''}</div>
                                </div>
                              ))}
                            </div>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              ))}

              {course.modules?.length === 0 ? <div className="text-slate-600">No modules yet. Create the first module on the right.</div> : null}
            </div>
          </Card>
        </div>

        {/* right: create module/lesson */}
        <div className="md:col-span-4">
          <Card>
            <div className="text-sm font-extrabold text-slate-900">Create Module</div>
            <div className="mt-1 text-xs text-slate-500">Modules group lessons in the learner outline.</div>
            <input value={newModuleTitle} onChange={(e)=>setNewModuleTitle(e.target.value)} placeholder="e.g. Site Safety" className="mt-3 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
            <button onClick={addModule} className="mt-3 w-full rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Add Module</button>
          </Card>

          <Card className="mt-4">
            <div className="text-sm font-extrabold text-slate-900">Create Lesson</div>
            <div className="mt-1 text-xs text-slate-500">Select a module, enter lesson details, and create.</div>

            <div className="mt-3 space-y-3">
              <div>
                <div className="text-xs font-semibold text-slate-600">Module</div>
                <select value={lessonDraft.moduleId || ''} onChange={(e)=>setLessonDraft({...lessonDraft, moduleId: Number(e.target.value) || null})} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                  <option value="">Select module…</option>
                  {(course.modules || []).map((m)=> <option key={m.id} value={m.id}>{titleOf(m, `Module ${m.sort_order}`)}</option>)}
                </select>
              </div>
              <div>
                <div className="text-xs font-semibold text-slate-600">Title</div>
                <input value={lessonDraft.title} onChange={(e)=>setLessonDraft({...lessonDraft, title:e.target.value})} placeholder="e.g. PPE Requirements" className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <div className="text-xs font-semibold text-slate-600">Type</div>
                  <select value={lessonDraft.type} onChange={(e)=>setLessonDraft({...lessonDraft, type:e.target.value})} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="video">Video</option>
                    <option value="pdf">PDF</option>
                    <option value="ppt">PPT</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div>
                  <div className="text-xs font-semibold text-slate-600">Required</div>
                  <select value={lessonDraft.required ? '1' : '0'} onChange={(e)=>setLessonDraft({...lessonDraft, required: e.target.value==='1'})} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                  </select>
                </div>
              </div>

              {lessonDraft.type === 'video' ? (
                <div>
                  <div className="text-xs font-semibold text-slate-600">Minimum Watch %</div>
                  <input type="number" min={1} max={100} value={lessonDraft.min_watch_percent} onChange={(e)=>setLessonDraft({...lessonDraft, min_watch_percent: e.target.value})} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                </div>
              ) : null}

              {(lessonDraft.type === 'pdf' || lessonDraft.type === 'ppt') ? (
                <div>
                  <div className="text-xs font-semibold text-slate-600">Must View All Pages</div>
                  <select value={lessonDraft.must_view_all_slides ? '1' : '0'} onChange={(e)=>setLessonDraft({...lessonDraft, must_view_all_slides: e.target.value==='1'})} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                  </select>
                </div>
              ) : null}

              <button onClick={addLesson} disabled={!lessonDraft.moduleId} className="w-full rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                Add Lesson
              </button>
            </div>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
