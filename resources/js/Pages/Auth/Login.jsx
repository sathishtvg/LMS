import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';

export default function Login({ needTenantCode = false, tenantHint = null }) {
  const { data, setData, post, processing, errors } = useForm({ tenant_code: '', identifier: '', password: '' });

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 px-4">
      <div className="w-full max-w-md rounded-2xl bg-white shadow p-8">
        <div className="text-sm font-extrabold text-blue-600">LMS</div>
        <h1 className="mt-1 text-2xl font-extrabold text-slate-900">Sign in</h1>
        <p className="text-slate-600 mt-1">Use email or phone and your password.</p>

        <div className="mt-6 space-y-4">
          <div>
            <label className="text-sm font-medium text-slate-700">Email or Phone</label>
            <input className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 focus:outline-none focus:ring"
              value={data.identifier} onChange={e=>setData('identifier', e.target.value)} />
            {errors.identifier && <div className="text-sm text-red-600 mt-1">{errors.identifier}</div>}
          </div>
          <div>
            <label className="text-sm font-medium text-slate-700">Password</label>
            <input type="password" className="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 focus:outline-none focus:ring"
              value={data.password} onChange={e=>setData('password', e.target.value)} />
            {errors.password && <div className="text-sm text-red-600 mt-1">{errors.password}</div>}
          </div>

          <button
            disabled={processing}
            onClick={()=>post('/login')}
            className="w-full rounded-xl bg-blue-600 text-white py-2 font-semibold hover:bg-blue-700 disabled:opacity-60">
            {processing ? 'Signing in…' : 'Sign in'}
          </button>

          <div className="text-xs text-slate-500">
            Demo: admin@example.com / Admin@12345
          </div>
        </div>
      </div>
    </div>
  );
}
