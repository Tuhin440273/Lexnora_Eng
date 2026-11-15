# Lexnora — Architecture decisions

## Layout (modular monolith)

```
app/Modules/
  Learning/      enums, user profile, progress, notes, settings, service provider
  Courses/       Category, Course, Chapter, Lesson, Sentence, SentenceAudio
  Dictation/     PracticeSession, PracticeAttempt, MistakeRecord  (scoring service: Phase 4)
  Vocabulary/    Vocabulary (admin master list), SavedVocabulary (learner notebook)
  Review/        ReviewSchedule, ReviewHistory
  Analytics/     DailyLearningStatistic
  Gamification/  LearningCheckin, UserXpTransaction, Badge
  Admin/         (admin controllers and services: Phase 3 and 7)
```

Each module will get `Http/Controllers`, `Http/Requests`, `Services`, `Policies` as it is built.
`LearningServiceProvider` is registered in `bootstrap/providers.php`.

## Decisions

1. **Laravel 13** is used (the installer's latest). Nothing in the project depends on 12-only behaviour.
2. **IDs:** `users` keeps the starter kit's auto-increment id so Fortify and the starter tests stay untouched. New tables use ULIDs (sortable, safe to expose in URLs).
3. **"Category":** the spec lists Category both inside a course's fields and in the hierarchy. It is implemented as a *classification of courses* (`courses.category_id`); chapters belong directly to a course.
4. **Words, phrases, sentences:** one `sentences` table with `content_type` and a self-reference `parent_id`, so every chunk can carry its own uploaded audio.
5. **Roles:** `spatie/laravel-permission`. Roles: `super-admin`, `admin`, `content-manager`, `learner`. Registration assigns `learner`.
6. **Settings in the database:** scoring rules, hints per mode, review intervals and XP values live in `app_settings` (defaults in `config/learning.php`) so the Admin Panel can change them without a deploy.
7. **XP is a ledger:** never a counter column. All awards happen in server-side services with an idempotency key.
8. **No duplicated data:** course duration, lesson counts, progress percentages and average accuracy are computed, not stored. The only snapshots are `practice_attempts.expected_text` and `saved_vocabulary.context_text`, kept on purpose so history survives content edits.
9. **UTC everywhere:** `config/app.php` timezone stays `UTC`; user timezone is display-only.
10. **Morph map:** `sentence` and `saved_vocabulary` aliases are used in polymorphic columns. It is non-enforcing so Spatie keeps working.
