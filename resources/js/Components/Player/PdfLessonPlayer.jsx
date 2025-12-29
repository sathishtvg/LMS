import React, { useEffect, useMemo, useState } from 'react';
import { Document, Page } from 'react-pdf';

export default function PdfLessonPlayer({
  accessUrl,
  initialVisited = [],
  initialLastPage = 1,
  onProgressPush,
  onBlocked,
}) {
  const [numPages, setNumPages] = useState(null);
  const [page, setPage] = useState(Math.max(1, Number(initialLastPage) || 1));
  const [scale, setScale] = useState(1.2);
  const [visited, setVisited] = useState(() => {
    const base = Array.isArray(initialVisited) && initialVisited.length ? initialVisited : [1];
    const n = new Set(base);
    const lp = Math.max(1, Number(initialLastPage) || 1);
    n.add(lp);
    return n;
  });

  const visitedCount = visited.size;
  const complete = useMemo(() => {
    if (!numPages) return false;
    return visitedCount >= numPages;
  }, [visitedCount, numPages]);

  // push progress when page/visited changes (debounced)
  useEffect(() => {
    if (!numPages || !onProgressPush) return;
    const t = setTimeout(async () => {
      try {
        await onProgressPush({
          pages_visited: Array.from(visited),
          total_pages: numPages,
          last_page: page,
        });
      } catch (e) {
        if (e?.status === 423 && onBlocked) onBlocked();
      }
    }, 800);
    return () => clearTimeout(t);
  }, [page, visitedCount, numPages]);

  function markVisited(p) {
    setVisited((prev) => {
      const n = new Set(prev);
      n.add(p);
      return n;
    });
  }

  function goTo(p) {
    const max = numPages || p;
    const next = Math.max(1, Math.min(max, p));
    setPage(next);
    markVisited(next);
  }

  if (!accessUrl) {
    return <div className="text-slate-600">No PDF/PPT asset attached.</div>;
  }

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="text-sm font-semibold text-slate-700">
          Page {page}/{numPages || '…'} • Visited {visitedCount}/{numPages || '…'}
        </div>

        <div className="flex items-center gap-2">
          <button className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold" onClick={() => setScale((s) => Math.max(0.8, Number((s - 0.1).toFixed(1))))}>-</button>
          <button className="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold" onClick={() => setScale((s) => Math.min(2.0, Number((s + 0.1).toFixed(1))))}>+</button>
          <div className={complete ? 'text-sm font-extrabold text-emerald-700' : 'text-sm font-extrabold text-slate-500'}>
            {complete ? 'All pages viewed' : 'View all pages'}
          </div>
          <a href={accessUrl} target="_blank" rel="noreferrer" className="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white">
            Open
          </a>
        </div>
      </div>

      <div className="rounded-2xl border border-slate-200 bg-white p-3">
        <Document
          file={accessUrl}
          onLoadSuccess={({ numPages }) => {
            setNumPages(numPages);
            // Ensure resume page is within bounds, then mark visited
            const start = Math.max(1, Math.min(numPages || 1, page || 1));
            setPage(start);
            markVisited(start);
          }}
          loading={<div className="text-slate-600">Loading PDF…</div>}
          error={<div className="text-red-600">Failed to load PDF.</div>}
        >
          <Page
            pageNumber={page}
            scale={scale}
            onRenderSuccess={() => markVisited(page)}
          />
        </Document>
      </div>

      <div className="flex items-center justify-between">
        <button
          onClick={() => goTo(page - 1)}
          disabled={page <= 1}
          className="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold disabled:opacity-50"
        >
          Prev
        </button>
        <button
          onClick={() => goTo(page + 1)}
          disabled={!!numPages && page >= numPages}
          className="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold disabled:opacity-50"
        >
          Next
        </button>
      </div>
    </div>
  );
}
