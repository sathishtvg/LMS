import React, { useEffect, useMemo, useState } from "react";
import AppLayout from "@/Layouts/AppLayout";
import Card from "@/Components/Card";
import Button from "@/Components/Button";
import { apiFetch, apiSend } from "@/lib/apiClient";

export default function Assessment({ assessmentId, enrollmentId }) {
  const [attempt, setAttempt] = useState(null);
  const [questions, setQuestions] = useState([]);
  const [answers, setAnswers] = useState({});
  const [err, setErr] = useState("");
  const [result, setResult] = useState(null);
  const [left, setLeft] = useState(null);

  const nav = [
    { href: "/learner/my-courses", label: "My Courses" },
    { href: "/learner/certificates", label: "Certificates" },
  ];

  async function start() {
    setErr("");
    try {
      const res = await apiSend(`/api/assessments/${assessmentId}/attempts/start`, "POST", { enrollment_id: Number(enrollmentId) });
      const a = res.attempt;
      setAttempt(a);
      if (res.duration_sec) setLeft(res.duration_sec);
      const qs = await apiFetch(`/api/attempts/${a.id}/questions`);
      setQuestions(qs.questions || []);
    } catch (e) {
      setErr(e.message);
    }
  }

  useEffect(() => { start(); }, [assessmentId, enrollmentId]);

  useEffect(() => {
    if (left === null) return;
    const t = setInterval(() => setLeft((x) => (x === null ? x : Math.max(0, x - 1))), 1000);
    return () => clearInterval(t);
  }, [left]);

  const timeLabel = useMemo(() => {
    if (left === null) return "No timer";
    const m = Math.floor(left / 60);
    const s = left % 60;
    return `${m}:${String(s).padStart(2, "0")}`;
  }, [left]);

  async function submit() {
    setErr("");
    try {
      const payload = {
        answers: questions.map((q) => ({
          question_id: q.id,
          option_id: answers[q.id] || null,
        })),
        client_time_sec: left === null ? null : left,
      };
      const res = await apiSend(`/api/attempts/${attempt.id}/submit`, "POST", payload);
      setResult(res);
    } catch (e) {
      setErr(e.message);
    }
  }

  return (
    <AppLayout title="Assessment" nav={nav} headerRight={<div className="text-sm font-bold text-gray-900">Timer: {timeLabel}</div>}>
      {err ? <div className="text-red-600">{err}</div> : null}

      {result ? (
        <Card>
          <div className="text-lg font-extrabold text-gray-900">{result.passed ? "Passed" : "Failed"}</div>
          <div className="mt-2 text-sm text-gray-700">Score: {result.score_percent}% (Pass: {result.pass_percent}%)</div>
          <div className="mt-1 text-sm text-gray-700">Critical wrong: {result.critical_wrong_count}</div>
          <div className="mt-4">
            <a className="text-blue-600 hover:underline" href="/learner/my-courses">Back to My Courses</a>
          </div>
        </Card>
      ) : (
        <>
          <Card>
            <div className="text-sm text-gray-600">
              Soft timer: you can still submit if time reaches 0 (warning only).
            </div>
          </Card>

          <div className="mt-4 space-y-4">
            {questions.map((q, idx) => (
              <Card key={q.id}>
                <div className="font-bold text-gray-900">Q{idx + 1}. {q.text}</div>
                <div className="mt-3 space-y-2">
                  {(q.options || []).map((o) => (
                    <label key={o.id} className="flex items-center gap-2 rounded-xl border border-gray-200 p-2 hover:bg-gray-50">
                      <input type="radio" name={`q_${q.id}`} checked={answers[q.id] === o.id}
                        onChange={() => setAnswers((a) => ({ ...a, [q.id]: o.id }))} />
                      <span className="text-sm text-gray-800">{o.text}</span>
                    </label>
                  ))}
                </div>
              </Card>
            ))}
          </div>

          <div className="mt-4">
            <Button onClick={submit} disabled={!attempt}>Submit</Button>
          </div>
        </>
      )}
    </AppLayout>
  );
}
