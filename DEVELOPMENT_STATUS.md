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
| 13    | Practice comfort: letter size, shortcuts, hint-free score | Done   |
| 14    | Classical look: colours, serif headings, lesson road      | Done   |

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
- **Fonts:** Hind Siliguri for body text (Latin and Bangla), a serif font for headings (see Phase 14), Lexend for practice letters (`font-practice`) and numbers (`stat-figure`). Loaded with `bunny()` in `vite.config.ts`, with the `bengali` subset.
- **Bangla and motion:** Bangla text (`lang="bn"`) is never italic, and `prefers-reduced-motion` is respected.
- **Not done yet:** the `Starter` level (needs a new level value and a migration), per-level hint and checking rules, the placement test and level test, the Today screen, the level map with stars, and the other items of the levels spec.

## Phase 14 - classical look

- **Colours:** warm ivory with deep royal blue and antique gold (light), ink navy with gold (dark), in `resources/css/app.css`. The level accents (A1 amber, A2 green, B1 blue, B2 plum, Starter coral) are softened to match.
- **Fonts:** Playfair Display for English headings and Noto Serif Bengali for Bangla headings (both loaded with `bunny()` in `vite.config.ts`; Georgia is the fallback), Hind Siliguri for body text, Lexend for practice letters. This replaces Baloo Da 2.
- **Animation:** pages and cards rise in one after the other, tiles pop in, the START bubble bobs, cards lift on hover, and words drift slowly behind the landing and sign-in pages (`components/floating-words.tsx`). Everything stands still when the device asks for less motion.
- **Course page as a road:** the lessons of each chapter sit on a winding road (left to right, then back right to left), four tiles per row, two on a phone (`components/lesson-road.tsx`). Each tile shows stars, the next lesson is marked START, and numbers count on across chapters. The star rule is printed under the course title: 3 stars from 90%, 2 from 70%, 1 for a completed lesson (`lib/stars.ts`, a display figure worked out from the data the server already sends).
- **Restyled:** course cards (cover in the level colour), lesson page (three mode cards with a green, gold and red top line), landing page, sign-in pages and the dashboard (animation).
- **Bangladesh pack:** `BangladeshPackSeeder` (separate from the first pack, so its numbers stay as they are) adds 2 courses (A1 Life in Bangladesh, A2 Getting Around Bangladesh), 8 lessons, 48 sentences with Bengali translations, 23 words and phrases for Easy and Medium mode, and 18 vocabulary entries. Safe to run again. The Bengali was written for this project and is in the review sheet (`lexnora-bengali-review.xlsx`, 193 rows) for a native speaker to read.
- **Doel and pictures:** the doel (magpie-robin, the national bird of Bangladesh) is drawn in `components/doel.tsx` and appears on the new-learner dashboard card and in the empty-state blocks (`components/empty-state.tsx`: vocabulary, notes, mistakes, review history, course search). Course covers carry a flat picture chosen from the category (`components/cover-art.tsx`, `lib/cover-art.ts`: sunrise, shopping bag, heart, briefcase, map pin, book). All of it is code, so there are no image files to download.
- **Small fixes:** counts read correctly ("1 lesson", "1 session", `plural()` in `lib/format.ts`), and the learner pages (vocabulary, review, mistakes, notes, progress, leaderboard, courses) sit in the middle with the same width and a gentle rise-in.
- **Next review on the result bar:** after a full sentence the bar says when it comes back ("Comes back for review tomorrow", "in 3 days", or "Mastered. This sentence is done with review."). Words and phrases are not scheduled, so they say nothing. A wrong answer starts the schedule again at the first step. The server sends `next_review` with the result (`PracticeSessionService`).
- **MySQL check:** two things differed from SQLite and are now handled in the tests, not in the app: `SUM()` gives text on MySQL (the app already casts it to a number), and MySQL keeps JSON keys in its own order (so the test compares arrays without caring about order).
- **Bug fixed:** the level colour (`bg-level`, `text-level-foreground`) always showed the primary colour, because Tailwind worked it out once at the root. It is now an `inline` theme value, so `data-level` on a page or card changes it.

