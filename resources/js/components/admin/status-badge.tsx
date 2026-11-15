import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export function StatusBadge({
    published,
    className,
}: {
    published: boolean;
    className?: string;
}) {
    return (
        <Badge
            variant="outline"
            className={cn(
                published
                    ? 'border-success/30 bg-success/10 text-success'
                    : 'border-warning/40 bg-warning/15 text-warning-foreground dark:text-warning',
                className,
            )}
        >
            {published ? 'Published' : 'Draft'}
        </Badge>
    );
}
