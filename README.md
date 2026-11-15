# Lexnora — English Learning Platform

Listen → Understand → Type → Check → Learn → Review.
Laravel + React (Inertia) + MySQL. See `DEVELOPMENT_STATUS.md` for progress and `docs/` for the ERD and architecture decisions.

## Requirements
PHP 8.2+, Composer 2, Node 22+, MySQL 8+, Git.

## Installation
```bash
git clone https://github.com/Tuhin440273/Lexnora_Eng.git
cd Lexnora_Eng
composer install
npm install
cp .env.example .env
php artisan key:generate
```
Create a MySQL database and user, then set the `DB_*` values in `.env`.
Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env` to get an admin account from the seeder.

```bash
php artisan migrate --seed
composer run dev
```
Open http://localhost:8000.

## Tests
```bash
php artisan test
```
Tests use an in-memory SQLite database, so your MySQL data is never touched.

## Roles
`super-admin`, `admin`, `content-manager`, `learner` (default for new registrations).
