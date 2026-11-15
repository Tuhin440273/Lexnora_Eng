import type { ContentType } from './admin';

export type DiffToken = {
    type: 'correct' | 'incorrect' | 'missing' | 'extra';
    expected?: string;
    typed?: string;
};

export type EarnedBadge = {
    name: string;
    description: string | null;
    icon: string | null;
    xp_reward: number;
};

export type PracticeResult = {
    attempt_id: string;
    sentence_id: string;
    content_type: ContentType;
    is_correct: boolean;
    accuracy: number;
    tokens: DiffToken[];
    counts: {
        correct: number;
        incorrect: number;
        missing: number;
        extra: number;
    };
    typed: string;
    expected: string;
    bengali: string | null;
    explanation: string | null;
    xp: number;
    finished: boolean;
    badges: EarnedBadge[];
};

export type PracticeItem = {
    id: string;
    content_type: ContentType;
    word_count: number;
    bengali: string | null;
    guide_text: string | null;
    audio_url: string | null;
    speak_text: string | null;
};

export type PracticeSessionInfo = {
    id: string;
    mode: 'easy' | 'medium' | 'hard';
    kind: 'lesson' | 'review' | 'mistakes';
    position: number;
    total: number;
    finished: boolean;
    title: string;
    back_lesson_id: string | null;
};
