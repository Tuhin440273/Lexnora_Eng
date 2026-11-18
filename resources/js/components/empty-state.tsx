import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Doel } from '@/components/doel';
import { cn } from '@/lib/utils';

/** A friendly "nothing here yet" block: the doel, a small icon, a title and a hint. */
export function EmptyState({
    icon: Icon,
    title,
    children,
    className,
}: {
    icon: LucideIcon;
    title: string;
    children?: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex flex-col items-center gap-3 rounded-2xl border border-dashed bg-card/60 px-6 py-10 text-center',
                className,
            )}
        >
            <div className="relative">
                <Doel className="h-24" />
                <span className="absolute -top-1 -right-3 flex size-8 items-center justify-center rounded-full bg-star/20 text-star">
                    <Icon className="size-4" aria-hidden />
                </span>
            </div>
            <h2 className="font-heading text-xl font-semibold">{title}</h2>
            {children && (
                <p className="max-w-md text-sm text-muted-foreground">
                    {children}
                </p>
            )}
        </div>
    );
}
