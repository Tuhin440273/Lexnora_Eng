import { coverSceneFor } from '@/lib/cover-art';
import type { CoverScene } from '@/lib/cover-art';
import { cn } from '@/lib/utils';

function Scene({ scene }: { scene: CoverScene }) {
    switch (scene) {
        case 'sunrise':
            return (
                <>
                    <circle
                        cx="128"
                        cy="84"
                        r="32"
                        fill="#fff"
                        opacity="0.92"
                    />
                    {[0, 1, 2, 3, 4, 5, 6].map((index) => {
                        const angle = Math.PI + (index * Math.PI) / 6;

                        return (
                            <line
                                key={index}
                                x1={128 + Math.cos(angle) * 40}
                                y1={84 + Math.sin(angle) * 40}
                                x2={128 + Math.cos(angle) * 52}
                                y2={84 + Math.sin(angle) * 52}
                                stroke="#fff"
                                strokeWidth="4"
                                strokeLinecap="round"
                                opacity="0.7"
                            />
                        );
                    })}
                    <path
                        d="M20 128 V104 Q60 82 110 106 T200 98 V128 Z"
                        fill="#fff"
                        opacity="0.35"
                    />
                    <path
                        d="M60 128 V114 Q110 96 150 116 T200 112 V128 Z"
                        fill="#fff"
                        opacity="0.25"
                    />
                </>
            );
        case 'shopping':
            return (
                <>
                    <path
                        d="M104 54 H164 L170 118 H98 Z"
                        fill="#fff"
                        opacity="0.92"
                    />
                    <path
                        d="M120 66 V58 Q134 36 148 58 V66"
                        fill="none"
                        stroke="#fff"
                        strokeWidth="5"
                        strokeLinecap="round"
                        opacity="0.8"
                    />
                    <circle cx="86" cy="98" r="19" fill="#ffd76a" />
                    <circle
                        cx="86"
                        cy="98"
                        r="12"
                        fill="none"
                        stroke="#e0a92f"
                        strokeWidth="3"
                    />
                </>
            );
        case 'health':
            return (
                <>
                    <path
                        d="M130 112 C84 82 94 46 118 50 C128 52 130 60 130 60 C130 60 134 50 146 50 C170 48 176 82 130 112 Z"
                        fill="#fff"
                        opacity="0.92"
                    />
                    <path
                        d="M24 84 H88 L100 64 L116 104 L130 80 H200"
                        fill="none"
                        stroke="#fff"
                        strokeWidth="5"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        opacity="0.55"
                    />
                </>
            );
        case 'work':
            return (
                <>
                    <path
                        d="M118 62 V54 Q118 46 126 46 H150 Q158 46 158 54 V62"
                        fill="none"
                        stroke="#fff"
                        strokeWidth="6"
                        opacity="0.8"
                    />
                    <rect
                        x="92"
                        y="62"
                        width="94"
                        height="56"
                        rx="10"
                        fill="#fff"
                        opacity="0.92"
                    />
                    <rect
                        x="92"
                        y="84"
                        width="94"
                        height="6"
                        fill="#000"
                        opacity="0.12"
                    />
                    <rect
                        x="130"
                        y="80"
                        width="18"
                        height="14"
                        rx="3"
                        fill="#ffd76a"
                    />
                </>
            );
        case 'travel':
            return (
                <>
                    <path
                        d="M24 108 Q70 60 112 92 T196 56"
                        fill="none"
                        stroke="#fff"
                        strokeWidth="4"
                        strokeDasharray="2 9"
                        strokeLinecap="round"
                        opacity="0.7"
                    />
                    <path
                        d="M146 112 C120 86 118 62 146 56 C174 62 172 86 146 112 Z"
                        fill="#fff"
                        opacity="0.95"
                    />
                    <circle cx="146" cy="76" r="9" fill="#ffd76a" />
                    <ellipse
                        cx="146"
                        cy="116"
                        rx="22"
                        ry="5"
                        fill="#000"
                        opacity="0.12"
                    />
                </>
            );
        default:
            return (
                <>
                    <path
                        d="M104 118 V58 Q124 48 146 60 V120 Q124 108 104 118 Z"
                        fill="#fff"
                        opacity="0.92"
                    />
                    <path
                        d="M146 60 Q168 48 188 58 V118 Q168 108 146 120 Z"
                        fill="#fff"
                        opacity="0.72"
                    />
                    <path
                        d="M112 74 Q126 68 138 75 M112 88 Q126 82 138 89 M154 75 Q166 68 180 74 M154 89 Q166 83 180 88"
                        fill="none"
                        stroke="#000"
                        strokeWidth="3"
                        strokeLinecap="round"
                        opacity="0.14"
                    />
                </>
            );
    }
}

/** A flat picture for a course cover, chosen from the course category. */
export function CoverArt({
    category,
    className,
}: {
    category: string | null;
    className?: string;
}) {
    return (
        <svg
            aria-hidden
            viewBox="0 0 200 128"
            preserveAspectRatio="xMaxYMax meet"
            className={cn('h-full w-auto', className)}
        >
            <Scene scene={coverSceneFor(category)} />
        </svg>
    );
}
