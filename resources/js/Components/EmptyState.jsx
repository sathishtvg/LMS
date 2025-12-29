import React from "react";

export default function EmptyState({ title = "Nothing here yet", description = "", action = null }) {
  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-6 text-center">
      <div className="text-sm font-extrabold text-slate-900">{title}</div>
      {description ? <div className="mt-1 text-xs text-slate-500">{description}</div> : null}
      {action ? <div className="mt-4 flex justify-center">{action}</div> : null}
    </div>
  );
}
