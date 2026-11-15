import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PageLink } from '@/types';

function cleanLabel(label: string): string {
    return label
        .replace('&laquo;', '‹')
        .replace('&raquo;', '›')
        .replace('Previous', 'Prev')
        .trim();
}

export function Pagination({
    links,
    total,
}: {
    links: PageLink[];
    total: number;
}) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav
            aria-label="Pagination"
            className="flex flex-wrap items-center justify-between gap-3 pt-2"
        >
            <p className="text-sm text-muted-foreground">{total} in total</p>
            <ul className="flex flex-wrap items-center gap-1">
                {links.map((link, index) => {
                    const className = cn(
                        'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm',
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'hover:bg-accent',
                        link.url === null && 'pointer-events-none opacity-40',
                    );

                    return (
                        <li key={`${index}-${link.label}`}>
                            {link.url === null ? (
                                <span className={className}>
                                    {cleanLabel(link.label)}
                                </span>
                            ) : (
                                <Link
                                    href={link.url}
                                    preserveScroll
                                    className={className}
                                    aria-current={
                                        link.active ? 'page' : undefined
                                    }
                                >
                                    {cleanLabel(link.label)}
                                </Link>
                            )}
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
