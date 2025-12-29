import React, { useEffect, useState } from "react";
import AppLayout from "@/Layouts/AppLayout";
import Card from "@/Components/Card";
import Button from "@/Components/Button";
import { apiFetch, apiSend } from "@/lib/apiClient";

export default function Index() {
  const [rows, setRows] = useState([]);
  const [courses, setCourses] = useState([]);
  const [courseId, setCourseId] = useState("");
  const [err, setErr] = useState("");
  const [creating, setCreating] = useState(false);
  const [form, setForm] = useState({
    title: "Safety Induction Quiz",
    mode: "quiz",
    duration_sec: 900,
    attempts_limit: 3,
    pass_percent: 80,
    critical_enabled: true,
    allowed_critical_mistakes: 0,
    shuffle_questions: true,
    shuffle_options: true,
  });

  const nav = [
    { href: "/admin/dashboard", label: "Dashboard" },
    { href: "/admin/users", label: "Users" },
    { href: "/admin/courses", label: "Courses" },
    { href: "/admin/assessments", label: "Assessments" },
    { href: "/admin/enrollments", label: "Enrollments" },
    { href: "/admin/reports/completions", label: "Reports" },
    { href: "/admin/settings", label: "Settings" },
  ];

  async function load() {
    setErr("");
    try {
      const cs = await apiFetch("/api/courses");
      setCourses(cs || []);
      const cid = courseId || (cs?.[0]?.id ? String(cs[0].id) : "");
      if (!courseId && cid) setCourseId(cid);
      const data = await apiFetch(`/api/admin/assessments${cid ? `?course_id=${cid}` : ""}`);
      setRows(data || []);
    } catch (e) {
      setErr(e.message);
    }
  }

  useEffect(() => { load(); }, []);

  async function createAssessment() {
    setCreating(true);
    setErr("");
    try {
      const payload = { ...form, course_id: Number(courseId) };
      await apiSend("/api/admin/assessments", "POST", payload);
      await load();
    } catch (e) {
      setErr(e.message);
    } finally {
      setCreating(false);
    }
  }

  return (
    <AppLayout title="Assessments" nav={nav} headerRight={<Button onClick={load}>Refresh</Button>}>
      {err ? <div className="text-red-600">{err}</div> : null}

      <Card>
        <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div className="flex-1">
            <label className="text-xs text-gray-600">Course</label>
            <select
              className="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"
              value={courseId}
              onChange={(e) => setCourseId(e.target.value)}
            >
              {courses.map((c) => (
                <option key={c.id} value={String(c.id)}>{c.title || c.code}</option>
              ))}
            </select>
          </div>

          <div className="flex gap-2">
            <Button variant="secondary" onClick={load}>Apply</Button>
          </div>
        </div>
      </Card>

      <Card className="mt-4">
        <div className="text-sm font-bold text-gray-900">Create Assessment</div>
        <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label className="text-xs text-gray-600">Title</label>
            <input className="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"
              value={form.title} onChange={(e)=>setForm(f=>({...f,title:e.target.value}))}/>
          </div>
          <div>
            <label className="text-xs text-gray-600">Duration (seconds)</label>
            <input type="number" className="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"
              value={form.duration_sec ?? ""} onChange={(e)=>setForm(f=>({...f,duration_sec:Number(e.target.value)}))}/>
          </div>
          <div>
            <label className="text-xs text-gray-600">Pass %</label>
            <input type="number" className="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"
              value={form.pass_percent} onChange={(e)=>setForm(f=>({...f,pass_percent:Number(e.target.value)}))}/>
          </div>
          <div>
            <label className="text-xs text-gray-600">Attempts limit</label>
            <input type="number" className="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm"
              value={form.attempts_limit ?? ""} onChange={(e)=>setForm(f=>({...f,attempts_limit:Number(e.target.value)}))}/>
          </div>
        </div>
        <div className="mt-3 flex gap-2">
          <Button disabled={!courseId || creating} onClick={createAssessment}>
            {creating ? "Creating..." : "Create"}
          </Button>
        </div>
      </Card>

      <div className="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 text-left text-gray-600">
            <tr>
              <th className="px-4 py-3">Title</th>
              <th className="px-4 py-3">Pass %</th>
              <th className="px-4 py-3">Attempts</th>
              <th className="px-4 py-3">Duration</th>
              <th className="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            {rows.map((a) => (
              <tr key={a.id} className="border-t border-gray-100">
                <td className="px-4 py-3 font-semibold text-gray-900">{a.title}</td>
                <td className="px-4 py-3">{a.pass_percent}%</td>
                <td className="px-4 py-3">{a.attempts_limit ?? "-"}</td>
                <td className="px-4 py-3">{a.duration_sec ? `${a.duration_sec}s` : "-"}</td>
                <td className="px-4 py-3 text-right">
                  <a className="text-blue-600 hover:underline" href={`/admin/assessments/${a.id}`}>Builder</a>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {rows.length === 0 ? <div className="px-4 py-6 text-gray-600">No assessments yet.</div> : null}
      </div>
    </AppLayout>
  );
}
