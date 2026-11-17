# Development status

| Phase | Scope                                                     | Status |
| ----- | --------------------------------------------------------- | ------ |
| 1     | Project foundation                                        | Done   |
| 2     | Database, models, roles/permissions, seeders              | Done   |
| 3     | Course management (admin CRUD, audio upload)              | Done   |
| 4     | Core study engine                                         | Done   |
| 5     | Notebooks (vocabulary, mistakes, review, notes)           | Done   |
| 6     | Dashboard, gamification, leaderboard, learning settings   | Done   |
| 7     | Admin completion, QA, documentation                       | Done   |
| 8     | Vocabulary list (admin), automatic links, content pack    | Done   |
| 9     | Practice screen: autoplay, Bengali meaning, guide text    | Done   |
| 12    | Design system: colours, Bangla-ready fonts, level accents | Done   |

## Phase 7

- **Users:** list with search and filters, change role, deactivate and reactivate. Nobody edits their own account here, only a super-admin touches admins, and the last active super-admin cannot be removed. A deactivated user is signed out on the next request.
- **Roles:** edit what each role may do. The super-admin role is locked.
- **Settings:** every scoring, hint, review, XP and leaderboard value is editable, validated and applied at once. "Restore defaults" goes back to `config/learning.php`.
- **Analytics:** learners, active users, sessions, hours, average text match, learners per day, course completion, hardest sentences, content to fix. Aggregated only, no personal data.
- **Landing page:** replaces the starter page.
- **QA:** lazy loading is forbidden outside production, two real N+1 queries found and fixed (course catalog, review center), and 16 tests check that pages keep the same query count as data grows.
- **Docs:** `README.md`, `docs/DEPLOYMENT.md` (with a MySQL check), architecture notes.

## Phase 8

- **Vocabulary list (admin):** add, edit, hide, remove, search and filter (type, level, status). Needs the `vocabulary.manage` permission.
- **Automatic links:** an entry is linked to every sentence that uses it as a whole word or plural ("card" and "cards", "allergy" and "allergies", never "change" inside "exchange"). Adding or editing a sentence refreshes its links.
- **Content pack:** `ContentPackSeeder` adds 5 courses (A1 to B2), 10 lessons, 60 sentences with Bengali translations, 17 words and phrases for Easy and Medium mode, and 27 vocabulary entries. Safe to run again.

## Phase 9 - practice screen

- **Autoplay:** the audio starts half a second after each item appears. If the browser refuses, nothing is shown and the learner presses Play. A setting under Learning turns it off.
- **Bengali meaning** is shown with every item while the learner listens (it is not the answer).
- **Guide text:** a faint copy of the sentence appears in the typing box, one word per second after the audio starts, and each word turns clear when it is typed correctly (words are matched in order, one mistake does not switch the rest off). Per mode, set by the admin: on for Easy and Medium, off for Hard. The text is sent to the browser only when the mode allows it, never for later items.

## Phase 12 - design system (first step of the levels redesign)

- **Colours:** one neutral palette for light and dark (indigo primary), mapped onto the existing shadcn tokens in `resources/css/app.css`. New tokens: `--err`, `--star`, `--hint`.
- **Level accents:** each level owns one accent colour (`[data-level='starter|a1|a2|b1|b2']`). It is used with `bg-level`, `text-level` and `text-level-foreground`, never on the primary button. The server sends the lower-case level key (`CefrLevel::key()`), and `lib/levels.ts` accepts only levels that have an accent. It is applied on the course page. C1 and C2 have no accent and use the primary colour.
- **Fonts:** Hind Siliguri for body text (Latin and Bangla), Baloo Da 2 for headings, Lexend for practice letters (`font-practice`) and numbers (`stat-figure`). Loaded with `bunny()` in `vite.config.ts`, with the `bengali` subset.
- **Bangla and motion:** Bangla text (`lang="bn"`) is never italic, and `prefers-reduced-motion` is respected.
- **Not done yet:** the `Starter` level (needs a new level value and a migration), per-level hint and checking rules, the placement test and level test, the Today screen, the level map with stars, and the other items of the levels spec.

## Tests

`php artisan test`: 427 passed, 3 skipped (Two-factor is not enabled). `vp test`: 17 passed. PHPStan has a baseline (`phpstan-baseline.neon`): old findings are listed there, new code must be clean. `npm run types:check` and `vp check` are clean.

## Not done or not verified

- The new fonts (Hind Siliguri, Baloo Da 2, Lexend) and colours were compiled and checked in a build, but not looked at in a real browser. Run `npm run build` once with internet to fetch the fonts.
- Autoplay, the one-word-per-second reveal and the blur are browser behaviour. They are written and their logic is tested, but they were not watched in a real browser, and Chrome may refuse autoplay until the learner has clicked anywhere on the site once.

- The tests run on SQLite. Run them once on MySQL (see `docs/DEPLOYMENT.md`, step 1).
- Layouts were written mobile-first (tables scroll sideways, forms stack) but were not checked on real phones and tablets.
- Not built, by design for version 1: AI tutor, voice conversation, pronunciation scoring, YouTube lessons, AI lesson or quiz generation, payments, adaptive learning, native apps.
- The content pack has no audio. The Bengali translations were written for this project and should be read once by a native speaker before real learners use them.
