import React, { useEffect, useMemo, useState } from "react";
import AppLayout from "@/Layouts/AppLayout";
import Card from "@/Components/Card";
import { apiGet, apiPut, apiUpload, apiPost } from "@/lib/apiClient";

const DEFAULT_ALLOWED = {
  pdf: ["application/pdf"],
  video: ["video/mp4", "video/quicktime", "video/x-matroska"],
  ppt: [
    "application/vnd.ms-powerpoint",
    "application/vnd.openxmlformats-officedocument.presentationml.presentation",
  ],
  image: ["image/png", "image/jpeg", "image/webp"],
};

const TEMPLATE_KEYS = [
  { key: "enrollment_assigned", label: "Enrollment Assigned" },
  { key: "course_completed", label: "Course Completed" },
  { key: "certificate_issued", label: "Certificate Issued" },
];

export default function AdminSettings() {
  const nav = useMemo(
    () => [
      { href: "/admin/dashboard", label: "Dashboard" },
      { href: "/admin/users", label: "Users" },
      { href: "/admin/courses", label: "Courses" },
      { href: "/admin/enrollments", label: "Enrollments" },
      { href: "/admin/certificates", label: "Certificates" },
      { href: "/admin/reports/completions", label: "Reports" },
      { href: "/admin/settings", label: "Settings" },
    ],
    []
  );

  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState("");
  const [msg, setMsg] = useState("");

  // Branding
  const [portalName, setPortalName] = useState("LMS");
  const [companyName, setCompanyName] = useState("LMS");
  const [primaryColor, setPrimaryColor] = useState("#2563eb");
  const [logoPath, setLogoPath] = useState(null);

  // Storage
  const [privateAssets, setPrivateAssets] = useState(false);

  // Uploads
  const [maxMb, setMaxMb] = useState(50);
  const [allowed, setAllowed] = useState(DEFAULT_ALLOWED);

  // Email
  const [emailEnabled, setEmailEnabled] = useState(false);
  const [emailFromName, setEmailFromName] = useState("LMS");
  const [emailFromEmail, setEmailFromEmail] = useState("");
  const [testEmailTo, setTestEmailTo] = useState("");

  // Email templates
  const [templates, setTemplates] = useState({
    enrollment_assigned: { subject: "", body: "" },
    course_completed: { subject: "", body: "" },
    certificate_issued: { subject: "", body: "" },
  });
  const [tplKey, setTplKey] = useState("enrollment_assigned");
  const [preview, setPreview] = useState({ subject: "", body: "" });
  const [previewOpen, setPreviewOpen] = useState(false);

  async function load() {
    setErr("");
    setMsg("");
    setLoading(true);
    try {
      const s = await apiGet("/api/admin/settings");
      const b = s.branding || {};
      setPortalName(b.portal_name ?? "LMS");
      setCompanyName(b.company_name ?? (b.portal_name ?? "LMS"));
      setPrimaryColor(b.primary_color ?? "#2563eb");
      setLogoPath(b.logo_path ?? null);

      const storage = s.storage || { mode: "local", private_assets: false };
      setPrivateAssets(!!storage.private_assets);

      const uploads = s.uploads || {};
      setMaxMb(uploads.max_mb ?? 50);
      setAllowed(uploads.allowed_mimes ?? DEFAULT_ALLOWED);

      const e = s.email || {};
      setEmailEnabled(!!e.enabled);
      setEmailFromName(e.from_name ?? (b.portal_name ?? "LMS"));
      setEmailFromEmail(e.from_email ?? "");
      setTemplates({
        enrollment_assigned: e?.templates?.enrollment_assigned ?? { subject: "", body: "" },
        course_completed: e?.templates?.course_completed ?? { subject: "", body: "" },
        certificate_issued: e?.templates?.certificate_issued ?? { subject: "", body: "" },
      });
    } catch (e) {
      setErr(e.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
  }, []);

  function updateMimeList(type, text) {
    const lines = text
      .split("\n")
      .map((x) => x.trim())
      .filter(Boolean);
    setAllowed((prev) => ({ ...prev, [type]: lines }));
  }

  async function saveAll() {
    setErr("");
    setMsg("");
    try {
      await apiPut("/api/admin/settings", {
        branding: {
          portal_name: portalName,
          company_name: companyName,
          primary_color: primaryColor,
          logo_path: logoPath,
        },
        storage: { mode: "local", private_assets: privateAssets },
        uploads: { max_mb: Number(maxMb), allowed_mimes: allowed },
        email: {
          enabled: emailEnabled,
          from_name: emailFromName,
          from_email: emailFromEmail || null,
          templates,
        },
      });
      setMsg("Saved");
      await load();
    } catch (e) {
      setErr(e.message);
    }
  }

  async function uploadLogo(file) {
    if (!file) return;
    setErr("");
    setMsg("");
    try {
      const fd = new FormData();
      fd.append("logo", file);
      const res = await apiUpload("/api/admin/branding/logo", fd);
      setLogoPath(res.logo_path);
      setMsg("Logo uploaded");
    } catch (e) {
      setErr(e.message);
    }
  }

  async function sendTestEmail() {
    setErr("");
    setMsg("");
    try {
      // Save first to ensure templates/settings are current
      await saveAll();
      await apiPost("/api/admin/email/test", { to: testEmailTo || null, template_key: tplKey });
      setMsg("Test email sent (if SMTP is configured).");
    } catch (e) {
      setErr(e.message);
    }
  }

  async function previewTemplate() {
    setErr("");
    try {
      const res = await apiPost("/api/admin/email/preview", {
        template_key: tplKey,
        vars: {
          learner_name: "Test User",
          course_name: "Safety Induction",
          due_date: "—",
          certificate_url: `${window.location.origin}/learner/certificates`,
        },
      });
      setPreview(res);
      setPreviewOpen(true);
    } catch (e) {
      setErr(e.message);
    }
  }

  function setTplField(field, value) {
    setTemplates((prev) => ({
      ...prev,
      [tplKey]: { ...(prev[tplKey] || {}), [field]: value },
    }));
  }

  return (
    <AppLayout
      title="Settings"
      nav={nav}
      right={
        <div className="flex gap-2">
          <button
            onClick={load}
            className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            Reload
          </button>
          <button
            onClick={saveAll}
            className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
          >
            Save
          </button>
        </div>
      }
    >
      {loading ? <div className="text-slate-600">Loading…</div> : null}
      {err ? <div className="mt-2 text-red-600">{err}</div> : null}
      {msg ? <div className="mt-2 text-emerald-700">{msg}</div> : null}

      <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
        {/* Branding */}
        <Card>
          <div className="text-sm font-extrabold text-slate-900">Branding</div>
          <div className="mt-1 text-xs text-slate-500">Portal name, company name, blue primary color and logo.</div>

          <div className="mt-4">
            <div className="text-xs font-semibold text-slate-600">Portal name</div>
            <input
              value={portalName}
              onChange={(e) => setPortalName(e.target.value)}
              className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
            />
          </div>

          <div className="mt-4">
            <div className="text-xs font-semibold text-slate-600">Company name (certificates + emails)</div>
            <input
              value={companyName}
              onChange={(e) => setCompanyName(e.target.value)}
              className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
            />
          </div>

          <div className="mt-4">
            <div className="text-xs font-semibold text-slate-600">Primary color</div>
            <input
              value={primaryColor}
              onChange={(e) => setPrimaryColor(e.target.value)}
              className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-mono"
            />
            <div className="mt-2 text-xs text-slate-500">Recommended: #2563eb</div>
          </div>

          <div className="mt-4">
            <div className="text-xs font-semibold text-slate-600">Logo</div>
            <div className="mt-2 flex items-center gap-3">
              {logoPath ? (
                <img src={logoPath} alt="logo" className="h-10 w-10 rounded-xl border border-slate-200 object-contain bg-white" />
              ) : (
                <div className="h-10 w-10 rounded-xl border border-dashed border-slate-200 bg-slate-50" />
              )}
              <input type="file" accept="image/*" onChange={(e) => uploadLogo(e.target.files?.[0])} />
            </div>
          </div>
        </Card>

        {/* Email */}
        <Card>
          <div className="text-sm font-extrabold text-slate-900">Email notifications</div>
          <div className="mt-1 text-xs text-slate-500">Enrollment + completion emails (SMTP required).</div>

          <label className="mt-4 flex items-center gap-2 text-sm font-semibold text-slate-700">
            <input type="checkbox" checked={emailEnabled} onChange={(e) => setEmailEnabled(e.target.checked)} />
            Enable emails
          </label>

          <div className="mt-4">
            <div className="text-xs font-semibold text-slate-600">From name</div>
            <input
              value={emailFromName}
              onChange={(e) => setEmailFromName(e.target.value)}
              className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
            />
          </div>

          <div className="mt-4">
            <div className="text-xs font-semibold text-slate-600">From email (optional)</div>
            <input
              value={emailFromEmail}
              onChange={(e) => setEmailFromEmail(e.target.value)}
              className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
              placeholder="mail@company.com"
            />
          </div>

          <div className="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
              <div className="text-xs font-semibold text-slate-600">Test recipient</div>
              <input
                value={testEmailTo}
                onChange={(e) => setTestEmailTo(e.target.value)}
                className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                placeholder="recipient@example.com"
              />
            </div>
            <div>
              <div className="text-xs font-semibold text-slate-600">Template</div>
              <select
                value={tplKey}
                onChange={(e) => setTplKey(e.target.value)}
                className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
              >
                {TEMPLATE_KEYS.map((t) => (
                  <option key={t.key} value={t.key}>{t.label}</option>
                ))}
              </select>
            </div>
          </div>

          <div className="mt-4 flex gap-2">
            <button
              onClick={previewTemplate}
              className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold hover:bg-slate-50"
            >
              Preview
            </button>
            <button
              onClick={sendTestEmail}
              className="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
            >
              Send test
            </button>
          </div>
        </Card>

        {/* Email templates */}
        <Card className="md:col-span-2">
          <div className="text-sm font-extrabold text-slate-900">Email templates</div>
          <div className="mt-1 text-xs text-slate-500">
            Edit subject/body with placeholders: <code>{'{{ learner_name }}'}</code>,{' '}
            <code>{'{{ course_name }}'}</code>, <code>{'{{ due_date }}'}</code>,{' '}
            <code>{'{{ certificate_url }}'}</code>, <code>{'{{ portal_url }}'}</code>,{' '}
            <code>{'{{ company_name }}'}</code>.
          </div>

          <div className="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
              <div className="text-xs font-semibold text-slate-600">Subject</div>
              <input
                value={(templates[tplKey]?.subject) || ""}
                onChange={(e) => setTplField("subject", e.target.value)}
                className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
              />
            </div>
          </div>

          <div className="mt-4">
            <div className="text-xs font-semibold text-slate-600">Body</div>
            <textarea
              value={(templates[tplKey]?.body) || ""}
              onChange={(e) => setTplField("body", e.target.value)}
              rows={10}
              className="mt-2 w-full rounded-xl border border-slate-200 p-3 text-sm"
            />
            <div className="mt-2 text-xs text-slate-500">Use plain text; new lines are preserved. Phase 3 can upgrade to rich HTML builder.</div>
          </div>
        </Card>

        {/* Storage */}
        <Card>
          <div className="text-sm font-extrabold text-slate-900">Storage</div>
          <div className="mt-1 text-xs text-slate-500">Local storage only (S3/MinIO later).</div>

          <label className="mt-4 flex items-center gap-2 text-sm font-semibold text-slate-700">
            <input type="checkbox" checked={privateAssets} onChange={(e) => setPrivateAssets(e.target.checked)} />
            Private assets (optional)
          </label>
          <div className="mt-2 text-xs text-slate-500">If disabled, assets use public /storage URLs (best for streaming).</div>
        </Card>

        {/* Upload rules */}
        <Card>
          <div className="text-sm font-extrabold text-slate-900">Uploads</div>
          <div className="mt-1 text-xs text-slate-500">Config-driven limits + allowed MIME types.</div>

          <div className="mt-4">
            <div className="text-xs font-semibold text-slate-600">Max upload size (MB)</div>
            <input
              type="number"
              min={1}
              max={2048}
              value={maxMb}
              onChange={(e) => setMaxMb(e.target.value)}
              className="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
            />
          </div>
        </Card>

        <Card className="md:col-span-2">
          <div className="text-sm font-extrabold text-slate-900">Allowed MIME types</div>
          <div className="mt-1 text-xs text-slate-500">One per line.</div>

          <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            {Object.keys(allowed).map((t) => (
              <div key={t}>
                <div className="text-xs font-extrabold text-slate-900">{t.toUpperCase()}</div>
                <textarea
                  value={(allowed[t] || []).join("\n")}
                  onChange={(e) => updateMimeList(t, e.target.value)}
                  rows={6}
                  className="mt-2 w-full rounded-xl border border-slate-200 p-3 font-mono text-xs"
                />
              </div>
            ))}
          </div>
        </Card>
      </div>

      {/* Preview modal */}
      {previewOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-4">
          <div className="w-full max-w-2xl rounded-2xl bg-white shadow-xl">
            <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
              <div className="text-sm font-extrabold text-slate-900">Email preview</div>
              <button onClick={() => setPreviewOpen(false)} className="rounded-lg px-2 py-1 text-sm hover:bg-slate-50">
                Close
              </button>
            </div>
            <div className="px-4 py-4">
              <div className="text-xs font-semibold text-slate-600">Subject</div>
              <div className="mt-1 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">{preview.subject}</div>

              <div className="mt-4 text-xs font-semibold text-slate-600">Body</div>
              <pre className="mt-1 whitespace-pre-wrap rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">{preview.body}</pre>
            </div>
          </div>
        </div>
      ) : null}
    </AppLayout>
  );
}
