import React, { useEffect, useMemo, useState } from "react";
import AppLayout from "@/Layouts/AppLayout";
import Card from "@/Components/Card";
import Button from "@/Components/Button";
import ConfirmDialog from "@/Components/ConfirmDialog";
import { apiFetch, apiSend } from "@/lib/apiClient";

export default function Builder({ assessmentId }) {
  const [assessment, setAssessment] = useState(null);
  const [err, setErr] = useState("");
  const [qText, setQText] = useState("");
  const [opts, setOpts] = useState([
    { text: "", is_correct: false },
    { text: "", is_correct: false },
    { text: "", is_correct: false },
    { text: "", is_correct: false },
  ]);

  const [activeLang, setActiveLang] = useState("en");
  const [editing, setEditing] = useState(null); // question object
  const [editText, setEditText] = useState("");
  const [editCritical, setEditCritical] = useState(false);
  const [editPoints, setEditPoints] = useState(1);
  const [editOptions, setEditOptions] = useState([
    { text: "", is_correct: true },
    { text: "", is_correct: false },
    { text: "", is_correct: false },
    { text: "", is_correct: false },
  ]);
  const [confirmDelete, setConfirmDelete] = useState({ open: false, q: null });
  const [dragId, setDragId] = useState(null);

  const nav = [
    { href: "/admin/dashboard", label: "Dashboard" },
    { href: "/admin/courses", label: "Courses" },
    { href: "/admin/assessments", label: "Assessments" },
    { href: "/admin/enrollments", label: "Enrollments" },
    { href: "/admin/reports/completions", label: "Reports" },
  ];

  async function load() {
    setErr("");
    try {
      const data = await apiFetch(`/api/admin/assessments/${assessmentId}`);
      setAssessment(data);
    } catch (e) {
      setErr(e.message);
    }
  }

  useEffect(() => { load(); }, [assessmentId]);

  const bankId = useMemo(() => assessment?.banks?.[0]?.id, [assessment]);
  const questions = useMemo(() => {
    const qs = assessment?.banks?.[0]?.questions || [];
    return [...qs].sort((a,b)=> (a.sort_order||0)-(b.sort_order||0));
  }, [assessment]);

  function qTextFor(q) {
    const t = q.translations || [];
    const found = t.find(x => x.lang === activeLang) || t.find(x => x.lang === "en") || t[0];
    return found?.question_text || "(no text)";
  }
  function optTextFor(o) {
    const t = o.translations || [];
    const found = t.find(x => x.lang === activeLang) || t.find(x => x.lang === "en") || t[0];
    return found?.option_text || "";
  }

  async function addQuestion() {
    if (!bankId) return;
    setErr("");
    try {
      const payload = {
        type: "mcq",
        question_text: qText,
        lang: activeLang,
        options: opts.filter(o => o.text.trim().length>0).map(o => ({ text: o.text, is_correct: !!o.is_correct }))
      };
      await apiSend(`/api/admin/banks/${bankId}/questions`, "POST", payload);
      setQText("");
      setOpts([{text:"",is_correct:false},{text:"",is_correct:false},{text:"",is_correct:false},{text:"",is_correct:false}]);
      await load();
    } catch (e) {
      setErr(e.message);
    }
  }

  function openEdit(q) {
    setEditing(q);
    setEditText(qTextFor(q));
    setEditCritical(!!q.is_critical);
    setEditPoints(q.points || 1);
    const qOpts = (q.options || []).map(o => ({ text: optTextFor(o), is_correct: !!o.is_correct }));
    const padded = [...qOpts];
    while (padded.length < 4) padded.push({ text: "", is_correct: false });
    if (!padded.some(x => x.is_correct)) padded[0].is_correct = true;
    setEditOptions(padded);
  }

  function closeEdit() {
    setEditing(null);
  }

  function setSingleCorrect(i) {
    setEditOptions(arr => arr.map((x,idx)=>({ ...x, is_correct: idx===i })));
  }

  async function saveEdit() {
    if (!editing) return;
    setErr("");
    try {
      await apiSend(`/api/admin/questions/${editing.id}`, "PUT", {
        lang: activeLang,
        question_text: editText,
        is_critical: editCritical,
        points: Number(editPoints) || 1,
      });

      const cleanedOptions = editOptions
        .map(o => ({ ...o, text: (o.text||"").trim() }))
        .filter(o => o.text.length > 0);
      if (cleanedOptions.length < 2) {
        throw new Error("Please provide at least 2 options.");
      }
      if (!cleanedOptions.some(o => o.is_correct)) cleanedOptions[0].is_correct = true;
      // enforce single correct
      const firstCorrect = cleanedOptions.findIndex(o => o.is_correct);
      cleanedOptions.forEach((o, idx) => { o.is_correct = idx === firstCorrect; });

      await apiSend(`/api/admin/questions/${editing.id}/options`, "POST", {
        lang: activeLang,
        options: cleanedOptions,
      });

      await load();
      closeEdit();
    } catch (e) {
      setErr(e.message);
    }
  }

  async function doDelete() {
    const q = confirmDelete.q;
    if (!q) return;
    setErr("");
    try {
      await apiSend(`/api/admin/questions/${q.id}`, "DELETE");
      setConfirmDelete({ open: false, q: null });
      await load();
    } catch (e) {
      setErr(e.message);
    }
  }

  async function persistReorder(newOrderIds) {
    if (!bankId) return;
    try {
      await apiSend(`/api/admin/banks/${bankId}/questions/reorder`, "POST", { question_ids: newOrderIds });
    } catch (e) {
      // if reorder fails, reload from server
      await load();
      throw e;
    }
  }

  async function moveQuestion(id, dir) {
    const idx = questions.findIndex(q => q.id === id);
    if (idx < 0) return;
    const nextIdx = idx + dir;
    if (nextIdx < 0 || nextIdx >= questions.length) return;
    const reordered = [...questions];
    const [item] = reordered.splice(idx, 1);
    reordered.splice(nextIdx, 0, item);
    // optimistic UI update
    setAssessment(a => {
      if (!a) return a;
      const b = a.banks?.[0];
      if (!b) return a;
      return {
        ...a,
        banks: [{ ...b, questions: reordered.map((q, i) => ({ ...q, sort_order: i + 1 })) }, ...(a.banks?.slice(1) || [])],
      };
    });
    await persistReorder(reordered.map(q => q.id));
    await load();
  }

  return (
    <AppLayout title="Assessment Builder" nav={nav} headerRight={<Button variant="secondary" onClick={load}>Refresh</Button>}>
      {err ? <div className="text-red-600">{err}</div> : null}
      {!assessment ? <div className="text-gray-600">Loading…</div> : (
        <>
          <Card>
            <div className="text-sm font-bold text-gray-900">{assessment.title}</div>
            <div className="mt-1 text-xs text-gray-600">
              Pass {assessment.pass_percent}% • Attempts {assessment.attempts_limit ?? "-"} • Duration {assessment.duration_sec ? `${assessment.duration_sec}s` : "-"}
            </div>
          </Card>

          <Card className="mt-4">
            <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <div className="text-sm font-bold text-gray-900">Add MCQ Question</div>
                <div className="mt-1 text-xs text-gray-600">Language: {activeLang.toUpperCase()}</div>
              </div>
              <div className="flex items-center gap-2">
                <select className="rounded-xl border border-gray-200 px-3 py-2 text-sm" value={activeLang} onChange={(e)=>setActiveLang(e.target.value)}>
                  <option value="en">EN</option>
                  <option value="ms">MS</option>
                  <option value="ta">TA</option>
                  <option value="zh">ZH</option>
                </select>
              </div>
            </div>
            <div className="mt-3">
              <label className="text-xs text-gray-600">Question</label>
              <textarea className="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"
                rows={3} value={qText} onChange={(e)=>setQText(e.target.value)} />
            </div>

            <div className="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
              {opts.map((o, i) => (
                <div key={i} className="flex items-center gap-2">
                  <input className="flex-1 rounded-xl border border-gray-200 px-3 py-2 text-sm"
                    value={o.text}
                    onChange={(e)=>setOpts(arr=>arr.map((x,idx)=>idx===i?{...x,text:e.target.value}:x))}
                    placeholder={`Option ${i+1}`}
                  />
                  <label className="flex items-center gap-2 text-xs text-gray-700">
                    <input type="radio" name="newCorrect" checked={o.is_correct}
                      onChange={()=>setOpts(arr=>arr.map((x,idx)=>({ ...x, is_correct: idx===i })))} />
                    Correct
                  </label>
                </div>
              ))}
            </div>

            <div className="mt-3">
              <Button disabled={!qText.trim()} onClick={addQuestion}>Add Question</Button>
            </div>
          </Card>

          <Card className="mt-4">
            <div className="flex items-center justify-between">
              <div className="text-sm font-bold text-gray-900">Questions</div>
              <div className="text-xs text-gray-600">Drag to reorder, or use ↑↓</div>
            </div>

            <div className="mt-3 space-y-3">
              {questions.map((q, idx) => (
                <div
                  key={q.id}
                  draggable
                  onDragStart={() => setDragId(q.id)}
                  onDragOver={(e) => e.preventDefault()}
                  onDrop={async () => {
                    if (!dragId || dragId === q.id) return;
                    const from = questions.findIndex(x => x.id === dragId);
                    const to = idx;
                    if (from < 0) return;
                    const reordered = [...questions];
                    const [item] = reordered.splice(from, 1);
                    reordered.splice(to, 0, item);
                    setAssessment(a => {
                      if (!a) return a;
                      const b = a.banks?.[0];
                      if (!b) return a;
                      return { ...a, banks: [{ ...b, questions: reordered.map((qq,i)=>({ ...qq, sort_order: i+1 })) }, ...(a.banks?.slice(1)||[]) ] };
                    });
                    try {
                      await persistReorder(reordered.map(x => x.id));
                      await load();
                    } catch (e) {
                      setErr(e.message);
                    } finally {
                      setDragId(null);
                    }
                  }}
                  className="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="flex-1">
                      <div className="flex items-center gap-2">
                        <div className="text-xs font-extrabold text-blue-700">Q{idx + 1}</div>
                        {q.is_critical ? <span className="rounded-lg bg-rose-50 px-2 py-1 text-[11px] font-bold text-rose-700">Critical</span> : null}
                        <span className="rounded-lg bg-slate-50 px-2 py-1 text-[11px] font-bold text-slate-700">{q.points || 1} pt</span>
                      </div>
                      <div className="mt-2 text-sm font-semibold text-slate-900">{qTextFor(q)}</div>

                      <div className="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                        {(q.options || []).map((o) => (
                          <div key={o.id} className={`rounded-xl border px-3 py-2 text-sm ${o.is_correct ? "border-emerald-200 bg-emerald-50" : "border-slate-200 bg-white"}`}>
                            <div className="flex items-center justify-between gap-2">
                              <div className="text-slate-900">{optTextFor(o)}</div>
                              {o.is_correct ? <div className="text-xs font-bold text-emerald-700">Correct</div> : null}
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>

                    <div className="flex flex-col items-end gap-2">
                      <div className="flex items-center gap-2">
                        <Button variant="secondary" onClick={() => moveQuestion(q.id, -1)} disabled={idx === 0}>↑</Button>
                        <Button variant="secondary" onClick={() => moveQuestion(q.id, 1)} disabled={idx === questions.length - 1}>↓</Button>
                      </div>
                      <div className="flex items-center gap-2">
                        <Button variant="secondary" onClick={() => openEdit(q)}>Edit</Button>
                        <Button variant="danger" onClick={() => setConfirmDelete({ open: true, q })}>Delete</Button>
                      </div>
                    </div>
                  </div>
                </div>
              ))}

              {questions.length === 0 ? <div className="text-gray-600">No questions yet.</div> : null}
            </div>
          </Card>
        </>
      )}

      <ConfirmDialog
        open={confirmDelete.open}
        title="Delete question"
        description="This will permanently remove the question and its options. This cannot be undone."
        confirmText="Delete"
        danger
        onCancel={() => setConfirmDelete({ open: false, q: null })}
        onConfirm={doDelete}
      />

      {editing ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
          <div className="absolute inset-0 bg-slate-900/40" onClick={closeEdit} />
          <div className="relative w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
            <div className="flex items-start justify-between gap-3">
              <div>
                <div className="text-lg font-extrabold text-slate-900">Edit Question</div>
                <div className="mt-1 text-xs text-slate-600">Language: {activeLang.toUpperCase()}</div>
              </div>
              <div className="flex items-center gap-2">
                <select className="rounded-xl border border-gray-200 px-3 py-2 text-sm" value={activeLang} onChange={(e)=>setActiveLang(e.target.value)}>
                  <option value="en">EN</option>
                  <option value="ms">MS</option>
                  <option value="ta">TA</option>
                  <option value="zh">ZH</option>
                </select>
                <Button variant="secondary" onClick={closeEdit}>Close</Button>
              </div>
            </div>

            <div className="mt-4">
              <label className="text-xs text-slate-600">Question text</label>
              <textarea rows={3} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" value={editText} onChange={(e)=>setEditText(e.target.value)} />
            </div>

            <div className="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
              <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={editCritical} onChange={(e)=>setEditCritical(e.target.checked)} />
                Critical question
              </label>
              <div>
                <label className="text-xs text-slate-600">Points</label>
                <input type="number" min={1} max={100} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                  value={editPoints} onChange={(e)=>setEditPoints(e.target.value)} />
              </div>
            </div>

            <div className="mt-4">
              <div className="text-sm font-bold text-slate-900">Options (single correct)</div>
              <div className="mt-2 grid grid-cols-1 gap-2 md:grid-cols-2">
                {editOptions.map((o, i) => (
                  <div key={i} className="flex items-center gap-2">
                    <input className="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm"
                      value={o.text}
                      onChange={(e)=>setEditOptions(arr=>arr.map((x,idx)=>idx===i?{...x,text:e.target.value}:x))}
                      placeholder={`Option ${i+1}`}
                    />
                    <label className="flex items-center gap-2 text-xs text-slate-700">
                      <input type="radio" name="editCorrect" checked={o.is_correct} onChange={()=>setSingleCorrect(i)} />
                      Correct
                    </label>
                  </div>
                ))}
              </div>
            </div>

            <div className="mt-5 flex items-center justify-end gap-2">
              <Button variant="secondary" onClick={closeEdit}>Cancel</Button>
              <Button onClick={saveEdit} disabled={!editText.trim()}>Save</Button>
            </div>
          </div>
        </div>
      ) : null}
    </AppLayout>
  );
}
