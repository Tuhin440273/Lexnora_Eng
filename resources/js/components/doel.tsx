import { cn } from '@/lib/utils';

/**
 * The doel (oriental magpie-robin), the national bird of Bangladesh, is the
 * friendly face of the app. Drawn here as a flat, original illustration.
 */
export function Doel({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 160 140"
            role="img"
            aria-label="A doel, the magpie-robin bird of Bangladesh"
            className={cn('h-28 w-auto', className)}
        >
            {/* branch */}
            <path
                d="M8 121 Q80 111 152 123"
                fill="none"
                stroke="#9a7442"
                strokeWidth="5"
                strokeLinecap="round"
            />
            <path
                d="M124 118 q10 -14 22 -12 q-4 12 -22 12z"
                fill="#5aa17f"
                opacity="0.85"
            />
            {/* tail, held up the way a doel carries it */}
            <path d="M55 86 L22 26 Q20 21 26 24 L72 74 Z" fill="#26325f" />
            {/* body */}
            <ellipse
                cx="78"
                cy="84"
                rx="35"
                ry="26"
                transform="rotate(-8 78 84)"
                fill="#26325f"
            />
            {/* white belly */}
            <ellipse
                cx="88"
                cy="95"
                rx="26"
                ry="16"
                transform="rotate(-6 88 95)"
                fill="#ffffff"
            />
            {/* wing with its white stripe */}
            <path d="M52 80 Q76 60 104 80 Q84 98 56 91 Z" fill="#1b2447" />
            <path
                d="M62 82 Q78 71 98 81"
                fill="none"
                stroke="#ffffff"
                strokeWidth="5"
                strokeLinecap="round"
            />
            {/* head */}
            <circle cx="110" cy="60" r="17" fill="#26325f" />
            <path d="M124 57 L140 62 L124 67 Z" fill="#3b3f58" />
            <circle cx="114" cy="56" r="4" fill="#ffffff" />
            <circle cx="115" cy="56" r="2" fill="#0d1226" />
            <circle cx="115.8" cy="55.2" r="0.8" fill="#ffffff" />
            {/* legs */}
            <path
                d="M82 108 V118 M92 109 V119"
                stroke="#d9a441"
                strokeWidth="2.6"
                strokeLinecap="round"
            />
        </svg>
    );
}
