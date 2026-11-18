import { Link } from '@inertiajs/react';
import { Check, Star } from 'lucide-react';
import CatalogController from '@/actions/App/Modules/Courses/Http/Controllers/CatalogController';
import { useIsMobile } from '@/hooks/use-mobile';
import { formatMinutes, plural } from '@/lib/format';
import { starsFor } from '@/lib/stars';
import type { LessonStatus } from '@/lib/stars';
import { cn } from '@/lib/utils';

export type RoadLesson = {
    id: string;
    title: string;
    sentences_count: number;
    estimated_minutes: number;
    status: LessonStatus;
    best_accuracy: number | null;
};

const ROW_HEIGHT = 208; // px: stars, tile, title and details
const LINE_Y = 68; // px from the top of a row to the middle of the tiles
const LINE = 'bg-primary/20';
const CURVE = 'border-primary/20';

function chunk<T>(items: T[], size: number): T[][] {
    const rows: T[][] = [];

    for (let index = 0; index < items.length; index += size) {
        rows.push(items.slice(index, index + size));
    }

    return rows;
}

function Stars({ count }: { count: number }) {
    return (
        <div className="flex h-5 items-start gap-0.5" aria-hidden>
            {[0, 1, 2].map((index) => (
                <Star
                    key={index}
                    className={cn(
                        'size-4',
                        index === 1 ? '' : 'translate-y-1',
                        index < count
                            ? 'fill-star text-star'
                            : 'fill-transparent text-muted-foreground/40',
                    )}
                />
            ))}
        </div>
    );
}

/**
 * Lessons laid out along a winding road: left to right, then back right to
 * left on the next row. Each tile shows stars, and the next lesson to do is
 * marked START.
 */
export function LessonRoad({
    lessons,
    nextId,
    firstNumber = 1,
}: {
    lessons: RoadLesson[];
    nextId: string | undefined;
    /** The number shown on the first tile, so a course counts on across chapters. */
    firstNumber?: number;
}) {
    const columns = useIsMobile() ? 2 : 4;
    const rows = chunk(lessons, columns);

    return (
        <ol className="pt-12">
            {rows.map((row, rowIndex) => {
                const reversed = rowIndex % 2 === 1;
                const last = rowIndex === rows.length - 1;
                const half = 50 / columns;
                const lineStart = reversed
                    ? ((columns - row.length) / columns) * 100 + half
                    : half;
                const lineEnd = reversed
                    ? half
                    : 100 - ((row.length - 0.5) / columns) * 100;

                return (
                    <li key={rowIndex} className="list-none">
                        <ol
                            className={cn(
                                'relative flex',
                                reversed ? 'flex-row-reverse' : 'flex-row',
                            )}
                            style={{ height: ROW_HEIGHT }}
                        >
                            <span
                                aria-hidden
                                className={cn(
                                    'absolute h-1.5 rounded-full',
                                    LINE,
                                )}
                                style={{
                                    top: LINE_Y - 3,
                                    left: `${lineStart}%`,
                                    right: `${lineEnd}%`,
                                }}
                            />
                            {!last && (
                                <span
                                    aria-hidden
                                    className={cn(
                                        'absolute w-[80px] border-[6px]',
                                        CURVE,
                                        reversed
                                            ? 'rounded-l-[32px] border-r-0'
                                            : 'rounded-r-[32px] border-l-0',
                                    )}
                                    style={{
                                        top: LINE_Y - 3,
                                        height: ROW_HEIGHT + 6,
                                        ...(reversed
                                            ? { right: `${100 - half}%` }
                                            : { left: `${100 - half}%` }),
                                    }}
                                />
                            )}

                            {row.map((lesson, position) => {
                                const stars = starsFor(
                                    lesson.status,
                                    lesson.best_accuracy,
                                );
                                const done = lesson.status === 'completed';
                                const isNext = lesson.id === nextId;
                                const order =
                                    firstNumber + rowIndex * columns + position;

                                return (
                                    <li
                                        key={lesson.id}
                                        className="relative z-10 flex shrink-0 list-none flex-col items-center"
                                        style={{ width: `${100 / columns}%` }}
                                    >
                                        {isNext && (
                                            <span
                                                aria-hidden
                                                className="absolute -top-9 animate-bob rounded-full bg-primary px-3 py-1 text-xs font-bold tracking-wide text-primary-foreground shadow-md after:absolute after:top-full after:left-1/2 after:size-2 after:-translate-x-1/2 after:-translate-y-1 after:rotate-45 after:bg-primary"
                                            >
                                                START
                                            </span>
                                        )}
                                        <Stars count={stars} />
                                        <Link
                                            href={CatalogController.showLesson(
                                                lesson.id,
                                            )}
                                            aria-label={`${lesson.title}. ${
                                                done
                                                    ? `Completed, ${stars} of 3 stars`
                                                    : lesson.status ===
                                                        'in_progress'
                                                      ? 'In progress'
                                                      : isNext
                                                        ? 'Start here'
                                                        : 'Not started'
                                            }`}
                                            className={cn(
                                                'flex size-24 animate-pop items-center justify-center rounded-3xl border-2 bg-card shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                                                done
                                                    ? 'border-star/70 bg-[color-mix(in_oklab,var(--star)_14%,var(--card))]'
                                                    : isNext ||
                                                        lesson.status ===
                                                            'in_progress'
                                                      ? 'border-primary shadow-md ring-4 ring-primary/10'
                                                      : 'border-border',
                                            )}
                                            style={{
                                                animationDelay: `${order * 70}ms`,
                                            }}
                                        >
                                            {done ? (
                                                <Check
                                                    className="size-10 text-star"
                                                    strokeWidth={3}
                                                />
                                            ) : (
                                                <span className="font-heading text-4xl font-semibold text-primary">
                                                    {order}
                                                </span>
                                            )}
                                        </Link>
                                        <p className="mt-2 flex h-10 w-28 items-center justify-center text-center text-sm leading-tight font-medium">
                                            <span className="line-clamp-2">
                                                {lesson.title}
                                            </span>
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {plural(
                                                lesson.sentences_count,
                                                'sentence',
                                            )}{' '}
                                            ·{' '}
                                            {formatMinutes(
                                                lesson.estimated_minutes,
                                            )}
                                        </p>
                                    </li>
                                );
                            })}
                        </ol>
                    </li>
                );
            })}
        </ol>
    );
}
