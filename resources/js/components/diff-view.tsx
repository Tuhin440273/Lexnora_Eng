import { cn } from '@/lib/utils';
import type { DiffToken } from '@/types';

import { useT } from '@/lib/i18n';
/**
 * Shows the typed text against the expected text, word by word. Meaning is
 * carried by words and symbols as well as by colour.
 */
export function DiffView({
    tokens,
    className,
}: {
    tokens: DiffToken[];
    className?: string;
}) {
    const t = useT();
    return (
        <p className={cn('text-lg leading-relaxed', className)}>
            {tokens.map((token, index) => (
                <span key={index} className="mr-1.5 inline-block">
                    {token.type === 'correct' && <span>{token.expected}</span>}

                    {token.type === 'incorrect' && (
                        <>
                            <span className="sr-only">{t('You typed')} </span>
                            <span className="text-err line-through decoration-2">
                                {token.typed}
                            </span>
                            <span className="sr-only">{t(', should be')} </span>
                            <span className="ml-1 rounded bg-success/15 px-1 font-medium text-success">
                                {token.expected}
                            </span>
                        </>
                    )}

                    {token.type === 'missing' && (
                        <>
                            <span className="sr-only">
                                {t('Missing word:')}{' '}
                            </span>
                            <span className="rounded border border-dashed border-warning bg-warning/15 px-1 font-medium">
                                {token.expected}
                            </span>
                        </>
                    )}

                    {token.type === 'extra' && (
                        <>
                            <span className="sr-only">{t('Extra word:')} </span>
                            <span className="text-err line-through decoration-2">
                                {token.typed}
                            </span>
                        </>
                    )}
                </span>
            ))}
        </p>
    );
}

export function DiffLegend({ className }: { className?: string }) {
    const t = useT();
    return (
        <ul
            className={cn(
                'flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground',
                className,
            )}
        >
            <li>
                <span className="text-err line-through">{t('typed')}</span>{' '}
                {t('wrong or extra')}
            </li>
            <li>
                <span className="rounded bg-success/15 px-1 text-success">
                    {t('word')}
                </span>{' '}
                \1{t('what it should be')}
            </li>
            <li>
                <span className="rounded border border-dashed border-warning px-1">
                    {t('word')}
                </span>{' '}
                \1{t('missing')}
                \2
            </li>
        </ul>
    );
}
