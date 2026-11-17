/**
 * Level accent colours (see resources/css/app.css, `[data-level]`).
 *
 * Each level owns one accent. The server sends the lower-case CEFR key
 * (`a1`, `b2`...); only levels that have an accent return a value, so the page
 * falls back to the primary colour for the rest.
 */
export const LEVEL_KEYS = ['starter', 'a1', 'a2', 'b1', 'b2'] as const;

export type LevelKey = (typeof LEVEL_KEYS)[number];

export function levelAccent(
    key: string | null | undefined,
): LevelKey | undefined {
    return LEVEL_KEYS.find((level) => level === key);
}
