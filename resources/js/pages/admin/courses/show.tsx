import { Head, Link, router } from '@inertiajs/react';
import { Eye, EyeOff, Pencil, Plus, Trash2 } from 'lucide-react';
import ChapterController from '@/actions/App/Modules/Admin/Http/Controllers/ChapterController';
import CourseController from '@/actions/App/Modules/Admin/Http/Controllers/CourseController';
import DashboardController from '@/actions/App/Modules/Admin/Http/Controllers/DashboardController';
import LessonController from '@/actions/App/Modules/Admin/Http/Controllers/LessonController';
import { ChapterForm } from '@/components/admin/chapter-form';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { FormDialog } from '@/components/admin/form-dialog';
import { LessonForm } from '@/components/admin/lesson-form';
import { MoveButtons } from '@/components/admin/move-buttons';
import { PageHeader } from '@/components/admin/page-header';
import { StatusBadge } from '@/components/admin/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { formatMinutes, plural } from '@/lib/format';
import type { Chapter, Course } from '@/types';

export default function CourseShow({ course }: { course: Course }) {
    const canManage = useCan('content.manage');
    const canPublish = useCan('content.publish');

    const chapters: Chapter[] = course.chapters ?? [];
    const lessonCount = chapters.reduce(
        (total, chapter) => total + (chapter.lessons?.length ?? 0),
        0,
    );

    const patch = (url: string) =>
        router.patch(url, {}, { preserveScroll: true });

    return (
        <>
            <Head title={course.title} />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title={course.title}
                    description={course.description ?? undefined}
                    actions={
                        <>
                            <StatusBadge published={course.is_published} />
                            {canPublish && (
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        patch(
                                            CourseController.togglePublish.url(
                                                course.id,
                                            ),
                                        )
                                    }
                                >
                                    {course.is_published ? (
                                        <>
                                            <EyeOff /> Unpublish
                                        </>
                                    ) : (
                                        <>
                                            <Eye /> Publish
                                        </>
                                    )}
                                </Button>
                            )}
                            {canManage && (
                                <Button variant="outline" asChild>
                                    <Link
                                        href={CourseController.edit(course.id)}
                                    >
                                        <Pencil /> Edit course
                                    </Link>
                                </Button>
                            )}
                        </>
                    }
                />

                <dl className="flex flex-wrap gap-x-8 gap-y-2 text-sm">
                    <div>
                        <dt className="text-muted-foreground">Level</dt>
                        <dd className="font-medium">{course.cefr_label}</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Category</dt>
                        <dd className="font-medium">
                            {course.category?.name ?? 'None'}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Difficulty</dt>
                        <dd className="font-medium">{course.difficulty} / 5</dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Content</dt>
                        <dd className="font-medium">
                            {plural(chapters.length, 'chapter')},{' '}
                            {plural(lessonCount, 'lesson')}
                        </dd>
                    </div>
                </dl>

                <section className="space-y-4" aria-labelledby="chapters">
                    <div className="flex items-center justify-between gap-3">
                        <h2 id="chapters" className="text-lg font-semibold">
                            Chapters
                        </h2>
                        {canManage && (
                            <FormDialog
                                title="Add chapter"
                                trigger={
                                    <Button size="sm">
                                        <Plus /> Add chapter
                                    </Button>
                                }
                            >
                                {(close) => (
                                    <ChapterForm
                                        courseId={course.id}
                                        onSaved={close}
                                    />
                                )}
                            </FormDialog>
                        )}
                    </div>

                    {chapters.length === 0 && (
                        <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                            This course has no chapters yet. Add a chapter, then
                            add lessons to it.
                        </p>
                    )}

                    {chapters.map((chapter, chapterIndex) => {
                        const lessons = chapter.lessons ?? [];

                        return (
                            <article
                                key={chapter.id}
                                className="rounded-xl border bg-card"
                            >
                                <header className="flex flex-wrap items-center justify-between gap-2 border-b p-3">
                                    <div className="flex min-w-0 items-center gap-2">
                                        {canManage && (
                                            <MoveButtons
                                                url={ChapterController.move.url(
                                                    chapter.id,
                                                )}
                                                label={chapter.title}
                                                isFirst={chapterIndex === 0}
                                                isLast={
                                                    chapterIndex ===
                                                    chapters.length - 1
                                                }
                                            />
                                        )}
                                        <div className="min-w-0">
                                            <h3 className="truncate font-medium">
                                                {chapter.title}
                                            </h3>
                                            {chapter.description && (
                                                <p className="line-clamp-1 text-xs text-muted-foreground">
                                                    {chapter.description}
                                                </p>
                                            )}
                                        </div>
                                        <StatusBadge
                                            published={chapter.is_published}
                                        />
                                    </div>
                                    <div className="flex items-center gap-1">
                                        {canPublish && (
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={
                                                    chapter.is_published
                                                        ? `Unpublish ${chapter.title}`
                                                        : `Publish ${chapter.title}`
                                                }
                                                onClick={() =>
                                                    patch(
                                                        ChapterController.togglePublish.url(
                                                            chapter.id,
                                                        ),
                                                    )
                                                }
                                            >
                                                {chapter.is_published ? (
                                                    <EyeOff />
                                                ) : (
                                                    <Eye />
                                                )}
                                            </Button>
                                        )}
                                        {canManage && (
                                            <>
                                                <FormDialog
                                                    title="Edit chapter"
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label={`Edit ${chapter.title}`}
                                                        >
                                                            <Pencil />
                                                        </Button>
                                                    }
                                                >
                                                    {(close) => (
                                                        <ChapterForm
                                                            courseId={course.id}
                                                            chapter={chapter}
                                                            onSaved={close}
                                                        />
                                                    )}
                                                </FormDialog>
                                                <ConfirmDialog
                                                    title={`Delete "${chapter.title}"?`}
                                                    description="The chapter and all of its lessons and sentences will be removed."
                                                    onConfirm={() =>
                                                        router.delete(
                                                            ChapterController.destroy.url(
                                                                chapter.id,
                                                            ),
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                    trigger={
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label={`Delete ${chapter.title}`}
                                                        >
                                                            <Trash2 />
                                                        </Button>
                                                    }
                                                />
                                            </>
                                        )}
                                    </div>
                                </header>

                                <ul className="divide-y">
                                    {lessons.length === 0 && (
                                        <li className="p-4 text-sm text-muted-foreground">
                                            No lessons in this chapter yet.
                                        </li>
                                    )}
                                    {lessons.map((lesson, lessonIndex) => (
                                        <li
                                            key={lesson.id}
                                            className="flex flex-wrap items-center justify-between gap-2 p-3 pl-4"
                                        >
                                            <div className="flex min-w-0 items-center gap-2">
                                                {canManage && (
                                                    <MoveButtons
                                                        url={LessonController.move.url(
                                                            lesson.id,
                                                        )}
                                                        label={lesson.title}
                                                        isFirst={
                                                            lessonIndex === 0
                                                        }
                                                        isLast={
                                                            lessonIndex ===
                                                            lessons.length - 1
                                                        }
                                                    />
                                                )}
                                                <div className="min-w-0">
                                                    <Link
                                                        href={LessonController.show(
                                                            lesson.id,
                                                        )}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {lesson.title}
                                                    </Link>
                                                    <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                        <span>
                                                            {lesson.sentences_count ??
                                                                0}{' '}
                                                            sentences
                                                        </span>
                                                        <span>
                                                            {formatMinutes(
                                                                lesson.estimated_minutes,
                                                            )}
                                                        </span>
                                                        <Badge variant="secondary">
                                                            Difficulty{' '}
                                                            {lesson.difficulty}
                                                        </Badge>
                                                    </div>
                                                </div>
                                                <StatusBadge
                                                    published={
                                                        lesson.is_published
                                                    }
                                                />
                                            </div>
                                            <div className="flex items-center gap-1">
                                                {canPublish && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={
                                                            lesson.is_published
                                                                ? `Unpublish ${lesson.title}`
                                                                : `Publish ${lesson.title}`
                                                        }
                                                        onClick={() =>
                                                            patch(
                                                                LessonController.togglePublish.url(
                                                                    lesson.id,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        {lesson.is_published ? (
                                                            <EyeOff />
                                                        ) : (
                                                            <Eye />
                                                        )}
                                                    </Button>
                                                )}
                                                {canManage && (
                                                    <>
                                                        <FormDialog
                                                            title="Edit lesson"
                                                            trigger={
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    aria-label={`Edit ${lesson.title}`}
                                                                >
                                                                    <Pencil />
                                                                </Button>
                                                            }
                                                        >
                                                            {(close) => (
                                                                <LessonForm
                                                                    chapterId={
                                                                        chapter.id
                                                                    }
                                                                    lesson={
                                                                        lesson
                                                                    }
                                                                    onSaved={
                                                                        close
                                                                    }
                                                                />
                                                            )}
                                                        </FormDialog>
                                                        <ConfirmDialog
                                                            title={`Delete "${lesson.title}"?`}
                                                            description="The lesson and all of its sentences will be removed."
                                                            onConfirm={() =>
                                                                router.delete(
                                                                    LessonController.destroy.url(
                                                                        lesson.id,
                                                                    ),
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                            trigger={
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    aria-label={`Delete ${lesson.title}`}
                                                                >
                                                                    <Trash2 />
                                                                </Button>
                                                            }
                                                        />
                                                    </>
                                                )}
                                            </div>
                                        </li>
                                    ))}
                                </ul>

                                {canManage && (
                                    <footer className="border-t p-3">
                                        <FormDialog
                                            title="Add lesson"
                                            description={`Add a lesson to "${chapter.title}".`}
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    <Plus /> Add lesson
                                                </Button>
                                            }
                                        >
                                            {(close) => (
                                                <LessonForm
                                                    chapterId={chapter.id}
                                                    onSaved={close}
                                                />
                                            )}
                                        </FormDialog>
                                    </footer>
                                )}
                            </article>
                        );
                    })}
                </section>
            </div>
        </>
    );
}

CourseShow.layout = {
    breadcrumbs: [
        { title: 'Content admin', href: DashboardController.index() },
        { title: 'Courses', href: CourseController.index() },
    ],
};
