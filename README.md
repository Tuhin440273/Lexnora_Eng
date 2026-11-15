# Lexnora - English learning platform

Learners listen to a short recording, type what they hear, see exactly which words they got right, wrong or missed, and come back to it later. Levels A1 to C2, translations in Bengali.

Listen, Understand, Type, Check, Learn, Review.

Built with Laravel, React (Inertia, TypeScript, Tailwind, shadcn/ui), Recharts and MySQL. See `DEVELOPMENT_STATUS.md` for what is done, `docs/ARCHITECTURE.md` for the decisions, `docs/ERD.md` for the database and `docs/DEPLOYMENT.md` for going live.

## What it does

- **Learners:** browse courses, practise in Easy, Medium or Hard mode, vocabulary notebook, mistakes notebook, spaced review, personal notes, dashboard with charts, daily check-in, streak, XP, badges and a leaderboard (with an anonymous option).
- **Staff:** manage categories, courses, chapters, lessons, sentences, words and phrases, upload audio, publish and unpublish, manage users and roles, change the learning rules, see platform analytics.

## Requirements

PHP 8.3 or newer, Composer 2, Node 22 or newer, MySQL 8 or newer, Git.

## Installation

```bash
git clone https://github.com/Tuhin440273/Lexnora_Eng.git
cd Lexnora_Eng
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database and user, then set the `DB_*` values in `.env`. To get an admin account, also set:

```
ADMIN_EMAIL=admin@lexnora.test
ADMIN_PASSWORD=choose-a-strong-password
```

Then:

```bash
php artisan migrate --seed
npm run build
composer run dev
```

Open http://localhost:8000 and sign in with the admin account. In local development, email verification links are written to `storage/logs/laravel.log`.

## Ready-made content

Five courses (A1 to B2) with Bengali translations, words and phrases for Easy and Medium mode, and a vocabulary list are included. Add them to any database, as often as you like (nothing is duplicated):

```bash
php artisan db:seed --class=ContentPackSeeder
```

They have no recordings yet. Upload audio from a lesson page in Content admin; until then the browser's own voice reads the text aloud.

## Vocabulary list

Content admin, Vocabulary: add, edit, hide and remove words and phrases with English and Bengali meanings. Each entry is linked to the sentences that use it (whole words and plurals only), and it is re-linked when you add or edit a sentence. When a learner saves the same word, they see your meaning.

## Roles

| Role            | Can do                                                                        |
| --------------- | ----------------------------------------------------------------------------- |
| learner         | Study. This is the default for new accounts.                                  |
| content-manager | Edit and publish content, manage the vocabulary list, upload audio.           |
| admin           | Everything above, plus users, settings and analytics.                         |
| super-admin     | Everything, including roles and permissions. Cannot be limited or locked out. |

What each role may do can be changed under Content admin, Roles.

## Tests and checks

```bash
php artisan test           # PHP tests (they use an in-memory database)
npm run types:check        # TypeScript
npx vp test run            # frontend unit tests
npx vp check               # lint and formatting
```

## Project layout

```
app/Modules/   Learning, Courses, Dictation, Vocabulary, Review, Analytics, Gamification, Admin
resources/js/  pages, components, hooks, lib, types
routes/        web.php, learn.php (learners), admin.php (staff), settings.php
tests/Feature/ Phase2 .. Phase7 test folders
```
