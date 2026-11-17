import { describe, expect, it } from 'vitest';
import { starsFor } from './stars';

describe('starsFor', () => {
    it('gives no stars until the lesson is completed', () => {
        expect(starsFor('not_started', null)).toBe(0);
        expect(starsFor('in_progress', 95)).toBe(0);
    });

    it('gives one star for a completed lesson with a low or missing score', () => {
        expect(starsFor('completed', null)).toBe(1);
        expect(starsFor('completed', 40)).toBe(1);
        expect(starsFor('completed', 69.9)).toBe(1);
    });

    it('gives two stars from 70% and three from 90%', () => {
        expect(starsFor('completed', 70)).toBe(2);
        expect(starsFor('completed', 89.9)).toBe(2);
        expect(starsFor('completed', 90)).toBe(3);
        expect(starsFor('completed', 100)).toBe(3);
    });
});
