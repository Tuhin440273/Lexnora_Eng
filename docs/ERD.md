# Lexnora — Database ERD (Phase 2)

Diagram source is Mermaid; GitHub renders it automatically.

- `users` keeps the starter kit's auto-increment id. Every **new** table uses a **ULID** primary key.
- All timestamps are stored in **UTC**. `user_profiles.timezone` is for display only.
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` come from `spatie/laravel-permission`.

```mermaid
erDiagram
    users ||--o| user_profiles : "has one"
    users }o--o{ roles : "model_has_roles"
    roles }o--o{ permissions : "role_has_permissions"

    categories ||--o{ courses : "classifies"
    courses ||--o{ chapters : "contains"
    chapters ||--o{ lessons : "contains"
    lessons ||--o{ sentences : "contains"
    sentences ||--o{ sentences : "parent_id (word/phrase chunks)"
    sentences ||--o{ sentence_audio : "has audio files"
    sentences }o--o{ vocabulary : "sentence_vocabulary"

    users ||--o{ practice_sessions : "starts"
    lessons ||--o{ practice_sessions : "practised in"
    practice_sessions ||--o{ practice_attempts : "records"
    sentences ||--o{ practice_attempts : "answered"
    users ||--o{ practice_attempts : "makes"

    users ||--o{ user_course_progress : "tracks"
    courses ||--o{ user_course_progress : "progress of"
    users ||--o{ user_lesson_progress : "tracks"
    lessons ||--o{ user_lesson_progress : "progress of"

    users ||--o{ saved_vocabulary : "saves"
    vocabulary |o--o{ saved_vocabulary : "based on"
    sentences |o--o{ saved_vocabulary : "context"

    users ||--o{ mistake_records : "has"
    sentences ||--o{ mistake_records : "missed"
    practice_attempts |o--o{ mistake_records : "latest_attempt_id"

    users ||--o{ review_schedules : "owns"
    review_schedules ||--o{ review_history : "logs"
    sentences |o--o{ review_schedules : "reviewable (morph: sentence)"
    saved_vocabulary |o--o{ review_schedules : "reviewable (morph: saved_vocabulary)"

    users ||--o{ user_notes : "writes"
    sentences |o--o{ user_notes : "attached to"

    users ||--o{ learning_checkins : "checks in"
    users ||--o{ user_xp_transactions : "earns"
    users }o--o{ badges : "user_badges"
    users ||--o{ daily_learning_statistics : "aggregates"

    users {
        bigint id PK
        string email
    }
    user_profiles {
        ulid id PK
        bigint user_id FK "unique"
        string timezone
        bool leaderboard_anonymous
        json preferences
    }
    categories {
        ulid id PK
        string slug "unique"
    }
    courses {
        ulid id PK
        ulid category_id FK
        string cefr_level "A1..C2"
        tinyint difficulty "1-5"
        bool is_published
    }
    chapters {
        ulid id PK
        ulid course_id FK
        int sort_order
    }
    lessons {
        ulid id PK
        ulid chapter_id FK
        smallint estimated_minutes
        int sort_order
    }
    sentences {
        ulid id PK
        ulid lesson_id FK
        ulid parent_id FK "null = top-level sentence"
        string content_type "word|phrase|sentence"
        text english_text
        text bengali_text
    }
    sentence_audio {
        ulid id PK
        ulid sentence_id FK
        string disk
        string path "file path, never binary"
        bool is_primary
    }
    vocabulary {
        ulid id PK
        string normalized_term
        string type "word|phrase"
    }
    practice_sessions {
        ulid id PK
        bigint user_id FK
        ulid lesson_id FK
        string mode "easy|medium|hard"
        string status
        decimal average_accuracy
    }
    practice_attempts {
        ulid id PK
        ulid practice_session_id FK
        ulid sentence_id FK
        text submitted_text
        text expected_text "snapshot"
        decimal accuracy
        bool is_correct
        json error_details
    }
    mistake_records {
        ulid id PK
        bigint user_id FK
        ulid sentence_id FK "unique with user_id"
        ulid latest_attempt_id FK
        int times_missed
        timestamp resolved_at
    }
    review_schedules {
        ulid id PK
        bigint user_id FK
        string reviewable_type "morph"
        ulid reviewable_id "morph"
        tinyint step
        timestamp due_at
    }
    user_xp_transactions {
        ulid id PK
        bigint user_id FK
        int amount
        string idempotency_key "unique"
    }
    badges {
        ulid id PK
        string code "unique"
        string criteria_type
        int criteria_value
    }
    app_settings {
        bigint id PK
        string key "unique"
        json value
    }
```

`app_settings` has no relations: it holds admin-editable rules (scoring, hints per mode, review intervals, XP values, leaderboard metric).

## Relationships explained

| Relationship                                                    | Meaning                                                                                                                                                                                                 |
| --------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `categories → courses`                                          | A category classifies many courses. A course has one category (optional).                                                                                                                               |
| `courses → chapters → lessons → sentences`                      | The learning tree. Deleting a parent permanently removes its children (FK cascade). Day to day, content is **soft deleted**.                                                                            |
| `sentences.parent_id`                                           | A word or phrase row points at the sentence it was cut from. This lets EASY mode play _words → phrases → full sentence_, each with its own audio. Rows with `parent_id = null` are the real sentences.  |
| `sentence_audio`                                                | One sentence can have several audio files; `is_primary` marks the one the player uses.                                                                                                                  |
| `sentence_vocabulary`                                           | Many-to-many: a sentence teaches many words, a word appears in many sentences.                                                                                                                          |
| `practice_sessions → practice_attempts`                         | A session is one run of a lesson in one mode. Each submitted answer is an attempt. The expected text is **copied** into the attempt so later content edits never change history.                        |
| `mistake_records`                                               | One row per user + sentence. It stores the counters and points at the **latest** failing attempt, so the wrong answer and diff are not duplicated.                                                      |
| `review_schedules` / `review_history`                           | Polymorphic: a review item is a `sentence` or a `saved_vocabulary`. `step` indexes the configurable interval list; `interval_days`, `repetitions`, `ease_factor` are ready for spaced repetition later. |
| `user_xp_transactions`                                          | Append-only ledger. A user's XP = `SUM(amount)`. The unique `idempotency_key` makes double rewards impossible.                                                                                          |
| `user_badges` / `user_course_progress` / `user_lesson_progress` | Per-user state. Progress percentages are **derived**, not stored twice.                                                                                                                                 |
| `daily_learning_statistics`                                     | Pre-aggregated numbers for the dashboard charts (one row per user per day). Can be rebuilt from raw tables.                                                                                             |
