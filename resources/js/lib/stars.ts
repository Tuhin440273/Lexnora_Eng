/**
 * Stars shown on a lesson tile. A display figure only, worked out from what the
 * server already sends (the lesson status and the best text match).
 */
export type LessonStatus = 'not_started' | 'in_progress' | 'completed';

export const STAR_RULE =
    '3 stars: 90% or more · 2 stars: 70% or more · 1 star: lesson completed';

export function starsFor(
    status: LessonStatus,
    bestAccuracy: number | null,
): 0 | 1 | 2 | 3 {
    if (status !== 'completed') {
        return 0;
    }

    if (bestAccuracy !== null && bestAccuracy >= 90) {
        return 3;
    }

    return bestAccuracy !== null && bestAccuracy >= 70 ? 2 : 1;
}
