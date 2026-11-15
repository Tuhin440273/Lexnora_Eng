import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import CatalogController from '@/actions/App/Modules/Courses/Http/Controllers/CatalogController';
import { PageHeader } from '@/components/admin/page-header';
import { Pagination } from '@/components/admin/pagination';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Button } from '@/components/ui/button';
import { formatMinutes } from '@/lib/format';
import type { Paginated, SelectOption } from '@/types';

type CourseCard = {
    id: string;
    title: string;
    description: string | null;
    cefr_level: string;
    category: string | null;
    thumbnail_url: string | null;
    lessons_count: number;
    minutes: number;
    completed_lessons: number;
    progress: number;
};

type Filters = { search: string; cefr_level: string; category_id: string };

export default function CoursesIndex({
    courses,
    filters,
    options,
}: {
    courses: Paginated<CourseCard>;
    filters: Filters;
    options: {
        categories: { id: string; name: string }[];
        cefr_levels: SelectOption[];
    };
}) {
    const [values, setValues] = useState<Filters>(filters);

    const apply = (next: Filters) => {
        setValues(next);
        router.get(
            CatalogController.index().url,
            Object.fromEntries(
                Object.entries(next).filter(([, v]) => v !== ''),
            ),
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Courses" />
            <div className="flex flex-col gap-6 p-4">
                <PageHeader
                    title="Courses"
                    description="Pick a course and practise by listening and typing."
                />

                <form
                    role="search"
                    className="flex flex-wrap gap-3"
                    onSubmit={(event: FormEvent) => {
                        event.preventDefault();
                        apply(values);
                    }}
                >
                    <label htmlFor="search" className="sr-only">
                        Search courses
                    </label>
                    <Input
                        id="search"
                        type="search"
                        placeholder="Search by title"
                        className="min-w-48 flex-1"
                        value={values.search}
                        onChange={(event) =>
                            setValues({ ...values, search: event.target.value })
                        }
                    />
                    <label htmlFor="level" className="sr-only">
                        Level
                    </label>
                    <NativeSelect
                        id="level"
                        className="w-48"
                        value={values.cefr_level}
                        onChange={(event) =>
                            apply({ ...values, cefr_level: event.target.value })
                        }
                    >
                        <option value="">All levels</option>
                        {options.cefr_levels.map((level) => (
                            <option key={level.value} value={level.value}>
                                {level.label}
                            </option>
                        ))}
                    </NativeSelect>
                    <label htmlFor="category" className="sr-only">
                        Category
                    </label>
                    <NativeSelect
                        id="category"
                        className="w-48"
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
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                {courses.data.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        No courses match. Try another level or clear the search.
                    </p>
                ) : (
                    <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {courses.data.map((course) => (
                            <li
                                key={course.id}
                                className="flex flex-col overflow-hidden rounded-xl border bg-card"
                            >
                                {course.thumbnail_url ? (
                                    <img
                                        src={course.thumbnail_url}
                                        alt=""
                                        className="h-32 w-full object-cover"
                                    />
                                ) : (
                                    <div
                                        aria-hidden
                                        className="flex h-32 items-center justify-center bg-accent text-3xl font-semibold text-accent-foreground"
                                    >
                                        {course.cefr_level}
                                    </div>
                                )}
                                <div className="flex flex-1 flex-col gap-3 p-4">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge variant="secondary">
                                            {course.cefr_level}
                                        </Badge>
                                        {course.category && (
                                            <span className="text-xs text-muted-foreground">
                                                {course.category}
                                            </span>
                                        )}
                                    </div>
                                    <Link
                                        href={CatalogController.showCourse(
                                            course.id,
                                        )}
                                        className="text-lg font-semibold hover:underline"
                                    >
                                        {course.title}
                                    </Link>
                                    {course.description && (
                                        <p className="line-clamp-2 text-sm text-muted-foreground">
                                            {course.description}
                                        </p>
                                    )}
                                    <div className="mt-auto space-y-1">
                                        <div
                                            role="progressbar"
                                            aria-valuemin={0}
                                            aria-valuemax={100}
                                            aria-valuenow={course.progress}
                                            aria-label={`${course.title} progress`}
                                            className="h-1.5 overflow-hidden rounded-full bg-muted"
                                        >
                                            <div
                                                className="h-full bg-primary"
                                                style={{
                                                    width: `${course.progress}%`,
                                                }}
                                            />
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            {course.completed_lessons} of{' '}
                                            {course.lessons_count} lessons ·{' '}
                                            {formatMinutes(course.minutes)}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={courses.links} total={courses.total} />
            </div>
        </>
    );
}

CoursesIndex.layout = {
    breadcrumbs: [{ title: 'Courses', href: CatalogController.index() }],
};
