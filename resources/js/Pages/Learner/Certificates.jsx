import React, { useEffect, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/Card';
import { apiFetch } from '@/lib/apiClient';

export default function Certificates() {
  const [rows, setRows] = useState([]);
  const [err, setErr] = useState('');
  const [loading, setLoading] = useState(true);

  async function load() {
    setErr('');
    setLoading(true);
    try {
      const res = await apiFetch('/api/my-certificates');
      setRows(res?.data || []);
    } catch (e) {
      setErr(e?.message || 'Failed to load certificates');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, []);

  const nav = [
    { href: '/learner/dashboard', label: 'Dashboard' },
    { href: '/learner/my-courses', label: 'My Courses' },
    { href: '/learner/certificates', label: 'Certificates' },
  ];

  return (
    <AppLayout
      title="Certificates"
      nav={nav}
      right={
        <button onClick={load} className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
          Refresh
        </button>
      }
    >
      {loading ? <div className="text-slate-600">Loading…</div> : null}
      {err ? <div className="text-red-600">{err}</div> : null}

      <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        {rows.map((c) => (
          <Card key={c.id}>
            <div className="text-sm font-bold text-slate-900">{c.course_code}</div>
            <div className="mt-2 text-xs text-slate-600">Cert No: <span className="font-semibold">{c.certificate_no}</span></div>
            <div className="text-xs text-slate-600">Issued: <span className="font-semibold">{c.issued_at || '-'}</span></div>
            <div className="mt-3 flex items-center gap-2">
              <a href={`/api/certificates/${c.id}/download`} className="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Download PDF
              </a>
              <a href={c.verify_url} target="_blank" rel="noreferrer" className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold hover:bg-slate-50">
                Verify
              </a>
            </div>
          </Card>
        ))}
      </div>

      {!loading && rows.length === 0 ? (
        <div className="mt-6 text-slate-600">No certificates issued yet.</div>
      ) : null}
    </AppLayout>
  );
}
