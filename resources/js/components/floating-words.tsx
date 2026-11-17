import { cn } from '@/lib/utils';

// [word, left, top, size (rem), delay (s), duration (s)]
const WORDS: [string, string, string, number, number, number][] = [
    ['listen', '-2%', '30%', 3.4, 0, 24],
    ['check', '44%', '0%', 2.2, 4, 25],
    ['write', '24%', '74%', 2.4, 9, 23],
    ['word', '6%', '84%', 3.4, 6, 26],
    ['sentence', '40%', '84%', 2.6, 8, 28],
    ['hear', '68%', '72%', 3, 2, 22],
    ['learn', '84%', '82%', 3, 5, 21],
    ['review', '92%', '24%', 2.4, 7, 27],
];

/**
 * A quiet backdrop of English words drifting slowly. Decorative only: hidden
 * from screen readers, and it stands still when the device asks for less motion.
 */
export function FloatingWords({ className }: { className?: string }) {
    return (
        <div
            aria-hidden
            className={cn(
                'pointer-events-none absolute inset-0 overflow-hidden select-none',
                className,
            )}
        >
            {WORDS.map(([word, left, top, size, delay, duration]) => (
                <span
                    key={word}
                    className="absolute animate-drift font-heading text-primary/[0.07]"
                    style={{
                        left,
                        top,
                        fontSize: `${size}rem`,
                        animationDelay: `-${delay}s`,
                        animationDuration: `${duration}s`,
                    }}
                >
                    {word}
                </span>
            ))}
        </div>
    );
}
