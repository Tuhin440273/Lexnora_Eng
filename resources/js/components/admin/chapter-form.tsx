import { Form } from '@inertiajs/react';
import ChapterController from '@/actions/App/Modules/Admin/Http/Controllers/ChapterController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Chapter } from '@/types';

export function ChapterForm({
    courseId,
    chapter,
    onSaved,
}: {
    courseId: string;
    chapter?: Chapter;
    onSaved: () => void;
}) {
    const formProps = chapter
        ? ChapterController.update.form(chapter.id)
        : ChapterController.store.form(courseId);

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
                        <Label htmlFor="chapter-title">Title</Label>
                        <Input
                            id="chapter-title"
                            name="title"
                            defaultValue={chapter?.title ?? ''}
                            required
                            maxLength={150}
                            aria-invalid={!!errors.title}
                        />
                        <InputError message={errors.title} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="chapter-description">Description</Label>
                        <Textarea
                            id="chapter-description"
                            name="description"
                            rows={3}
                            defaultValue={chapter?.description ?? ''}
                            aria-invalid={!!errors.description}
                        />
                        <InputError message={errors.description} />
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {chapter ? 'Save changes' : 'Add chapter'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
