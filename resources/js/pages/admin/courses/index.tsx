import { Head, Link, router } from '@inertiajs/react';
import { Eye, EyeOff, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import CourseController from '@/actions/App/Modules/Admin/Http/Controllers/CourseController';
import DashboardController from '@/actions/App/Modules/Admin/Http/Controllers/DashboardController';
import { ConfirmDialog } from '@/components/admin/confirm-dialog';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { StatusBadge } from '@/components/admin/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useCan } from '@/hooks/use-can';
import { formatMinutes } from '@/lib/format';
import type { Category, Course, Paginated, SelectOption } from '@/types';

type Filters = {
    search: string;
    status: string;
    cefr_level: string;
    category_id: string;
};

type Props = {
    courses: Paginated<Course>;
    filters: Filters;
    options: { categories: Category[]; cefr_levels: SelectOption[] };
};

export default function CoursesIndex({ courses, filters, options }: Props) {
    const canManage = useCan('content.manage');
    const canPublish = useCan('content.publish');
    const [values, setValues] = useState<Filters>(filters);

    const apply = (next: Filters) => {
        setValues(next);

        const query = Object.fromEntries(
            Object.entries(next).filter(([, value]) => value !== ''),
        );

        router.get(CourseController.index().url, query, {
            preserveState: true,
            replace: true,
        });
    };

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        apply(values);
    };

    const hasFilters = Object.values(filters).some((value) => value !== '');

    return (
        <>
            <Head title="Courses" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Courses"
                    description="Create courses, then add chapters, lessons and sentences."
                    actions={
                        canManage && (
                            <Button asChild>
                                <Link href={CourseController.create()}>
                                    <Plus /> New course
                                </Link>
                            </Button>
                        )
                    }
                />

                <form
                    onSubmit={submitSearch}
                    className="flex flex-wrap items-end gap-3"
                    role="search"
                >
                    <div className="min-w-48 flex-1">
                        <label htmlFor="course-search" className="sr-only">
                            Search courses
                        </label>
                        <Input
                            id="course-search"
                            type="search"
                            placeholder="Search by title"
                            value={values.search}
                            onChange={(event) =>
                                setValues({
                                    ...values,
                                    search: event.target.value,
                                })
                            }
                        />
                    </div>
                    <div className="w-36">
                        <label htmlFor="filter-status" className="sr-only">
                            Status
                        </label>
                        <NativeSelect
                            id="filter-status"
                            value={values.status}
                            onChange={(event) =>
                                apply({ ...values, status: event.target.value })
                            }
                        >
                            <option value="">All statuses</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </NativeSelect>
                    </div>
                    <div className="w-44">
                        <label htmlFor="filter-level" className="sr-only">
                            Level
                        </label>
                        <NativeSelect
                            id="filter-level"
                            value={values.cefr_level}
                            onChange={(event) =>
                                apply({
                                    ...values,
                                    cefr_level: event.target.value,
                                })
                            }
                        >
                            <option value="">All levels</option>
                            {options.cefr_levels.map((level) => (
                                <option key={level.value} value={level.value}>
                                    {level.label}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                    <div className="w-44">
                        <label htmlFor="filter-category" className="sr-only">
                            Category
                        </label>
                        <NativeSelect
                            id="filter-category"
                            value={values.category_id}
                            onChange={(event) =>
                                apply({
                                    ...values,
                                    category_id: event.target.value,
                                })
                            }
                        >
                            <option value="">All categories</option>
                            {options.categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                {courses.data.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        {hasFilters
                            ? 'No course matches these filters.'
                            : 'No courses yet. Create the first one to get started.'}
                    </p>
                ) : (
                    <div className="rounded-xl border bg-card">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Course</TableHead>
                                    <TableHead>Level</TableHead>
                                    <TableHead>Content</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {courses.data.map((course) => (
                                    <TableRow key={course.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                {course.thumbnail_url ? (
                                                    <img
                                                        src={
                                                            course.thumbnail_url
                                                        }
                                                        alt=""
                                                        className="size-10 rounded-md object-cover"
                                                    />
                                                ) : (
                                                    <div
                                                        aria-hidden
                                                        className="flex size-10 items-center justify-center rounded-md bg-accent text-sm font-semibold text-accent-foreground"
                                                    >
                                                        {course.cefr_level}
                                                    </div>
                                                )}
                                                <div className="min-w-0">
                                                    <Link
                                                        href={CourseController.show(
                                                            course.id,
                                                        )}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {course.title}
                                                    </Link>
                                                    <div className="text-xs text-muted-foreground">
                                                        {course.category
                                                            ?.name ??
                                                            'No category'}
                                                    </div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {course.cefr_level}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {course.chapters_count ?? 0}{' '}
                                            chapters,{' '}
                                            {course.lessons_count ?? 0} lessons
                                            <div className="text-xs">
                                                {formatMinutes(
                                                    course.estimated_minutes,
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                published={course.is_published}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-1">
                                                {canPublish && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={
                                                            course.is_published
                                                                ? `Unpublish ${course.title}`
                                                                : `Publish ${course.title}`
                                                        }
                                                        onClick={() =>
                                                            router.patch(
                                                                CourseController.togglePublish.url(
                                                                    course.id,
                                                                ),
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        {course.is_published ? (
                                                            <EyeOff />
                                                        ) : (
                                                            <Eye />
                                                        )}
                                                    </Button>
                                                )}
                                                {canManage && (
                                                    <>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={CourseController.edit(
                                                                    course.id,
                                                                )}
                                                                aria-label={`Edit ${course.title}`}
                                                            >
                                                                <Pencil />
                                                            </Link>
                                                        </Button>
                                                        <ConfirmDialog
                                                            title={`Delete "${course.title}"?`}
                                                            description="The course and all of its chapters, lessons and sentences will be removed."
                                                            onConfirm={() =>
                                                                router.delete(
                                                                    CourseController.destroy.url(
                                                                        course.id,
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
                                                                    aria-label={`Delete ${course.title}`}
                                                                >
                                                                    <Trash2 />
                                                                </Button>
                                                            }
                                                        />
                                                    </>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                <Pagination links={courses.links} total={courses.total} />
            </div>
        </>
    );
}

CoursesIndex.layout = {
    breadcrumbs: [
        { title: 'Content admin', href: DashboardController.index() },
        { title: 'Courses', href: CourseController.index() },
    ],
};
