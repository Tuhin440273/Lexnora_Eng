import { Head, Link } from '@inertiajs/react';
import { CircleCheck, Circle, CircleDot } from 'lucide-react';
import CatalogController from '@/actions/App/Modules/Courses/Http/Controllers/CatalogController';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { formatMinutes } from '@/lib/format';
import { formatPercent } from '@/lib/learn';

type LessonRow = {
    id: string;
    title: string;
    description: string | null;
    difficulty: number;
    estimated_minutes: number;
    sentences_count: number;
    status: 'not_started' | 'in_progress' | 'completed';
    best_accuracy: number | null;
};

type Props = {
    course: {
        id: string;
        title: string;
        description: string | null;
        cefr_label: string;
        category: string | null;
        is_published: boolean;
        thumbnail_url: string | null;
        chapters: {
            id: string;
            title: string;
            description: string | null;
            lessons: LessonRow[];
        }[];
    };
};

const STATUS = {
    not_started: { icon: Circle, label: 'Not started' },
    in_progress: { icon: CircleDot, label: 'In progress' },
    completed: { icon: CircleCheck, label: 'Completed' },
} as const;

export default function CourseShow({ course }: Props) {
    return (
        <>
            <Head title={course.title} />
            <div className="flex max-w-4xl flex-col gap-6 p-4">
                <PageHeader
                    title={course.title}
                    description={course.description ?? undefined}
                    actions={
                        <>
                            {!course.is_published && (
                                <Badge variant="outline">Draft preview</Badge>
                            )}
                            <Badge variant="secondary">
                                {course.cefr_label}
                            </Badge>
                            {course.category && (
                                <Badge variant="outline">
                                    {course.category}
                                </Badge>
                            )}
                        </>
                    }
                />

                {course.chapters.length === 0 && (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        This course has no lessons yet.
                    </p>
                )}

                {course.chapters.map((chapter) => (
                    <section
                        key={chapter.id}
                        aria-labelledby={`chapter-${chapter.id}`}
                        className="rounded-xl border bg-card"
                    >
                        <header className="border-b p-4">
                            <h2
                                id={`chapter-${chapter.id}`}
                                className="font-semibold"
                            >
                                {chapter.title}
                            </h2>
                            {chapter.description && (
                                <p className="text-sm text-muted-foreground">
                                    {chapter.description}
                                </p>
                            )}
                        </header>
                        <ul className="divide-y">
                            {chapter.lessons.map((lesson) => {
                                const status = STATUS[lesson.status];

                                return (
                                    <li key={lesson.id}>
                                        <Link
                                            href={CatalogController.showLesson(
                                                lesson.id,
                                            )}
                                            className="flex flex-wrap items-center justify-between gap-3 p-4 hover:bg-accent/50"
                                        >
                                            <span className="flex items-center gap-3">
                                                <status.icon
                                                    className={
                                                        lesson.status ===
                                                        'completed'
                                                            ? 'size-5 text-success'
                                                            : 'size-5 text-muted-foreground'
                                                    }
                                                    aria-hidden
                                                />
                                                <span>
                                                    <span className="block font-medium">
                                                        {lesson.title}
                                                    </span>
                                                    <span className="block text-xs text-muted-foreground">
                                                        {lesson.sentences_count}{' '}
                                                        sentences ·{' '}
                                                        {formatMinutes(
                                                            lesson.estimated_minutes,
                                                        )}
                                                    </span>
                                                </span>
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {status.label}
                                                {lesson.best_accuracy !==
                                                    null &&
                                                    ` · best ${formatPercent(lesson.best_accuracy)}`}
                                            </span>
                                        </Link>
                                    </li>
                                );
                            })}
                            {chapter.lessons.length === 0 && (
                                <li className="p-4 text-sm text-muted-foreground">
                                    No lessons in this chapter yet.
                                </li>
                            )}
                        </ul>
                    </section>
                ))}
            </div>
        </>
    );
}

CourseShow.layout = {
    breadcrumbs: [{ title: 'Courses', href: CatalogController.index() }],
};
