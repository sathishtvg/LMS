import React from 'react';

export default function ProgressBar({ value = 0 }) {
  const v = Math.max(0, Math.min(100, Number(value || 0)));
  return (
    <div className="h-2 w-full rounded-full bg-slate-100">
      <div className="h-2 rounded-full bg-slate-900" style={{ width: `${v}%` }} />
    </div>
  );
}
