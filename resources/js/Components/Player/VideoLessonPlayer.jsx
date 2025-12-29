import React, { useEffect, useMemo, useRef, useState } from 'react';
import ReactPlayer from 'react-player';

export default function VideoLessonPlayer({
  accessUrl,
  minWatchPercent = 90,
  initialWatchedSec = 0,
  onProgressPush,
  onBlocked,
}) {
  const playerRef = useRef(null);
  const seekAppliedRef = useRef(false);

  const [duration, setDuration] = useState(0);
  const [watched, setWatched] = useState(Number(initialWatchedSec) || 0);
  const [playing, setPlaying] = useState(false);

  const percent = useMemo(() => {
    if (!duration || duration <= 0) return 0;
    return Math.min(100, Math.floor((watched / duration) * 100));
  }, [watched, duration]);

  const eligible = percent >= minWatchPercent;

  // Resume once when duration is known
  useEffect(() => {
    if (seekAppliedRef.current) return;
    if (!duration || duration <= 1) return;
    if (!initialWatchedSec || initialWatchedSec < 5) {
      seekAppliedRef.current = true;
      return;
    }
    try {
      playerRef.current?.seekTo(Number(initialWatchedSec), 'seconds');
    } catch {
      // ignore
    } finally {
      seekAppliedRef.current = true;
    }
  }, [duration, initialWatchedSec]);

  // Push progress every 10 seconds while playing
  useEffect(() => {
    if (!playing) return;
    if (!onProgressPush) return;

    const timer = setInterval(async () => {
      try {
        const watchedSec = Math.floor(watched || 0);
        const totalSec = Math.max(1, Math.floor(duration || 1));
        await onProgressPush({ watched_sec: watchedSec, total_sec: totalSec });
      } catch (e) {
        if (e?.status === 423 && onBlocked) onBlocked();
      }
    }, 10000);

    return () => clearInterval(timer);
  }, [playing, watched, duration, onProgressPush, onBlocked]);

  async function pushFinal() {
    if (!onProgressPush) return;
    try {
      const totalSec = Math.max(1, Math.floor(duration || 1));
      await onProgressPush({ watched_sec: totalSec, total_sec: totalSec });
    } catch (e) {
      if (e?.status === 423 && onBlocked) onBlocked();
    }
  }

  if (!accessUrl) {
    return <div className="text-slate-600">No video asset attached.</div>;
  }

  return (
    <div className="space-y-3">
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-black">
        <ReactPlayer
          ref={playerRef}
          url={accessUrl}
          controls
          playing={playing}
          onPlay={() => setPlaying(true)}
          onPause={() => setPlaying(false)}
          onDuration={(d) => setDuration(d)}
          onProgress={({ playedSeconds }) => setWatched(playedSeconds)}
          onEnded={async () => {
            setPlaying(false);
            await pushFinal();
          }}
          width="100%"
          height="480px"
        />
      </div>

      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="text-sm text-slate-600">
          Watched <span className="font-semibold text-slate-900">{percent}%</span> (Required {minWatchPercent}%)
        </div>
        <div className={eligible ? 'text-sm font-extrabold text-emerald-700' : 'text-sm font-extrabold text-slate-500'}>
          {eligible ? 'Eligible to complete' : 'Keep watching'}
        </div>
      </div>

      <div className="h-2 w-full rounded-full bg-slate-100">
        <div className="h-2 rounded-full bg-slate-900" style={{ width: `${percent}%` }} />
      </div>
    </div>
  );
}
