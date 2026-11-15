import { Form, Head, Link } from '@inertiajs/react';
import CourseController from '@/actions/App/Modules/Admin/Http/Controllers/CourseController';
import DashboardController from '@/actions/App/Modules/Admin/Http/Controllers/DashboardController';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { Category, Course, SelectOption } from '@/types';

type Props = {
    course: Course | null;
    options: { categories: Category[]; cefr_levels: SelectOption[] };
};

const DIFFICULTIES = [
    { value: '1', label: '1 - Very easy' },
    { value: '2', label: '2 - Easy' },
    { value: '3', label: '3 - Medium' },
    { value: '4', label: '4 - Hard' },
    { value: '5', label: '5 - Very hard' },
];

export default function CourseForm({ course, options }: Props) {
    const formProps = course
        ? CourseController.update.form(course.id)
        : CourseController.store.form();

    return (
        <>
            <Head title={course ? `Edit ${course.title}` : 'New course'} />
            <div className="flex max-w-3xl flex-col gap-6 p-4">
                <PageHeader
                    title={course ? 'Edit course' : 'New course'}
                    description={
                        course
                            ? 'Change the details learners see for this course.'
                            : 'Start with the basics. You add chapters and lessons on the next page.'
                    }
                />

                <Form {...formProps} className="space-y-6">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    defaultValue={course?.title ?? ''}
                                    required
                                    maxLength={150}
                                    aria-invalid={!!errors.title}
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="slug">Web address (slug)</Label>
                                <Input
                                    id="slug"
                                    name="slug"
                                    defaultValue={course?.slug ?? ''}
                                    placeholder="Leave empty to create it from the title"
                                    aria-invalid={!!errors.slug}
                                />
                                <InputError message={errors.slug} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    rows={4}
                                    defaultValue={course?.description ?? ''}
                                    aria-invalid={!!errors.description}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-6 sm:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="category_id">
                                        Category
                                    </Label>
                                    <NativeSelect
                                        id="category_id"
                                        name="category_id"
                                        defaultValue={course?.category_id ?? ''}
                                    >
                                        <option value="">No category</option>
                                        {options.categories.map((category) => (
                                            <option
                                                key={category.id}
                                                value={category.id}
                                            >
                                                {category.name}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={errors.category_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="cefr_level">Level</Label>
                                    <NativeSelect
                                        id="cefr_level"
                                        name="cefr_level"
                                        defaultValue={
                                            course?.cefr_level ?? 'A1'
                                        }
                                    >
                                        {options.cefr_levels.map((level) => (
                                            <option
                                                key={level.value}
                                                value={level.value}
                                            >
                                                {level.label}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={errors.cefr_level} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="difficulty">
                                        Difficulty
                                    </Label>
                                    <NativeSelect
                                        id="difficulty"
                                        name="difficulty"
                                        defaultValue={String(
                                            course?.difficulty ?? 1,
                                        )}
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
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="thumbnail">Thumbnail</Label>
                                {course?.thumbnail_url && (
                                    <div className="flex items-center gap-3">
                                        <img
                                            src={course.thumbnail_url}
                                            alt="Current thumbnail"
                                            className="size-16 rounded-md object-cover"
                                        />
                                        <label className="flex items-center gap-2 text-sm">
                                            <input
                                                type="checkbox"
                                                name="remove_thumbnail"
                                                value="1"
                                                className="size-4 accent-primary"
                                            />
                                            Remove the current thumbnail
                                        </label>
                                    </div>
                                )}
                                <Input
                                    id="thumbnail"
                                    name="thumbnail"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp,image/*"
                                    aria-invalid={!!errors.thumbnail}
                                />
                                <p className="text-xs text-muted-foreground">
                                    JPG, PNG or WebP, up to 2 MB.
                                </p>
                                <InputError message={errors.thumbnail} />
                            </div>

                            <div className="flex items-center gap-3">
                                <Button type="submit" disabled={processing}>
                                    {course ? 'Save changes' : 'Create course'}
                                </Button>
                                <Button type="button" variant="ghost" asChild>
                                    <Link
                                        href={
                                            course
                                                ? CourseController.show(
                                                      course.id,
                                                  )
                                                : CourseController.index()
                                        }
                                    >
                                        Cancel
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CourseForm.layout = {
    breadcrumbs: [
        { title: 'Content admin', href: DashboardController.index() },
        { title: 'Courses', href: CourseController.index() },
    ],
};
