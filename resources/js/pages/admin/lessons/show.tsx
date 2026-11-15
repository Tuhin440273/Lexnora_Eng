import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Eye, EyeOff, Pencil, Plus } from 'lucide-react';
import CourseController from '@/actions/App/Modules/Admin/Http/Controllers/CourseController';
import DashboardController from '@/actions/App/Modules/Admin/Http/Controllers/DashboardController';
import LessonController from '@/actions/App/Modules/Admin/Http/Controllers/LessonController';
import { FormDialog } from '@/components/admin/form-dialog';
import { LessonForm } from '@/components/admin/lesson-form';
import { PageHeader } from '@/components/admin/page-header';
import { SentenceCard } from '@/components/admin/sentence-card';
import { SentenceForm } from '@/components/admin/sentence-form';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';
import { formatMinutes } from '@/lib/format';
import type { Lesson, Sentence } from '@/types';

type Props = {
    lesson: Lesson;
    chapter: { id: string; title: string };
    course: { id: string; title: string };
    sentences: Sentence[];
};

export default function LessonShow({
    lesson,
    chapter,
    course,
    sentences,
}: Props) {
    const canManage = useCan('content.manage');
    const canPublish = useCan('content.publish');
    const canManageAudio = useCan('audio.manage');

    return (
        <>
            <Head title={lesson.title} />
            <div className="flex flex-col gap-6 p-4">
                <div>
                    <Link
                        href={CourseController.show(course.id)}
                        className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" /> {course.title}
                    </Link>
                </div>

                <PageHeader
                    title={lesson.title}
                    description={
                        lesson.description ??
                        `${chapter.title} · ${formatMinutes(lesson.estimated_minutes)}`
                    }
                    actions={
                        <>
                            <StatusBadge published={lesson.is_published} />
                            {canPublish && (
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        router.patch(
                                            LessonController.togglePublish.url(
                                                lesson.id,
                                            ),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {lesson.is_published ? (
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
                                <FormDialog
                                    title="Edit lesson"
                                    trigger={
                                        <Button variant="outline">
                                            <Pencil /> Edit lesson
                                        </Button>
                                    }
                                >
                                    {(close) => (
                                        <LessonForm
                                            chapterId={lesson.chapter_id}
                                            lesson={lesson}
                                            onSaved={close}
                                        />
                                    )}
                                </FormDialog>
                            )}
                        </>
                    }
                />

                <section className="space-y-4" aria-labelledby="sentences">
                    <div className="flex items-center justify-between gap-3">
                        <h2 id="sentences" className="text-lg font-semibold">
                            Sentences{' '}
                            <span className="text-sm font-normal text-muted-foreground">
                                ({sentences.length})
                            </span>
                        </h2>
                        {canManage && (
                            <FormDialog
                                title="Add to this lesson"
                                description="Learners type what they hear, so write the text exactly as it is spoken."
                                trigger={
                                    <Button size="sm">
                                        <Plus /> Add sentence
                                    </Button>
                                }
                            >
                                {(close) => (
                                    <SentenceForm
                                        lessonId={lesson.id}
                                        onSaved={close}
                                    />
                                )}
                            </FormDialog>
                        )}
                    </div>

                    {sentences.length === 0 && (
                        <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                            This lesson has no sentences yet. A lesson needs at
                            least one sentence before it can be published.
                        </p>
                    )}

                    {sentences.map((sentence, index) => (
                        <SentenceCard
                            key={sentence.id}
                            sentence={sentence}
                            lessonId={lesson.id}
                            isFirst={index === 0}
                            isLast={index === sentences.length - 1}
                            canManage={canManage}
                            canManageAudio={canManageAudio}
                        />
                    ))}
                </section>
            </div>
        </>
    );
}

LessonShow.layout = {
    breadcrumbs: [
        { title: 'Content admin', href: DashboardController.index() },
        { title: 'Courses', href: CourseController.index() },
    ],
};
