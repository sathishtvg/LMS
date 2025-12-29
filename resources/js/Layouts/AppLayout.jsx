import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';

function IconMenu(props) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
      <path d="M4 6h16" />
      <path d="M4 12h16" />
      <path d="M4 18h16" />
    </svg>
  );
}

function IconX(props) {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" {...props}>
      <path d="M18 6 6 18" />
      <path d="M6 6l12 12" />
    </svg>
  );
}

function classNames(...xs) {
  return xs.filter(Boolean).join(' ');
}

function SidebarItem({ href, label, icon }) {
  const { url } = usePage();
  const active = url === href || url.startsWith(href + '/');
  return (
    <Link
      href={href}
      className={classNames(
        'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition',
        active ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-slate-100'
      )}
    >
      <span className={classNames('h-9 w-9 rounded-xl grid place-items-center', active ? 'bg-white/10' : 'bg-slate-100')}>
        {icon || <span className="h-2 w-2 rounded-full bg-current opacity-40" />}
      </span>
      {label}
    </Link>
  );
}

export default function AppLayout({ title, nav = [], children, right }) {
  const page = usePage();
  const user = page.props?.auth?.user;
  const apiToken = page.props?.auth?.api_token;
  const branding = page.props?.branding || {};
  const portalName = branding.portal_name || 'LMS';
  const logoPath = branding.logo_path || null;
  const [mobileOpen, setMobileOpen] = useState(false);

  // Persist API token for fetch() calls used by Inertia pages
  if (apiToken && typeof window !== 'undefined') {
    try { localStorage.setItem('token', apiToken); } catch {}
  }

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="flex">
        <aside className="hidden md:flex md:w-72 md:flex-col md:gap-6 md:border-r md:border-slate-200 md:bg-white md:px-5 md:py-6">
          <div className="flex items-center justify-between">
            <div>
              <div className="flex items-center gap-3">
                <div className="h-10 w-10 overflow-hidden rounded-2xl border border-slate-200 bg-white grid place-items-center">
                  {logoPath ? <img src={logoPath} alt="logo" className="h-8 w-8 object-contain" /> : <div className="h-2 w-2 rounded-full bg-blue-600" />}
                </div>
                <div>
                  <div className="text-lg font-extrabold tracking-tight text-slate-900">{portalName}</div>
                  <div className="text-xs text-slate-500">SaaS-ready LMS</div>
                </div>
              </div>
            </div>
          </div>

          <nav className="space-y-1">
            {nav.map((n) => (
              <SidebarItem key={n.href} href={n.href} label={n.label} icon={n.icon} />
            ))}
          </nav>

          <div className="mt-auto rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div className="text-xs text-slate-500">Signed in as</div>
            <div className="mt-1 text-sm font-extrabold text-slate-900">{user?.name || 'User'}</div>
            <div className="text-xs text-slate-600">{user?.role || ''}</div>
          </div>
        </aside>

        <main className="flex-1">
          <div className="sticky top-0 z-20 border-b border-slate-200 bg-white/80 backdrop-blur">
            <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
              <div>
                <div className="text-xl font-extrabold text-slate-900">{title}</div>
                <div className="text-sm text-slate-500">Modern LMS portal</div>
              </div>
              <div className="flex items-center gap-3">
                <button
                  type="button"
                  onClick={() => setMobileOpen(true)}
                  className="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 hover:bg-slate-50 md:hidden"
                  aria-label="Open menu"
                >
                  <IconMenu className="h-5 w-5" />
                </button>
                {right}
                <form method="post" action="/logout">
                  <input type="hidden" name="_token" value={document.querySelector('meta[name=csrf-token]')?.content || ''} />
                  <button className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Logout</button>
                </form>
              </div>
            </div>
          </div>

          <div className="mx-auto max-w-6xl px-4 py-6">{children}</div>
        </main>
      </div>

      {/* Mobile drawer */}
      {mobileOpen ? (
        <div className="fixed inset-0 z-50 md:hidden">
          <div className="absolute inset-0 bg-slate-900/40" onClick={() => setMobileOpen(false)} />
          <div className="absolute left-0 top-0 h-full w-80 max-w-[85%] bg-white shadow-xl">
            <div className="flex items-center justify-between border-b border-slate-200 px-4 py-4">
              <div className="flex items-center gap-3">
                <div className="h-10 w-10 overflow-hidden rounded-2xl border border-slate-200 bg-white grid place-items-center">
                  {logoPath ? <img src={logoPath} alt="logo" className="h-8 w-8 object-contain" /> : <div className="h-2 w-2 rounded-full bg-blue-600" />}
                </div>
                <div>
                  <div className="text-base font-extrabold tracking-tight text-slate-900">{portalName}</div>
                  <div className="text-xs text-slate-500">Signed in as {user?.name || 'User'}</div>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setMobileOpen(false)}
                className="rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
                aria-label="Close menu"
              >
                <IconX className="h-5 w-5" />
              </button>
            </div>

            <nav className="space-y-1 px-3 py-4">
              {nav.map((n) => (
                <div key={n.href} onClick={() => setMobileOpen(false)}>
                  <SidebarItem href={n.href} label={n.label} icon={n.icon} />
                </div>
              ))}
            </nav>
          </div>
        </div>
      ) : null}
    </div>
  );
}
