import { describe, expect, it } from 'vitest';
import { countOf, translate } from './i18n';

describe('translate', () => {
    it('returns the English text for English', () => {
        expect(translate('en', 'Dashboard')).toBe('Dashboard');
    });

    it('falls back to English when there is no Bangla for a phrase', () => {
        expect(translate('bn', 'A phrase nobody has translated')).toBe(
            'A phrase nobody has translated',
        );
    });

    it('fills in {placeholders}, also in English', () => {
        expect(translate('en', 'Welcome back, {name}', { name: 'Tuhin' })).toBe(
            'Welcome back, Tuhin',
        );
    });

    it('leaves an unknown placeholder as it is', () => {
        expect(translate('en', 'Hello {who}', {})).toBe('Hello {who}');
    });
});

describe('countOf', () => {
    it('uses the singular for one and the plural otherwise, in English', () => {
        expect(countOf('en', 1, 'lesson')).toBe('1 lesson');
        expect(countOf('en', 3, 'lesson')).toBe('3 lessons');
        expect(countOf('en', 2, 'story', 'stories')).toBe('2 stories');
    });

    it('counts with the Bangla counting word', () => {
        expect(countOf('bn', 3, 'lesson')).toMatch(/^3টি /);
    });
});
