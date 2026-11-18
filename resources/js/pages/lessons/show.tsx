import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Play } from 'lucide-react';
import CatalogController from '@/actions/App/Modules/Courses/Http/Controllers/CatalogController';
import PracticeController from '@/actions/App/Modules/Dictation/Http/Controllers/PracticeController';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate, formatPercent, MODE_INFO } from '@/lib/learn';
import { formatMinutes, plural } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';

import { useT } from '@/lib/i18n';
type Props = {
    lesson: {
        id: string;
        title: string;
        description: string | null;
        difficulty: number;
        estimated_minutes: number;
        sentences_count: number;
        is_published: boolean;
    };
    chapter: { id: string; title: string };
    course: { id: string; title: string };
    modes: {
        value: 'easy' | 'medium' | 'hard';
        label: string;
        hints: number;
        guide: boolean;
        items: number;
        resume: { done: number; total: number } | null;
    }[];
    progress: {
        status: string;
        best_accuracy: number | null;
        sessions_count: number;
    } | null;
    recent: {
        id: string;
        mode: string;
        accuracy: number;
        completed_at: string | null;
    }[];
};

export default function LessonShow({
    lesson,
    chapter,
    course,
    modes,
    progress,
    recent,
}: Props) {
    const t = useT();
    const start = (mode: string) =>
        router.post(PracticeController.start.url(lesson.id), { mode });

    return (
        <>
            <Head title={lesson.title} />
            <div className="mx-auto flex w-full max-w-4xl animate-rise flex-col gap-6 p-4 md:p-6">
                <Link
                    href={CatalogController.showCourse(course.id)}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" /> {course.title}
                </Link>

                <PageHeader
                    title={lesson.title}
                    description={lesson.description ?? chapter.title}
                    actions={
                        <>
                            {!lesson.is_published && (
                                <Badge variant="outline">
                                    {t('Draft preview')}
                                </Badge>
                            )}
                            <Badge variant="secondary">
                                {plural(lesson.sentences_count, 'sentence')}
                            </Badge>
                            <Badge variant="secondary">
                                {formatMinutes(lesson.estimated_minutes)}
                            </Badge>
                        </>
                    }
                />

                {progress && (
                    <p className="text-sm text-muted-foreground">
                        {progress.status === 'completed'
                            ? t('Completed')
                            : t('In progress')}
                        {progress.best_accuracy !== null &&
                            ` · ${t('best text match {percent}', { percent: formatPercent(progress.best_accuracy) })}`}
                        {` · ${t('practised {times}', { times: t.n(progress.sessions_count, 'time') })}`}
                    </p>
                )}

                <section aria-labelledby="modes" className="space-y-3">
                    <h2 id="modes" className="text-lg font-semibold">
                        {t('Choose how to practise')}
                    </h2>
                    <ul className="grid gap-3 md:grid-cols-3">
                        {modes.map((mode, index) => (
                            <li
                                key={mode.value}
                                className={cn(
                                    'flex animate-rise flex-col justify-between gap-4 rounded-2xl border border-t-4 bg-card p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg',
                                    mode.value === 'easy' && 'border-t-success',
                                    mode.value === 'medium' && 'border-t-star',
                                    mode.value === 'hard' && 'border-t-err',
                                )}
                                style={{
                                    animationDelay: `${(index + 1) * 90}ms`,
                                }}
                            >
                                <div className="space-y-1">
                                    <h3 className="text-xl font-semibold">
                                        {t(mode.label)}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {t(MODE_INFO[mode.value])}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {t.n(mode.items, 'item')}
                                        {mode.hints > 0 &&
                                            ` · ${t.n(mode.hints, 'hint')}`}
                                        {mode.guide &&
                                            ` · ${t('faint guide text')}`}
                                    </p>
                                </div>
                                <Button
                                    onClick={() => start(mode.value)}
                                    disabled={mode.items === 0}
                                >
                                    <Play />
                                    {mode.resume
                                        ? `${t('Resume')} (${mode.resume.done}/${mode.resume.total})`
                                        : t('Start')}
                                </Button>
                            </li>
                        ))}
                    </ul>
                </section>

                {recent.length > 0 && (
                    <section aria-labelledby="recent" className="space-y-3">
                        <h2 id="recent" className="text-lg font-semibold">
                            {t('Recent sessions')}
                        </h2>
                        <ul className="divide-y rounded-xl border bg-card">
                            {recent.map((session) => (
                                <li
                                    key={session.id}
                                    className="flex items-center justify-between gap-2 p-3 text-sm"
                                >
                                    <span className="capitalize">
                                        {t(session.mode)}
                                    </span>
                                    <span>
                                        {formatPercent(session.accuracy)}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {formatDate(session.completed_at)}
                                    </span>
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link
                                            href={PracticeController.summary(
                                                session.id,
                                            )}
                                        >
                                            {t('Open')}
                                        </Link>
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}

LessonShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Courses', href: CatalogController.index() },
    ],
};
