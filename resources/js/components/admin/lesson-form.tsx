import { Form } from '@inertiajs/react';
import LessonController from '@/actions/App/Modules/Admin/Http/Controllers/LessonController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { Lesson } from '@/types';

const DIFFICULTIES = [
    { value: '1', label: '1 - Very easy' },
    { value: '2', label: '2 - Easy' },
    { value: '3', label: '3 - Medium' },
    { value: '4', label: '4 - Hard' },
    { value: '5', label: '5 - Very hard' },
];

export function LessonForm({
    chapterId,
    lesson,
    onSaved,
}: {
    chapterId: string;
    lesson?: Lesson;
    onSaved: () => void;
}) {
    const formProps = lesson
        ? LessonController.update.form(lesson.id)
        : LessonController.store.form(chapterId);

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
                        <Label htmlFor="lesson-title">Title</Label>
                        <Input
                            id="lesson-title"
                            name="title"
                            defaultValue={lesson?.title ?? ''}
                            required
                            maxLength={150}
                            aria-invalid={!!errors.title}
                        />
                        <InputError message={errors.title} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="lesson-description">Description</Label>
                        <Textarea
                            id="lesson-description"
                            name="description"
                            rows={3}
                            defaultValue={lesson?.description ?? ''}
                            aria-invalid={!!errors.description}
                        />
                        <InputError message={errors.description} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="lesson-difficulty">
                                Difficulty
                            </Label>
                            <NativeSelect
                                id="lesson-difficulty"
                                name="difficulty"
                                defaultValue={String(lesson?.difficulty ?? 1)}
                            >
                                {DIFFICULTIES.map((difficulty) => (
                                    <option
                                        key={difficulty.value}
                                        value={difficulty.value}
                                    >
                                        {difficulty.label}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={errors.difficulty} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="lesson-minutes">
                                Estimated minutes
                            </Label>
                            <Input
                                id="lesson-minutes"
                                name="estimated_minutes"
                                type="number"
                                min={1}
                                max={240}
                                defaultValue={lesson?.estimated_minutes ?? 5}
                                required
                                aria-invalid={!!errors.estimated_minutes}
                            />
                            <InputError message={errors.estimated_minutes} />
                        </div>
                    </div>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {lesson ? 'Save changes' : 'Add lesson'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
