import { Head, Link, router } from '@inertiajs/react';
import { Star, Trash2 } from 'lucide-react';
import AudioLibraryController from '@/actions/App/Modules/Admin/Http/Controllers/AudioLibraryController';
import DashboardController from '@/actions/App/Modules/Admin/Http/Controllers/DashboardController';
import LessonController from '@/actions/App/Modules/Admin/Http/Controllers/LessonController';
import SentenceAudioController from '@/actions/App/Modules/Admin/Http/Controllers/SentenceAudioController';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatBytes, formatDuration } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ContentType, LessonSummary, Paginated } from '@/types';

type FileItem = {
    id: string;
    url: string;
    english_text: string;
    original_name: string | null;
    size_bytes: number | null;
    duration_ms: number | null;
    is_primary: boolean;
    lesson: LessonSummary;
};

type MissingItem = {
    id: string;
    english_text: string;
    content_type: ContentType;
    lesson: LessonSummary;
};

type Props =
    | { view: 'files'; items: Paginated<FileItem> }
    | { view: 'missing'; items: Paginated<MissingItem> };

function LessonLink({ lesson }: { lesson: LessonSummary }) {
    if (!lesson) {
        return <span className="text-muted-foreground">-</span>;
    }

    return (
        <Link
            href={LessonController.show(lesson.id)}
            className="hover:underline"
        >
            <span className="font-medium">{lesson.title}</span>
            {lesson.course_title && (
                <span className="block text-xs text-muted-foreground">
                    {lesson.course_title}
                </span>
            )}
        </Link>
    );
}

export default function AudioIndex(props: Props) {
    const tabs = [
        { key: 'files', label: 'Uploaded files' },
        { key: 'missing', label: 'Missing audio' },
    ] as const;

    return (
        <>
            <Head title="Audio" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Audio"
                    description="Review uploaded files, or find sentences that still need a recording. Upload audio from the lesson page."
                />

                <nav aria-label="Audio views" className="flex gap-1 border-b">
                    {tabs.map((tab) => (
                        <Link
                            key={tab.key}
                            href={AudioLibraryController.index({
                                query: { view: tab.key },
                            })}
                            aria-current={
                                props.view === tab.key ? 'page' : undefined
                            }
                            className={cn(
                                '-mb-px border-b-2 px-3 py-2 text-sm',
                                props.view === tab.key
                                    ? 'border-primary font-medium text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                        >
                            {tab.label}
                        </Link>
                    ))}
                </nav>

                {props.items.data.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        {props.view === 'files'
                            ? 'No audio has been uploaded yet.'
                            : 'Every sentence has audio.'}
                    </p>
                ) : props.view === 'files' ? (
                    <div className="rounded-xl border bg-card">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Sentence</TableHead>
                                    <TableHead>Lesson</TableHead>
                                    <TableHead>File</TableHead>
                                    <TableHead>Listen</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {props.items.data.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="max-w-xs">
                                            <span className="line-clamp-2">
                                                {item.english_text}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <LessonLink lesson={item.lesson} />
                                        </TableCell>
                                        <TableCell className="text-xs text-muted-foreground">
                                            <div className="flex items-center gap-1">
                                                <span className="max-w-40 truncate">
                                                    {item.original_name ??
                                                        'audio'}
                                                </span>
                                                {item.is_primary && (
                                                    <Badge className="gap-1">
                                                        <Star /> Primary
                                                    </Badge>
                                                )}
                                            </div>
                                            {formatDuration(item.duration_ms)} ·{' '}
                                            {formatBytes(item.size_bytes)}
                                        </TableCell>
                                        <TableCell>
                                            <audio
                                                controls
                                                preload="none"
                                                src={item.url}
                                                className="h-9 w-56"
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end">
                                                <ConfirmDialog
                                                    title="Delete this audio file?"
                                                    description="The file is removed from storage and learners will no longer hear it."
                                                    onConfirm={() =>
                                                        router.delete(
                                                            SentenceAudioController.destroy.url(
                                                                item.id,
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
                                                            aria-label={`Delete audio for ${item.english_text}`}
                                                        >
                                                            <Trash2 />
                                                        </Button>
                                                    }
                                                />
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                ) : (
                    <div className="rounded-xl border bg-card">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Sentence</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Lesson</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {props.items.data.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="max-w-md">
                                            <span className="line-clamp-2">
                                                {item.english_text}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="secondary"
                                                className="capitalize"
                                            >
                                                {item.content_type}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <LessonLink lesson={item.lesson} />
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end">
                                                {item.lesson && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={LessonController.show(
                                                                item.lesson.id,
                                                            )}
                                                        >
                                                            Open lesson
                                                        </Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <Pagination
                    links={props.items.links}
                    total={props.items.total}
                />
            </div>
        </>
    );
}

AudioIndex.layout = {
    breadcrumbs: [
        { title: 'Content admin', href: DashboardController.index() },
        { title: 'Audio', href: AudioLibraryController.index() },
    ],
};
