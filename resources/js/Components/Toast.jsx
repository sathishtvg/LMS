import React from "react";

export default function Toast({ message, type = "info", onClose }) {
  if (!message) return null;
  const styles = {
    info: "bg-blue-600 text-white",
    success: "bg-emerald-600 text-white",
    error: "bg-rose-600 text-white",
  }[type] || "bg-blue-600 text-white";

  return (
    <div className="fixed right-4 top-4 z-50">
      <div className={`max-w-sm rounded-2xl px-4 py-3 text-sm font-semibold shadow-lg ${styles}`}>
        <div className="flex items-start justify-between gap-3">
          <div className="leading-snug">{message}</div>
          <button onClick={onClose} className="rounded-lg px-2 py-1 text-xs font-bold opacity-90 hover:opacity-100">
            ✕
          </button>
        </div>
      </div>
    </div>
  );
}
