import { Form, Head, router } from '@inertiajs/react';
import { NotebookPen, Pencil, Plus, SearchX, Trash2 } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { useState } from 'react';
import type { FormEvent } from 'react';
import NoteController from '@/actions/App/Modules/Learning/Http/Controllers/NoteController';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { FormDialog } from '@/components/admin/form-dialog';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/learn';
import { dashboard } from '@/routes';
import type { Paginated } from '@/types';

import { useT } from '@/lib/i18n';
type Note = {
    id: string;
    title: string | null;
    body: string;
    sentence: string | null;
    created_at: string;
};

function NoteForm({ note, onSaved }: { note?: Note; onSaved: () => void }) {
    const t = useT();
    const formProps = note
        ? NoteController.update.form(note.id)
        : NoteController.store.form();

    return (
        <Form
            {...formProps}
            options={{ preserveScroll: true }}
            onSuccess={onSaved}
            className="space-y-4"
        >
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="note-title">
                            {t('Title (optional)')}
                        </Label>
                        <Input
                            id="note-title"
                            name="title"
                            defaultValue={note?.title ?? ''}
                            maxLength={150}
                        />
                        <InputError message={errors.title} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="note-body">{t('Note')}</Label>
                        <Textarea
                            id="note-body"
                            name="body"
                            rows={5}
                            required
                            defaultValue={note?.body ?? ''}
                            aria-invalid={!!errors.body}
                        />
                        <InputError message={errors.body} />
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {note ? 'Save changes' : 'Save note'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

export default function NotesIndex({
    notes,
    search,
}: {
    notes: Paginated<Note>;
    search: string;
}) {
    const t = useT();
    const [value, setValue] = useState(search);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get(NoteController.index().url, value ? { search: value } : {}, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <>
            <Head title={t('Notes')} />
            <div className="mx-auto flex w-full max-w-4xl animate-rise flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={t('Personal notes')}
                    description={t(
                        'Write down what you want to remember. Notes added from a result screen stay linked to their sentence.',
                    )}
                    actions={
                        <FormDialog
                            title={t('New note')}
                            trigger={
                                <Button>
                                    <Plus /> {t('New note')}
                                </Button>
                            }
                        >
                            {(close) => <NoteForm onSaved={close} />}
                        </FormDialog>
                    }
                />

                <form role="search" onSubmit={submit} className="flex gap-3">
                    <label htmlFor="note-search" className="sr-only">
                        {t('Search notes')}
                    </label>
                    <Input
                        id="note-search"
                        type="search"
                        placeholder={t('Search notes')}
                        value={value}
                        onChange={(event) => setValue(event.target.value)}
                    />
                    <Button type="submit" variant="secondary">
                        {t('Search')}
                    </Button>
                </form>

                {notes.data.length === 0 ? (
                    search ? (
                        <EmptyState icon={SearchX} title={t('No note matches')}>
                            {t('Try another word.')}
                        </EmptyState>
                    ) : (
                        <EmptyState
                            icon={NotebookPen}
                            title={t('No notes yet')}
                        >
                            {t(
                                'Write down what you want to remember. You can also add a note right after an answer.',
                            )}
                        </EmptyState>
                    )
                ) : (
                    <ul className="grid gap-3 md:grid-cols-2">
                        {notes.data.map((note) => (
                            <li
                                key={note.id}
                                className="flex flex-col gap-2 rounded-xl border bg-card p-4"
                            >
                                {note.title && (
                                    <h2 className="font-semibold">
                                        {note.title}
                                    </h2>
                                )}
                                <p className="text-sm whitespace-pre-wrap">
                                    {note.body}
                                </p>
                                {note.sentence && (
                                    <p className="text-xs text-muted-foreground italic">
                                        {t('About:')} &ldquo;{note.sentence}
                                        &rdquo;
                                    </p>
                                )}
                                <div className="mt-auto flex items-center justify-between pt-2">
                                    <span className="text-xs text-muted-foreground">
                                        {formatDate(note.created_at)}
                                    </span>
                                    <div className="flex">
                                        <FormDialog
                                            title={t('Edit note')}
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={t('Edit note')}
                                                >
                                                    <Pencil />
                                                </Button>
                                            }
                                        >
                                            {(close) => (
                                                <NoteForm
                                                    note={note}
                                                    onSaved={close}
                                                />
                                            )}
                                        </FormDialog>
                                        <ConfirmDialog
                                            title={t('Delete this note?')}
                                            description={t(
                                                'This cannot be undone.',
                                            )}
                                            onConfirm={() =>
                                                router.delete(
                                                    NoteController.destroy.url(
                                                        note.id,
                                                    ),
                                                    { preserveScroll: true },
                                                )
                                            }
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={t(
                                                        'Delete note',
                                                    )}
                                                >
                                                    <Trash2 />
                                                </Button>
                                            }
                                        />
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={notes.links} total={notes.total} />
            </div>
        </>
    );
}

NotesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Notes', href: NoteController.index() },
    ],
};
