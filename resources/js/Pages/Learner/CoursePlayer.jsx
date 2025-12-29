import React, { useEffect, useMemo, useRef, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/Card';
import ProgressBar from '@/Components/ProgressBar';
import VideoLessonPlayer from '@/Components/Player/VideoLessonPlayer';
import PdfLessonPlayer from '@/Components/Player/PdfLessonPlayer';
import { apiGet, apiPost } from '@/lib/apiClient';

function lessonTitle(l) {
  return l?.translations?.[0]?.title || `Lesson ${l?.id}`;
}

export default function CoursePlayer({ enrollmentId }) {
  const [enrollment, setEnrollment] = useState(null);
  const [assessments, setAssessments] = useState([]);
  const [selectedId, setSelectedId] = useState(null);
  const [err, setErr] = useState('');
  const [loading, setLoading] = useState(true);
  const [toast, setToast] = useState('');

  const nav = [
    { href: '/learner/my-courses', label: 'My Courses' },
    { href: '/learner/dashboard', label: 'Dashboard' },
  ];

  async function load() {
    setErr('');
    setLoading(true);
    let e = null;
    try {
      e = await apiGet(`/api/player/enrollments/${enrollmentId}`);
      if (!e) return null;
      setEnrollment(e);
      try {
        const as = await apiGet(`/api/courses/${e.course?.id}/assessments`);
        setAssessments(as || []);
      } catch (_) { /* ignore */ }

      // default selection: first unlocked lesson
      const lessons = [];
      for (const m of (e.course?.modules || []).slice().sort((a,b)=>a.sort_order-b.sort_order)) {
        for (const l of (m.lessons || []).slice().sort((a,b)=>a.sort_order-b.sort_order)) lessons.push(l);
      }
      const firstUnlocked = lessons.find(x => !x.is_locked) || lessons[0];
      setSelectedId((prev) => prev || firstUnlocked?.id || null);
    } catch (x) {
      setErr(x.message);
      return null;
    } finally {
      setLoading(false);
    }

    return e;
  }

  useEffect(() => { load(); }, [enrollmentId]);

  const flatLessons = useMemo(() => {
    if (!enrollment?.course?.modules) return [];
    const out = [];
    const modules = enrollment.course.modules.slice().sort((a,b)=>a.sort_order-b.sort_order);
    for (const m of modules) {
      const lessons = (m.lessons || []).slice().sort((a,b)=>a.sort_order-b.sort_order);
      for (const l of lessons) out.push({ module: m, lesson: l });
    }
    return out;
  }, [enrollment]);

  const selected = useMemo(() => {
    return flatLessons.find(x => x.lesson.id === selectedId)?.lesson || null;
  }, [flatLessons, selectedId]);

  function nextLessonId(afterLessonId, latestEnrollment) {
    const e = latestEnrollment || enrollment;
    if (!e?.course?.modules) return null;
    const ordered = [];
    for (const m of e.course.modules.slice().sort((a,b)=>a.sort_order-b.sort_order)) {
      for (const l of (m.lessons || []).slice().sort((a,b)=>a.sort_order-b.sort_order)) {
        ordered.push(l);
      }
    }
    const idx = ordered.findIndex(l => l.id === afterLessonId);
    if (idx < 0) return null;
    for (let i = idx + 1; i < ordered.length; i++) {
      const l = ordered[i];
      if (l.is_locked) continue;
      if (l.progress?.completed_at) continue;
      return l.id;
    }
    // If everything after is done, keep same selection
    return null;
  }

  function showBlocked() {
    setToast('This lesson is locked. Complete previous lessons first.');
    setTimeout(() => setToast(''), 3500);
  }

  if (loading) {
    return <AppLayout title="Course Player" nav={nav}><div className="text-slate-600">Loading...</div></AppLayout>;
  }
  if (err) {
    return <AppLayout title="Course Player" nav={nav}><div className="text-red-600">{err}</div></AppLayout>;
  }
  if (!enrollment) {
    return <AppLayout title="Course Player" nav={nav}><div className="text-slate-600">Not found</div></AppLayout>;
  }

  const courseTitle = enrollment.course?.translations?.[0]?.title || enrollment.course?.code;

  const allRequiredDone = flatLessons.length > 0 && flatLessons.every(x => (x.lesson.required ?? true) ? !!x.lesson.progress?.completed_at : true);
  const firstAssessment = (assessments || [])[0] || null;

  return (
    <AppLayout
      title={courseTitle}
      nav={nav}
      right={
        <button onClick={load} className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
          Refresh
        </button>
      }
    >
      <div className="grid grid-cols-1 gap-4 md:grid-cols-12">
        {/* Left outline */}
        <div className="md:col-span-4">
          <Card>
            <div className="text-sm font-extrabold text-slate-900">Course Outline</div>
            <div className="mt-1 text-xs text-slate-500">Sequential lock enabled. Complete lessons in order.</div>

            <div className="mt-4 space-y-5">
              {(enrollment.course?.modules || []).slice().sort((a,b)=>a.sort_order-b.sort_order).map((m) => (
                <div key={m.id}>
                  <div className="text-sm font-extrabold text-slate-900">{m.translations?.[0]?.title || `Module ${m.sort_order}`}</div>
                  <div className="mt-2 space-y-2">
                    {(m.lessons || []).slice().sort((a,b)=>a.sort_order-b.sort_order).map((l) => {
                      const percent = l.progress?.progress_percent ?? 0;
                      const done = !!l.progress?.completed_at;
                      const locked = !!l.is_locked;
                      const active = selected?.id === l.id;

                      return (
                        <button
                          key={l.id}
                          disabled={locked}
                          onClick={() => !locked && setSelectedId(l.id)}
                          className={[
                            'w-full rounded-xl border px-3 py-3 text-left transition',
                            locked ? 'cursor-not-allowed border-slate-200 bg-slate-50 opacity-70' : 'border-slate-200 bg-white hover:bg-slate-50',
                            active ? 'ring-2 ring-slate-900' : '',
                          ].join(' ')}
                        >
                          <div className="flex items-start justify-between gap-3">
                            <div>
                              <div className="text-sm font-bold text-slate-900">{lessonTitle(l)}</div>
                              <div className="mt-0.5 text-xs text-slate-500">{l.type.toUpperCase()}</div>
                            </div>
                            <div className={['text-xs font-extrabold', done ? 'text-emerald-700' : locked ? 'text-rose-700' : 'text-slate-700'].join(' ')}>
                              {done ? 'DONE' : locked ? 'LOCKED' : `${Math.round(percent)}%`}
                            </div>
                          </div>
                          <div className="mt-2">
                            <ProgressBar value={done ? 100 : percent} />
                          </div>
                          {locked ? <div className="mt-2 text-xs text-rose-700">{l.unlock_reason || 'Complete previous lessons first.'}</div> : null}
                        </button>
                      );
                    })}
                  </div>
                </div>
              ))}
            </div>
          </Card>
        </div>

        {/* Right player */}
        <div className="md:col-span-8">
          <Card>
            {toast ? (
              <div className="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                {toast}
              </div>
            ) : null}
            {!selected ? (
              <div className="text-slate-600">Select a lesson.</div>
            ) : (
              <>
                <div className="flex items-start justify-between gap-4">
                  <div>
                    <div className="text-xl font-extrabold text-slate-900">{lessonTitle(selected)}</div>
                    <div className="mt-1 text-sm text-slate-500">Type: {selected.type.toUpperCase()}</div>
                  </div>
                  <div className="rounded-xl bg-slate-100 px-3 py-1 text-sm font-extrabold text-slate-900">
                    {selected.progress?.completed_at ? 'Completed' : `${Math.round(selected.progress?.progress_percent ?? 0)}%`}
                  </div>
                </div>

                {/* Resume playback banner (Udemy-style) */}
                {(!selected.progress?.completed_at) ? (() => {
                  const meta = selected.progress?.meta_json || {};
                  let resumeText = '';
                  if (selected.type === 'video' && (meta.watched_sec || 0) >= 5) {
                    const s = Math.floor(meta.watched_sec || 0);
                    const mm = String(Math.floor(s / 60)).padStart(2, '0');
                    const ss = String(s % 60).padStart(2, '0');
                    resumeText = `Resume from ${mm}:${ss}`;
                  }
                  if ((selected.type === 'pdf' || selected.type === 'ppt') && (meta.last_page || 1) > 1) {
                    resumeText = `Resume from page ${meta.last_page}`;
                  }
                  if (!resumeText) return null;
                  return (
                    <div className="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                      <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                          <div className="text-sm font-extrabold text-slate-900">Continue where you left off</div>
                          <div className="mt-0.5 text-xs text-slate-600">{resumeText}. Progress is enforced; skipping is not allowed.</div>
                        </div>
                        <button
                          onClick={() => setToast('Resume is automatic when the player loads.')}
                          className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                          Resume
                        </button>
                      </div>
                    </div>
                  );
                })() : null}

                <div className="mt-4">
                  {selected.type === 'video' ? (
                    <VideoBlock
                      lesson={selected}
                      enrollmentId={enrollmentId}
                      onRefresh={load}
                      onAutoNext={(latestEnrollment) => {
                        const nextId = nextLessonId(selected.id, latestEnrollment);
                        if (nextId) setSelectedId(nextId);
                      }}
                      onBlocked={showBlocked}
                    />
                  ) : null}

                  {(selected.type === 'pdf' || selected.type === 'ppt') ? (
                    <PdfBlock
                      lesson={selected}
                      enrollmentId={enrollmentId}
                      onRefresh={load}
                      onAutoNext={(latestEnrollment) => {
                        const nextId = nextLessonId(selected.id, latestEnrollment);
                        if (nextId) setSelectedId(nextId);
                      }}
                      onBlocked={showBlocked}
                    />
                  ) : null}
                </div>
                {allRequiredDone && firstAssessment ? (
                  <div className="mt-6 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                      <div>
                        <div className="text-sm font-extrabold text-slate-900">Assessment unlocked</div>
                        <div className="mt-0.5 text-xs text-slate-700">
                          {firstAssessment.title} • Pass {firstAssessment.pass_percent}% • Attempts {firstAssessment.attempts_limit ?? '-'} • Soft timer
                        </div>
                      </div>
                      <a
                        href={`/learner/assessment/${firstAssessment.id}/${enrollment.id}`}
                        className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                      >
                        Start Assessment
                      </a>
                    </div>
                  </div>
                ) : null}

              </>
            )}
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}

function VideoBlock({ lesson, enrollmentId, onRefresh, onAutoNext, onBlocked }) {
  const asset = (lesson.assets || []).find((a) => a.asset_type === 'video') || lesson.assets?.[0];
  const initialWatchedSec = lesson.progress?.meta_json?.watched_sec || 0;
  const minWatchPercent = lesson.min_watch_percent || 90;

  async function onProgressPush(payload) {
    const res = await apiPost(`/api/lessons/${lesson.id}/progress`, {
      enrollment_id: Number(enrollmentId),
      payload,
    });
    // Refresh outline to unlock next lesson when server marks completed
    const latest = await onRefresh();
    // Auto-next: when lesson becomes completed, jump to next unlocked lesson
    if (onAutoNext && latest) {
      const current = (() => {
        for (const m of (latest.course?.modules || [])) {
          for (const l of (m.lessons || [])) if (l.id === lesson.id) return l;
        }
        return null;
      })();
      if (current?.progress?.completed_at) {
        onAutoNext(latest);
      }
    }
    return res;
  }

  return (
    <VideoLessonPlayer
      accessUrl={asset?.access_url}
      minWatchPercent={minWatchPercent}
      initialWatchedSec={initialWatchedSec}
      onProgressPush={onProgressPush}
      onBlocked={onBlocked}
    />
  );
}

function PdfBlock({ lesson, enrollmentId, onRefresh, onAutoNext, onBlocked }) {
  const asset = (lesson.assets || []).find((a) => a.asset_type === 'pdf' || a.asset_type === 'ppt') || lesson.assets?.[0];
  const initialVisited = lesson.progress?.meta_json?.pages_visited || [];
  const initialLastPage = lesson.progress?.meta_json?.last_page || 1;

  async function onProgressPush(payload) {
    const res = await apiPost(`/api/lessons/${lesson.id}/progress`, {
      enrollment_id: Number(enrollmentId),
      payload,
    });
    const latest = await onRefresh();
    if (onAutoNext && latest) {
      const current = (() => {
        for (const m of (latest.course?.modules || [])) {
          for (const l of (m.lessons || [])) if (l.id === lesson.id) return l;
        }
        return null;
      })();
      if (current?.progress?.completed_at) {
        onAutoNext(latest);
      }
    }
    return res;
  }

  return (
    <PdfLessonPlayer
      accessUrl={asset?.access_url}
      initialVisited={initialVisited}
      initialLastPage={initialLastPage}
      onProgressPush={onProgressPush}
      onBlocked={onBlocked}
    />
  );
}
