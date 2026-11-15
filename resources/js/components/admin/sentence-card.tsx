import { router } from '@inertiajs/react';
import { Pencil, Plus, Star, Trash2 } from 'lucide-react';
import SentenceAudioController from '@/actions/App/Modules/Admin/Http/Controllers/SentenceAudioController';
import SentenceController from '@/actions/App/Modules/Admin/Http/Controllers/SentenceController';
import { AudioUploader } from '@/components/admin/audio-uploader';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { FormDialog } from '@/components/admin/form-dialog';
import { MoveButtons } from '@/components/admin/move-buttons';
import { SentenceForm } from '@/components/admin/sentence-form';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatBytes, formatDuration } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Sentence } from '@/types';

type Props = {
    sentence: Sentence;
    lessonId: string;
    isFirst: boolean;
    isLast: boolean;
    canManage: boolean;
    canManageAudio: boolean;
    isChunk?: boolean;
};

export function SentenceCard({
    sentence,
    lessonId,
    isFirst,
    isLast,
    canManage,
    canManageAudio,
    isChunk = false,
}: Props) {
    const audio = sentence.audio ?? [];
    const children = sentence.children ?? [];

    return (
        <div
            className={cn(
                'rounded-xl border bg-card',
                isChunk && 'bg-muted/30 shadow-none',
            )}
        >
            <div className="flex flex-wrap items-start justify-between gap-3 p-4">
                <div className="min-w-0 flex-1 space-y-1">
                    <div className="flex items-center gap-2">
                        <Badge variant="secondary" className="capitalize">
                            {sentence.content_type}
                        </Badge>
                        {audio.length === 0 && (
                            <Badge
                                variant="outline"
                                className="border-warning/40 bg-warning/15 text-warning-foreground dark:text-warning"
                            >
                                No audio yet
                            </Badge>
                        )}
                    </div>
                    <p className="text-base font-medium">
                        {sentence.english_text}
                    </p>
                    {sentence.bengali_text && (
                        <p lang="bn" className="text-sm text-muted-foreground">
                            {sentence.bengali_text}
                        </p>
                    )}
                    {sentence.explanation && (
                        <p className="text-xs text-muted-foreground italic">
                            {sentence.explanation}
                        </p>
                    )}
                </div>

                {canManage && (
                    <div className="flex items-center gap-1">
                        <MoveButtons
                            url={SentenceController.move.url(sentence.id)}
                            label={sentence.english_text}
                            isFirst={isFirst}
                            isLast={isLast}
                        />
                        <FormDialog
                            title="Edit"
                            trigger={
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`Edit ${sentence.english_text}`}
                                >
                                    <Pencil />
                                </Button>
                            }
                        >
                            {(close) => (
                                <SentenceForm
                                    lessonId={lessonId}
                                    sentence={sentence}
                                    onSaved={close}
                                />
                            )}
                        </FormDialog>
                        <ConfirmDialog
                            title="Delete this item?"
                            description={
                                isChunk
                                    ? `"${sentence.english_text}" will be removed.`
                                    : `"${sentence.english_text}" and its words and phrases will be removed.`
                            }
                            onConfirm={() =>
                                router.delete(
                                    SentenceController.destroy.url(sentence.id),
                                    { preserveScroll: true },
                                )
                            }
                            trigger={
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`Delete ${sentence.english_text}`}
                                >
                                    <Trash2 />
                                </Button>
                            }
                        />
                    </div>
                )}
            </div>

            {(audio.length > 0 || canManageAudio) && (
                <div className="space-y-3 border-t px-4 py-3">
                    {audio.map((file) => (
                        <div
                            key={file.id}
                            className="flex flex-wrap items-center gap-3"
                        >
                            <audio
                                controls
                                preload="none"
                                src={file.url}
                                className="h-9 max-w-full"
                            />
                            <div className="min-w-0 text-xs text-muted-foreground">
                                <div className="max-w-48 truncate">
                                    {file.original_name ?? 'audio'}
                                </div>
                                <div>
                                    {formatDuration(file.duration_ms)} ·{' '}
                                    {formatBytes(file.size_bytes)}
                                </div>
                            </div>
                            {file.is_primary ? (
                                <Badge className="gap-1">
                                    <Star /> Primary
                                </Badge>
                            ) : (
                                canManageAudio && (
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            router.patch(
                                                SentenceAudioController.makePrimary.url(
                                                    file.id,
                                                ),
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        Make primary
                                    </Button>
                                )
                            )}
                            {canManageAudio && (
                                <ConfirmDialog
                                    title="Delete this audio file?"
                                    description="The file is removed from storage and learners will no longer hear it."
                                    onConfirm={() =>
                                        router.delete(
                                            SentenceAudioController.destroy.url(
                                                file.id,
                                            ),
                                            { preserveScroll: true },
                                        )
                                    }
                                    trigger={
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-8"
                                            aria-label={`Delete audio ${file.original_name ?? ''}`}
                                        >
                                            <Trash2 />
                                        </Button>
                                    }
                                />
                            )}
                        </div>
                    ))}
                    {canManageAudio && (
                        <AudioUploader
                            sentenceId={sentence.id}
                            label={sentence.english_text}
                        />
                    )}
                </div>
            )}

            {!isChunk && (children.length > 0 || canManage) && (
                <div className="space-y-3 border-t bg-muted/20 p-4">
                    <div className="flex items-center justify-between gap-2">
                        <h4 className="text-sm font-medium">
                            Words and phrases
                        </h4>
                        {canManage && (
                            <FormDialog
                                title="Add a word or phrase"
                                description="Used by the Easy and Medium practice modes. Each one can have its own audio."
                                trigger={
                                    <Button size="sm" variant="ghost">
                                        <Plus /> Add
                                    </Button>
                                }
                            >
                                {(close) => (
                                    <SentenceForm
                                        lessonId={lessonId}
                                        parentId={sentence.id}
                                        onSaved={close}
                                    />
                                )}
                            </FormDialog>
                        )}
                    </div>
                    {children.length === 0 && (
                        <p className="text-xs text-muted-foreground">
                            None yet. Without them, Easy and Medium mode fall
                            back to the full sentence.
                        </p>
                    )}
                    {children.map((child, index) => (
                        <SentenceCard
                            key={child.id}
                            sentence={child}
                            lessonId={lessonId}
                            isFirst={index === 0}
                            isLast={index === children.length - 1}
                            canManage={canManage}
                            canManageAudio={canManageAudio}
                            isChunk
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
