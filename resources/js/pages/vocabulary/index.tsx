import { Form, Head, router } from '@inertiajs/react';
import {
    BookMarked,
    Check,
    Pencil,
    Plus,
    SearchX,
    Trash2,
    Undo2,
} from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { useState } from 'react';
import type { FormEvent } from 'react';
import SavedVocabularyController from '@/actions/App/Modules/Vocabulary/Http/Controllers/SavedVocabularyController';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { FormDialog } from '@/components/admin/form-dialog';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes';
import type { Paginated } from '@/types';

type Saved = {
    id: string;
    term: string;
    type: string;
    context_text: string | null;
    personal_meaning: string | null;
    notes: string | null;
    is_learned: boolean;
    meaning_en: string | null;
    meaning_bn: string | null;
    pronunciation: string | null;
};

function AddForm({ onSaved }: { onSaved: () => void }) {
    return (
        <Form
            {...SavedVocabularyController.store.form()}
            options={{ preserveScroll: true }}
            onSuccess={onSaved}
            className="space-y-4"
        >
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="term">Word or phrase</Label>
                        <Input
                            id="term"
                            name="term"
                            required
                            maxLength={150}
                            aria-invalid={!!errors.term}
                        />
                        <InputError message={errors.term} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="personal_meaning">
                            Your meaning (optional)
                        </Label>
                        <Input
                            id="personal_meaning"
                            name="personal_meaning"
                            lang="bn"
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="notes">Notes (optional)</Label>
                        <Textarea id="notes" name="notes" rows={2} />
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            Save word
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

function EditForm({ item, onSaved }: { item: Saved; onSaved: () => void }) {
    return (
        <Form
            {...SavedVocabularyController.update.form(item.id)}
            options={{ preserveScroll: true }}
            onSuccess={onSaved}
            className="space-y-4"
        >
            {({ errors, processing }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="edit-meaning">Your meaning</Label>
                        <Input
                            id="edit-meaning"
                            name="personal_meaning"
                            lang="bn"
                            defaultValue={item.personal_meaning ?? ''}
                        />
                        <InputError message={errors.personal_meaning} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="edit-notes">Notes</Label>
                        <Textarea
                            id="edit-notes"
                            name="notes"
                            rows={3}
                            defaultValue={item.notes ?? ''}
                        />
                        <InputError message={errors.notes} />
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            Save changes
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

export default function VocabularyIndex({
    items,
    filters,
    totals,
}: {
    items: Paginated<Saved>;
    filters: { search: string; status: string };
    totals: { all: number; learned: number };
}) {
    const [values, setValues] = useState(filters);

    const apply = (next: typeof filters) => {
        setValues(next);
        router.get(
            SavedVocabularyController.index().url,
            Object.fromEntries(
                Object.entries(next).filter(([, v]) => v !== ''),
            ),
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Vocabulary notebook" />
            <div className="mx-auto flex w-full max-w-4xl animate-rise flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Vocabulary notebook"
                    description={`${totals.all} saved, ${totals.learned} learned. Save words from the result screen after each answer.`}
                    actions={
                        <FormDialog
                            title="Save a word"
                            trigger={
                                <Button>
                                    <Plus /> Add word
                                </Button>
                            }
                        >
                            {(close) => <AddForm onSaved={close} />}
                        </FormDialog>
                    }
                />

                <form
                    role="search"
                    className="flex flex-wrap gap-3"
                    onSubmit={(event: FormEvent) => {
                        event.preventDefault();
                        apply(values);
                    }}
                >
                    <label htmlFor="vocab-search" className="sr-only">
                        Search
                    </label>
                    <Input
                        id="vocab-search"
                        type="search"
                        placeholder="Search words and meanings"
                        className="min-w-48 flex-1"
                        value={values.search}
                        onChange={(event) =>
                            setValues({ ...values, search: event.target.value })
                        }
                    />
                    <label htmlFor="vocab-status" className="sr-only">
                        Show
                    </label>
                    <NativeSelect
                        id="vocab-status"
                        className="w-40"
                        value={values.status}
                        onChange={(event) =>
                            apply({ ...values, status: event.target.value })
                        }
                    >
                        <option value="">All words</option>
                        <option value="learning">Still learning</option>
                        <option value="learned">Learned</option>
                    </NativeSelect>
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                {items.data.length === 0 ? (
                    filters.search || filters.status ? (
                        <EmptyState icon={SearchX} title="Nothing matches">
                            Try another search.
                        </EmptyState>
                    ) : (
                        <EmptyState
                            icon={BookMarked}
                            title="Your notebook is empty"
                        >
                            Save a word from a result screen, or add one
                            yourself.
                        </EmptyState>
                    )
                ) : (
                    <ul className="space-y-3">
                        {items.data.map((item) => (
                            <li
                                key={item.id}
                                className="space-y-2 rounded-xl border bg-card p-4"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="text-lg font-semibold">
                                            {item.term}
                                        </span>
                                        <Badge variant="secondary">
                                            {item.type}
                                        </Badge>
                                        {item.is_learned && (
                                            <Badge
                                                variant="outline"
                                                className="border-success/30 bg-success/10 text-success"
                                            >
                                                Learned
                                            </Badge>
                                        )}
                                        {item.pronunciation && (
                                            <span className="text-sm text-muted-foreground">
                                                {item.pronunciation}
                                            </span>
                                        )}
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                router.patch(
                                                    SavedVocabularyController.toggleLearned.url(
                                                        item.id,
                                                    ),
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            {item.is_learned ? (
                                                <>
                                                    <Undo2 /> Not learned
                                                </>
                                            ) : (
                                                <>
                                                    <Check /> Mark learned
                                                </>
                                            )}
                                        </Button>
                                        <FormDialog
                                            title={`Edit "${item.term}"`}
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Edit ${item.term}`}
                                                >
                                                    <Pencil />
                                                </Button>
                                            }
                                        >
                                            {(close) => (
                                                <EditForm
                                                    item={item}
                                                    onSaved={close}
                                                />
                                            )}
                                        </FormDialog>
                                        <ConfirmDialog
                                            title={`Remove "${item.term}"?`}
                                            description="It is removed from your notebook and from your review schedule."
                                            confirmLabel="Remove"
                                            onConfirm={() =>
                                                router.delete(
                                                    SavedVocabularyController.destroy.url(
                                                        item.id,
                                                    ),
                                                    { preserveScroll: true },
                                                )
                                            }
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Remove ${item.term}`}
                                                >
                                                    <Trash2 />
                                                </Button>
                                            }
                                        />
                                    </div>
                                </div>
                                {(item.personal_meaning ||
                                    item.meaning_en ||
                                    item.meaning_bn) && (
                                    <p className="text-sm">
                                        {item.personal_meaning && (
                                            <span
                                                lang="bn"
                                                className="mr-3 font-medium"
                                            >
                                                {item.personal_meaning}
                                            </span>
                                        )}
                                        {item.meaning_en && (
                                            <span className="mr-3">
                                                {item.meaning_en}
                                            </span>
                                        )}
                                        {item.meaning_bn && (
                                            <span
                                                lang="bn"
                                                className="text-muted-foreground"
                                            >
                                                {item.meaning_bn}
                                            </span>
                                        )}
                                    </p>
                                )}
                                {item.context_text && (
                                    <p className="text-sm text-muted-foreground italic">
                                        &ldquo;{item.context_text}&rdquo;
                                    </p>
                                )}
                                {item.notes && (
                                    <p className="text-sm">{item.notes}</p>
                                )}
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={items.links} total={items.total} />
            </div>
        </>
    );
}

VocabularyIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Vocabulary', href: SavedVocabularyController.index() },
    ],
};
