import { describe, expect, it } from 'vitest';
import { plural } from './format';

describe('plural', () => {
    it('uses the singular for exactly one', () => {
        expect(plural(1, 'lesson')).toBe('1 lesson');
    });

    it('uses the plural for zero and for more than one', () => {
        expect(plural(0, 'lesson')).toBe('0 lessons');
        expect(plural(2, 'session')).toBe('2 sessions');
    });

    it('accepts an irregular plural', () => {
        expect(plural(1, 'story', 'stories')).toBe('1 story');
        expect(plural(3, 'story', 'stories')).toBe('3 stories');
    });
});
