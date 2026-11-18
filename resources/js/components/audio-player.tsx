import { Loader2, Pause, Play, RotateCcw, TriangleAlert } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { NativeSelect } from '@/components/ui/native-select';
import { formatDuration } from '@/lib/format';

import { useT } from '@/lib/i18n';
type Props = {
    /** Recorded audio. When missing, `speakText` is read by the browser's own voice. */
    src: string | null;
    speakText?: string | null;
    speeds: number[];
    defaultSpeed?: number;
    /** Called every time the learner starts the audio (play or replay). */
    onStart?: () => void;
    onSpeedChange?: (speed: number) => void;
    /** Start playing a moment after the item appears (no click needed). */
    autoPlay?: boolean;
};

const speechSupported = () =>
    typeof window !== 'undefined' && 'speechSynthesis' in window;

/**
 * Play / pause, replay, speed, duration, loading and error states.
 * Keyboard: every control is a real button; Alt+P plays or pauses and Alt+R
 * replays from anywhere on the page (also while typing).
 */
export function AudioPlayer({
    src,
    speakText = null,
    speeds,
    defaultSpeed = 1,
    onStart,
    onSpeedChange,
    autoPlay = false,
}: Props) {
    const t = useT();
    const audioRef = useRef<HTMLAudioElement>(null);
    const [speed, setSpeed] = useState(
        speeds.includes(defaultSpeed) ? defaultSpeed : 1,
    );
    const [playing, setPlaying] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [duration, setDuration] = useState<number | null>(null);

    const usingVoice = src === null;
    const canSpeak = usingVoice && !!speakText && speechSupported();

    const speak = useCallback(() => {
        if (!speakText || !speechSupported()) {
            return;
        }

        window.speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(speakText);
        utterance.lang = 'en-US';
        utterance.rate = speed;
        utterance.onstart = () => setPlaying(true);
        utterance.onend = () => setPlaying(false);
        utterance.onerror = () => setPlaying(false);

        window.speechSynthesis.speak(utterance);
    }, [speakText, speed]);

    const start = useCallback(
        (fromTheTop: boolean, silent = false) => {
            setError(null);
            onStart?.();

            if (usingVoice) {
                speak();

                return;
            }

            const audio = audioRef.current;

            if (!audio) {
                return;
            }

            if (fromTheTop) {
                audio.currentTime = 0;
            }

            audio.playbackRate = speed;
            void audio.play().catch(() => {
                // A browser may refuse to start sound by itself; then the learner presses Play.
                if (!silent) {
                    setError(t('The audio could not be played. Try again.'));
                }

                setPlaying(false);
            });
        },
        [onStart, speak, speed, usingVoice],
    );

    const togglePlay = useCallback(() => {
        if (playing) {
            if (usingVoice) {
                window.speechSynthesis.cancel();
                setPlaying(false);
            } else {
                audioRef.current?.pause();
            }

            return;
        }

        start(false);
    }, [playing, start, usingVoice]);

    const startRef = useRef(start);

    useEffect(() => {
        startRef.current = start;
    });

    useEffect(() => {
        if (!autoPlay) {
            return;
        }

        const timer = window.setTimeout(
            () => startRef.current(true, true),
            500,
        );

        return () => window.clearTimeout(timer);
    }, [autoPlay]);

    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            if (!event.altKey) {
                return;
            }

            if (event.code === 'KeyP') {
                event.preventDefault();
                togglePlay();
            } else if (event.code === 'KeyR') {
                event.preventDefault();
                start(true);
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [start, togglePlay]);

    useEffect(() => {
        if (audioRef.current) {
            audioRef.current.playbackRate = speed;
        }
    }, [speed, src]);

    useEffect(() => {
        const audio = audioRef.current;

        return () => {
            audio?.pause();

            if (speechSupported()) {
                window.speechSynthesis.cancel();
            }
        };
    }, []);

    if (usingVoice && !canSpeak) {
        return (
            <p
                role="alert"
                className="flex items-center gap-2 rounded-lg border border-warning/40 bg-warning/15 p-3 text-sm"
            >
                <TriangleAlert className="size-4 shrink-0" />
                {t(
                    'There is no recording for this item and your browser cannot read it aloud.',
                )}
            </p>
        );
    }

    return (
        <div className="flex flex-col items-center gap-3">
            <Button
                type="button"
                onClick={togglePlay}
                disabled={loading && !playing}
                aria-keyshortcuts="Alt+P"
                aria-label={playing ? 'Pause' : 'Play'}
                className="size-20 rounded-full shadow-md"
            >
                {loading && !playing ? (
                    <Loader2 className="size-8 animate-spin" />
                ) : playing ? (
                    <Pause className="size-8 fill-current" />
                ) : (
                    <Play className="size-8 fill-current" />
                )}
            </Button>

            <div className="flex flex-wrap items-center justify-center gap-2">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => start(true)}
                    aria-keyshortcuts="Alt+R"
                >
                    <RotateCcw /> {t('Replay')}
                </Button>

                <div className="flex items-center gap-1.5">
                    <label
                        htmlFor="playback-speed"
                        className="text-xs text-muted-foreground"
                    >
                        {t('Speed')}
                    </label>
                    <NativeSelect
                        id="playback-speed"
                        className="h-8 w-20"
                        value={String(speed)}
                        onChange={(event) => {
                            const next = Number(event.target.value);
                            setSpeed(next);
                            onSpeedChange?.(next);
                        }}
                    >
                        {speeds.map((value) => (
                            <option key={value} value={value}>
                                {value}x
                            </option>
                        ))}
                    </NativeSelect>
                </div>

                <span className="text-xs text-muted-foreground tabular-nums">
                    {!usingVoice && (
                        <>
                            <span className="sr-only">
                                {t('Audio length')}:{' '}
                            </span>
                            {formatDuration(duration)}
                        </>
                    )}
                </span>
            </div>

            {usingVoice && (
                <p className="text-xs text-muted-foreground">
                    {t(
                        "No recording yet. Your device's voice reads this aloud.",
                    )}
                </p>
            )}

            {error && (
                <p role="alert" className="text-sm text-destructive">
                    {error}
                </p>
            )}

            {!usingVoice && (
                <audio
                    ref={audioRef}
                    src={src}
                    preload="auto"
                    onLoadStart={() => setLoading(true)}
                    onCanPlay={() => setLoading(false)}
                    onLoadedMetadata={(event) =>
                        setDuration(
                            Number.isFinite(event.currentTarget.duration)
                                ? Math.round(
                                      event.currentTarget.duration * 1000,
                                  )
                                : null,
                        )
                    }
                    onPlay={() => setPlaying(true)}
                    onPause={() => setPlaying(false)}
                    onEnded={() => setPlaying(false)}
                    onError={() => {
                        setLoading(false);
                        setError(t('The audio file could not be loaded.'));
                    }}
                />
            )}
        </div>
    );
}
