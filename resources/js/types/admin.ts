export type ContentType = 'word' | 'phrase' | 'sentence';

export type SelectOption = {
    value: string;
    label: string;
};

export type PageLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PageLink[];
};

export type Category = {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    courses_count?: number;
};

export type Lesson = {
    id: string;
    chapter_id: string;
    title: string;
    description: string | null;
    difficulty: number;
    estimated_minutes: number;
    sort_order: number;
    is_published: boolean;
    sentences_count?: number;
};

export type Chapter = {
    id: string;
    course_id: string;
    title: string;
    description: string | null;
    sort_order: number;
    is_published: boolean;
    lessons?: Lesson[];
};

export type Course = {
    id: string;
    title: string;
    slug: string;
    description: string | null;
    category_id: string | null;
    category?: { id: string; name: string } | null;
    cefr_level: string;
    cefr_label: string;
    difficulty: number;
    is_published: boolean;
    published_at: string | null;
    sort_order: number;
    thumbnail_url: string | null;
    chapters_count?: number;
    lessons_count?: number;
    estimated_minutes?: number;
    chapters?: Chapter[];
};

export type Audio = {
    id: string;
    sentence_id: string;
    url: string;
    original_name: string | null;
    mime_type: string | null;
    size_bytes: number | null;
    duration_ms: number | null;
    voice_label: string | null;
    is_primary: boolean;
    created_at: string | null;
};

export type Sentence = {
    id: string;
    lesson_id: string;
    parent_id: string | null;
    content_type: ContentType;
    english_text: string;
    bengali_text: string | null;
    explanation: string | null;
    sort_order: number;
    audio?: Audio[];
    children?: Sentence[];
};

export type LessonSummary = {
    id: string;
    title: string;
    course_title: string | null;
} | null;
