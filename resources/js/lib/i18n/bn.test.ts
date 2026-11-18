import { describe, expect, it } from 'vitest';
import { translate } from '@/lib/i18n';
import { bn } from './bn';

const placeholders = (text: string) =>
    (text.match(/\{\w+\}/g) ?? []).slice().sort();

describe('the Bangla dictionary', () => {
    it('keeps the same {placeholders} as the English text', () => {
        for (const [english, bangla] of Object.entries(bn)) {
            expect(placeholders(bangla), english).toEqual(
                placeholders(english),
            );
        }
    });

    it('has no empty entries', () => {
        for (const [english, bangla] of Object.entries(bn)) {
            expect(bangla.trim(), english).not.toBe('');
        }
    });

    it('is written in Bangla (a few names and symbols stay as they are)', () => {
        const same = new Set(['email@example.com']);

        for (const [english, bangla] of Object.entries(bn)) {
            if (!same.has(english)) {
                expect(bangla, english).toMatch(/\p{Script=Bengali}/u);
            }
        }
    });

    it('translates and fills in values', () => {
        expect(translate('bn', 'Dashboard')).toBe('ড্যাশবোর্ড');
        expect(
            translate('bn', '{days} days in a row. Keep it going.', {
                days: 5,
            }),
        ).toBe('টানা 5 দিন! এভাবেই চালিয়ে যান।');
    });
});
