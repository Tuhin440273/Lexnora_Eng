import { Head, Link } from '@inertiajs/react';
import AudioLibraryController from '@/actions/App/Modules/Admin/Http/Controllers/AudioLibraryController';
import CourseController from '@/actions/App/Modules/Admin/Http/Controllers/CourseController';
import DashboardController from '@/actions/App/Modules/Admin/Http/Controllers/DashboardController';
import { PageHeader } from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { useCan } from '@/hooks/use-can';

type Stats = {
    courses: number;
    published_courses: number;
    chapters: number;
    lessons: number;
    sentences: number;
    audio_files: number;
    missing_audio: number;
    learners: number;
};

type Tile = {
    label: string;
    value: number;
    hint?: string;
    href?: ReturnType<typeof CourseController.index>;
};

export default function AdminDashboard({ stats }: { stats: Stats }) {
    const canManageContent = useCan('content.manage');
    const canManageAudio = useCan('audio.manage');

    const tiles: Tile[] = [
        {
            label: 'Courses',
            value: stats.courses,
            hint: `${stats.published_courses} published`,
            href: CourseController.index(),
        },
        { label: 'Chapters', value: stats.chapters },
        { label: 'Lessons', value: stats.lessons },
        { label: 'Sentences', value: stats.sentences },
        {
            label: 'Audio files',
            value: stats.audio_files,
            href: AudioLibraryController.index(),
        },
        { label: 'Learners', value: stats.learners },
    ];

    const draftCourses = stats.courses - stats.published_courses;

    return (
        <>
            <Head title="Content admin" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Content admin"
                    description="Build courses, add sentences and upload their audio."
                    actions={
                        canManageContent && (
                            <Button asChild>
                                <Link href={CourseController.create()}>
                                    New course
                                </Link>
                            </Button>
                        )
                    }
                />

                <dl className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                    {tiles.map((tile) => {
                        const body = (
                            <>
                                <dt className="text-sm text-muted-foreground">
                                    {tile.label}
                                </dt>
                                <dd className="mt-1 text-3xl font-semibold tabular-nums">
                                    {tile.value}
                                </dd>
                                {tile.hint && (
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {tile.hint}
                                    </p>
                                )}
                            </>
                        );

                        return tile.href ? (
                            <Link
                                key={tile.label}
                                href={tile.href}
                                className="rounded-xl border bg-card p-4 transition-colors hover:border-primary/50"
                            >
                                {body}
                            </Link>
                        ) : (
                            <div
                                key={tile.label}
                                className="rounded-xl border bg-card p-4"
                            >
                                {body}
                            </div>
                        );
                    })}
                </dl>

                <section aria-labelledby="attention" className="space-y-3">
                    <h2 id="attention" className="text-base font-semibold">
                        Needs your attention
                    </h2>
                    <ul className="divide-y rounded-xl border bg-card">
                        {stats.courses === 0 && (
                            <li className="flex flex-wrap items-center justify-between gap-2 p-4 text-sm">
                                <span>
                                    There are no courses yet. Create the first
                                    one to start adding lessons.
                                </span>
                                {canManageContent && (
                                    <Button size="sm" asChild>
                                        <Link href={CourseController.create()}>
                                            Create a course
                                        </Link>
                                    </Button>
                                )}
                            </li>
                        )}
                        {draftCourses > 0 && (
                            <li className="p-4 text-sm">
                                {draftCourses} draft{' '}
                                {draftCourses === 1
                                    ? 'course is'
                                    : 'courses are'}{' '}
                                hidden from learners until published.
                            </li>
                        )}
                        {stats.missing_audio > 0 && (
                            <li className="flex flex-wrap items-center justify-between gap-2 p-4 text-sm">
                                <span>
                                    {stats.missing_audio} sentence
                                    {stats.missing_audio === 1 ? '' : 's'} still
                                    have no audio.
                                </span>
                                {canManageAudio && (
                                    <Button size="sm" variant="outline" asChild>
                                        <Link
                                            href={AudioLibraryController.index({
                                                query: { view: 'missing' },
                                            })}
                                        >
                                            See which ones
                                        </Link>
                                    </Button>
                                )}
                            </li>
                        )}
                        {stats.courses > 0 &&
                            draftCourses === 0 &&
                            stats.missing_audio === 0 && (
                                <li className="p-4 text-sm text-muted-foreground">
                                    Everything is published and every sentence
                                    has audio.
                                </li>
                            )}
                    </ul>
                </section>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        { title: 'Content admin', href: DashboardController.index() },
    ],
};
