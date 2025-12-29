import React, { useEffect, useMemo, useState } from "react";
import AppLayout from "@/Layouts/AppLayout";
import Card from "@/Components/Card";
import Button from "@/Components/Button";
import ConfirmDialog from "@/Components/ConfirmDialog";
import EmptyState from "@/Components/EmptyState";
import { apiGet, apiPost } from "@/lib/apiClient";

export default function AdminCertificates() {
  const nav = useMemo(() => ([
    { href: '/admin/dashboard', label: 'Dashboard' },
    { href: '/admin/users', label: 'Users' },
    { href: '/admin/courses', label: 'Courses' },
    { href: '/admin/enrollments', label: 'Enrollments' },
    { href: '/admin/certificates', label: 'Certificates' },
    { href: '/admin/reports/completions', label: 'Reports' },
    { href: '/admin/settings', label: 'Settings' },
  ]), []);

  const [q, setQ] = useState('');
  const [status, setStatus] = useState('');
  const [rows, setRows] = useState([]);
  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState('');
  const [confirmId, setConfirmId] = useState(null);

  async function load() {
    setErr('');
    setLoading(true);
    try {
      const qs = new URLSearchParams();
      if (q.trim()) qs.set('q', q.trim());
      if (status) qs.set('status', status);
      const data = await apiGet(`/api/admin/certificates?${qs.toString()}`);
      setRows(data || []);
    } catch (e) {
      setErr(e.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { load(); }, []);

  async function revoke(id) {
    setConfirmId(id);
  }

  async function doRevoke() {
    const id = confirmId;
    if (!id) return;
    setErr('');
    try {
      await apiPost(`/api/admin/certificates/${id}/revoke`, {});
      await load();
    } catch (e) {
      setErr(e.message);
    } finally {
      setConfirmId(null);
    }
  }

  return (
    <AppLayout
      title="Certificates"
      nav={nav}
      right={
        <Button variant="secondary" onClick={load}>Reload</Button>
      }
    >
      {err ? <div className="text-red-600">{err}</div> : null}

      <Card>
        <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div className="flex flex-1 flex-col gap-2 md:flex-row">
            <div className="flex-1">
              <div className="text-xs font-semibold text-slate-600">Search learner</div>
              <input
                value={q}
                onChange={(e) => setQ(e.target.value)}
                placeholder="Name / Email / Phone"
                className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
              />
            </div>
            <div className="w-full md:w-56">
              <div className="text-xs font-semibold text-slate-600">Status</div>
              <select value={status} onChange={(e) => setStatus(e.target.value)} className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="issued">Issued</option>
                <option value="revoked">Revoked</option>
                <option value="expired">Expired</option>
              </select>
            </div>
          </div>

          <Button onClick={load}>Apply</Button>
        </div>
      </Card>

      <div className="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-left text-slate-600">
            <tr>
              <th className="px-4 py-3">Learner</th>
              <th className="px-4 py-3">Course</th>
              <th className="px-4 py-3">Certificate</th>
              <th className="px-4 py-3">Issued</th>
              <th className="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.id} className="border-t border-slate-100">
                <td className="px-4 py-3">
                  <div className="font-semibold text-slate-900">{r.user?.name}</div>
                  <div className="text-xs text-slate-500">{r.user?.email}{r.user?.phone ? ` • ${r.user.phone}` : ''}</div>
                </td>
                <td className="px-4 py-3">{r.course}</td>
                <td className="px-4 py-3">
                  <div className="font-semibold text-slate-900">{r.certificate_no}</div>
                  <div className="text-xs text-slate-500">{r.status}</div>
                </td>
                <td className="px-4 py-3">{r.issued_at || '-'}</td>
                <td className="px-4 py-3">
                  <div className="flex flex-wrap items-center gap-2">
                    <a href={`/api/certificates/${r.id}/download`} className="inline-flex">
                      <Button size="sm">Download</Button>
                    </a>
                    <a
                      href={r.verify_url}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex"
                    >
                      <Button size="sm" variant="secondary">Verify</Button>
                    </a>
                    <Button size="sm" variant="secondary" onClick={() => revoke(r.id)}>Revoke</Button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {loading ? <div className="px-4 py-6 text-slate-600">Loading…</div> : null}
        {!loading && rows.length === 0 ? (
          <div className="px-4 py-8">
            <EmptyState title="No certificates" description="Issue certificates by completing a course as a learner." />
          </div>
        ) : null}
      </div>

      <ConfirmDialog
        open={!!confirmId}
        title="Revoke certificate"
        description="This will mark the certificate as revoked. Learners can no longer use it as proof of completion."
        confirmText="Revoke"
        danger
        onCancel={() => setConfirmId(null)}
        onConfirm={doRevoke}
      />
    </AppLayout>
  );
}