## Phase 13 - practice screen comfort

- **Focused practice screen:** the practice page has no sidebar. A close button, a thin progress bar with the item count, and the letter-size buttons sit on top. In the middle there is one big play button (replay and speed under it), the Bengali meaning, and the typing line. A bar at the bottom holds the guide toggle, the hint button and "Check answer"; after an answer the same bar shows the result (green or red) and the "Next" button, so it is always in reach, also on phones. When every word matched, the word-by-word comparison is left out because it would only repeat the text. The page is laid out by `pages/practice/show.tsx` (no layout, see `app.tsx`) and `components/audio-player.tsx`.
- **Dashboard:** welcome by first name, a large "Continue where you stopped" card (a "Browse courses" card for a new learner), four main figures (streak, XP, words learned, reviews due), today's two goals as rings (green when reached), the weekly chart with today highlighted and a friendly note when the week is empty, recent practice with a coloured match chip, badges, and the other totals as a list. The numbers and the server code are unchanged (`pages/dashboard.tsx`, `components/weekly-chart.tsx`). Checked in headless Chromium for a learner with data and a new learner, in light, dark and phone size.
- **Letter size:** A- and A+ buttons above the typing box change the size of the guide text and of what the learner types (normal, large, larger). The choice is kept per device in the browser (`lib/letter-size.ts`, `hooks/use-letter-size.ts`).
- **Shortcuts:** Alt+I asks for a hint and Alt+G shows or hides the guide text, next to the existing Ctrl+Enter (check), Alt+P (play or pause) and Alt+R (replay). Alt+H and Alt+S were avoided because browsers use them for menus. The help line under the box lists the ones that apply.
- **Right without hints:** the session summary shows how many answers were right with no hint (`session.correct_without_hints`, counted on the server from the saved attempts). It is only a display figure. XP and scores are unchanged.
- **Own branding:** the Laravel starter-kit mark is replaced by the Lexnora mark (headphones with a text cursor: listen, then type) in the app, the sign-in pages, the browser tab (`favicon.svg`, `favicon.ico`) and `apple-touch-icon.png`. The "Documentation" link to the Laravel docs is removed; the "Repository" link points to this project.
- **Fixes found while checking on a real machine:** `User.php` trait list written on one line (Pint on PHP 8.5), a number instead of a string for the role id in `admin/roles/index.tsx` (Wayfinder types come from the real database columns), and font fallbacks switched off in `vite.config.ts` (the optional "fontaine" package is not installed).

## Tests

`php artisan test`: 436 passed, 3 skipped (Two-factor is not enabled). `vp test`: 30 passed. PHPStan has a baseline (`phpstan-baseline.neon`): old findings are listed there, new code must be clean. `npm run types:check` and `vp check` are clean.

## Not done or not verified

- The focused practice screen was opened and clicked in headless Chromium in the sandbox (desktop, phone size, light and dark: play controls, letter size, Alt+G, Alt+I, wrong and exact answers). It was not tried on a real phone or with real audio recordings.
- Check types in the sandbox only after migrating a database and regenerating Wayfinder, and run Pint with a fresh cache, otherwise results can differ from a real machine.
- Autoplay, the one-word-per-second reveal and the blur are browser behaviour. They are written and their logic is tested, but they were not watched in a real browser, and Chrome may refuse autoplay until the learner has clicked anywhere on the site once.

- The tests run on SQLite by default. The whole suite was also run on MySQL 8.0.46 in a separate test database (all migrations, the seeders and 439 tests passed), but not on MySQL 8.4 and not with a large amount of data. Run it once on your own MySQL (see `docs/DEPLOYMENT.md`, step 1).
- Layouts were written mobile-first (tables scroll sideways, forms stack) but were not checked on real phones and tablets.
- Not built, by design for version 1: AI tutor, voice conversation, pronunciation scoring, YouTube lessons, AI lesson or quiz generation, payments, adaptive learning, native apps.
- The content pack has no audio. The Bengali translations were written for this project and should be read once by a native speaker before real learners use them.
