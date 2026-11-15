# Development status

| Phase | Scope                                                        | Status                             |
| ----- | ------------------------------------------------------------ | ---------------------------------- |
| 1     | Project foundation                                           | Done                               |
| 2     | Database, models, roles/permissions, seeders                 | Done                               |
| 3     | Course management (admin CRUD, audio upload)                 | Done                               |
| 4     | Core study engine (browse, audio player, dictation, scoring) | Delivered, run `phase456-setup.sh` |
| 5     | Notebooks (vocabulary, mistakes, review, notes)              | Delivered, run `phase456-setup.sh` |
| 6     | Dashboard, gamification, leaderboard, learning settings      | Delivered, run `phase456-setup.sh` |
| 7     | Admin completion (users, roles, analytics, settings) and QA  | Next                               |

## Phase 4 - study engine

- Learner pages: courses (search, level, category), course page, lesson page with three modes.
- A session fixes its list of sentences when it starts (`practice_sessions.plan`). EASY goes words, phrases, sentence (shortest first); MEDIUM phrases then sentence; HARD sentences only. Sentences without chunks are practised whole.
- The browser receives only the current item. The answer text is never sent, except for the browser-voice fallback on items that have no recording yet.
- `ScoringService`: normalises case, spacing, quotes and punctuation (admin settings), character-level similarity (Levenshtein on whole characters) for the percentage, word-level alignment for the correct / wrong / missing / extra highlighting. The percentage is text similarity only and is never described as pronunciation.
- Hints are counted by the server (3 / 1 / 0 by mode). Study time is measured by the server and capped at 2 minutes per answer.
- Reusable `AudioPlayer`: play/pause, replay, six speeds, length, loading and error states, Alt+P / Alt+R shortcuts, browser-voice fallback.

## Phase 5 - notebooks

- Vocabulary notebook: save from the result screen (with the sentence as context) or by hand, search, filter, edit, learned flag, delete.
- Mistakes notebook: wrong sentences are saved automatically (one row per sentence with a counter), shown with the learner's answer and the correct text, practised again in a session, resolved when typed correctly.
- Review: configurable intervals (`review.intervals_days`), sentences are reviewed by dictation, words by self-rating, history page. A failed review starts again at step 1.
- Personal notes, optionally attached to a sentence.
- Private records can only be touched by their owner. Not even the super-admin passes these checks.

## Phase 6 - dashboard and gamification

- XP is an append-only ledger written only by the server. Every award has an idempotency key, and a sentence pays at most once per day, so repeating a lesson does not farm points.
- Daily check-in with streak bonus, streak from practice and check-ins in the learner's time zone, nine badges, daily and weekly goals.
- Dashboard (stats, weekly chart, goals, continue, recent sessions, badges), progress page with charts, leaderboard (daily / weekly / monthly / all time, XP or study time), learning settings (goals, time zone, defaults, anonymous leaderboard).

## Tests

`php artisan test`: 326 passed, 3 skipped (Two-factor is not enabled). `vp test`: 6 passed. `npm run types:check` and `vp check` are clean.

## Known notes

- Landing page and public pages are still the starter kit's.
- There is no separate chapter page: chapters are listed on the course page.
- There is no "previous sentence" button: going back would let a learner retry an answer after seeing it.
- The admin user, role and analytics screens come in Phase 7.
- Sample content has no recordings, so practice uses the browser's voice until audio is uploaded.
