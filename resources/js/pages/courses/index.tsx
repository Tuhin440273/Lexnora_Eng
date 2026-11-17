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
import { levelAccent } from '@/lib/levels';
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
                        {courses.data.map((course, index) => (
                            <li
                                key={course.id}
                                data-level={levelAccent(
                                    course.cefr_level.toLowerCase(),
                                )}
                                className="group relative flex animate-rise flex-col overflow-hidden rounded-2xl border bg-card shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg"
                                style={{ animationDelay: `${index * 70}ms` }}
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
                                        className="relative flex h-32 items-center justify-center overflow-hidden bg-linear-to-br from-level to-level/60 font-heading text-5xl font-semibold text-level-foreground"
                                    >
                                        <span className="absolute -top-10 -right-8 size-32 rounded-full bg-white/15" />
                                        <span className="absolute -bottom-12 -left-6 size-28 rounded-full bg-white/10" />
                                        <span className="relative">
                                            {course.cefr_level}
                                        </span>
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
                                        className="font-heading text-xl font-semibold after:absolute after:inset-0 hover:underline"
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
                                                className="h-full rounded-full bg-level transition-[width] duration-700 motion-reduce:transition-none"
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
