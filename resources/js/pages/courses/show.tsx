import { Head } from '@inertiajs/react';
import CatalogController from '@/actions/App/Modules/Courses/Http/Controllers/CatalogController';
import { LessonRoad } from '@/components/lesson-road';
import type { RoadLesson } from '@/components/lesson-road';
import { Badge } from '@/components/ui/badge';
import { plural } from '@/lib/format';
import { levelAccent } from '@/lib/levels';
import { STAR_RULE } from '@/lib/stars';

type Props = {
    course: {
        id: string;
        title: string;
        description: string | null;
        cefr: string;
        cefr_label: string;
        category: string | null;
        is_published: boolean;
        thumbnail_url: string | null;
        chapters: {
            id: string;
            title: string;
            description: string | null;
            lessons: (RoadLesson & {
                description: string | null;
                difficulty: number;
            })[];
        }[];
    };
};

export default function CourseShow({ course }: Props) {
    const lessons = course.chapters.flatMap((chapter) => chapter.lessons);
    const finished = lessons.filter(
        (lesson) => lesson.status === 'completed',
    ).length;
    const nextId = lessons.find((lesson) => lesson.status !== 'completed')?.id;
    const percent = Math.round((finished / Math.max(1, lessons.length)) * 100);

    return (
        <>
            <Head title={course.title} />
            <div
                data-level={levelAccent(course.cefr)}
                className="mx-auto flex w-full max-w-4xl flex-col gap-10 p-4 md:p-6"
            >
                <header className="animate-rise space-y-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge className="border-transparent bg-level text-level-foreground">
                            {course.cefr_label}
                        </Badge>
                        {course.category && (
                            <Badge variant="outline">{course.category}</Badge>
                        )}
                        {!course.is_published && (
                            <Badge variant="outline">Draft preview</Badge>
                        )}
                    </div>
                    <h1 className="text-3xl font-semibold tracking-tight md:text-4xl">
                        {course.title}
                    </h1>
                    {course.description && (
                        <p className="max-w-prose text-lg text-muted-foreground">
                            {course.description}
                        </p>
                    )}
                    <div className="flex max-w-sm items-center gap-3">
                        <div
                            role="progressbar"
                            aria-label="Course progress"
                            aria-valuemin={0}
                            aria-valuemax={100}
                            aria-valuenow={percent}
                            className="h-2.5 flex-1 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                className="h-full rounded-full bg-level transition-[width] duration-700 motion-reduce:transition-none"
                                style={{ width: `${percent}%` }}
                            />
                        </div>
                        <span className="text-sm text-muted-foreground tabular-nums">
                            {finished} of {plural(lessons.length, 'lesson')}
                        </span>
                    </div>
                    <p className="text-xs text-muted-foreground">{STAR_RULE}</p>
                </header>

                {course.chapters.length === 0 && (
                    <p className="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        This course has no lessons yet.
                    </p>
                )}

                {course.chapters.map((chapter, index) => {
                    const before = course.chapters
                        .slice(0, index)
                        .reduce((sum, item) => sum + item.lessons.length, 0);

                    return (
                        <section
                            key={chapter.id}
                            aria-labelledby={`chapter-${chapter.id}`}
                            className="animate-rise"
                            style={{ animationDelay: `${(index + 1) * 120}ms` }}
                        >
                            <h2
                                id={`chapter-${chapter.id}`}
                                className="text-2xl font-semibold"
                            >
                                {chapter.title}
                            </h2>
                            {chapter.description && (
                                <p className="text-muted-foreground">
                                    {chapter.description}
                                </p>
                            )}
                            {chapter.lessons.length === 0 ? (
                                <p className="mt-4 text-sm text-muted-foreground">
                                    No lessons in this chapter yet.
                                </p>
                            ) : (
                                <LessonRoad
                                    lessons={chapter.lessons}
                                    nextId={nextId}
                                    firstNumber={before + 1}
                                />
                            )}
                        </section>
                    );
                })}
            </div>
        </>
    );
}

CourseShow.layout = {
    breadcrumbs: [{ title: 'Courses', href: CatalogController.index() }],
};
