import { describe, expect, it } from 'vitest';
import { coverSceneFor } from './cover-art';

describe('coverSceneFor', () => {
    it('matches the categories of the content pack', () => {
        expect(coverSceneFor('Daily Life')).toBe('sunrise');
        expect(coverSceneFor('Shopping & Money')).toBe('shopping');
        expect(coverSceneFor('Health')).toBe('health');
        expect(coverSceneFor('Work & Business')).toBe('work');
        expect(coverSceneFor('Travel')).toBe('travel');
    });

    it('ignores case', () => {
        expect(coverSceneFor('HEALTH')).toBe('health');
    });

    it('falls back to a book for unknown or missing categories', () => {
        expect(coverSceneFor('Grammar')).toBe('book');
        expect(coverSceneFor(null)).toBe('book');
        expect(coverSceneFor(undefined)).toBe('book');
    });
});
