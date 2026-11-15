# Development status

| Phase | Scope | Status |
|---|---|---|
| 1 | Project foundation (Laravel starter kit, MySQL, Git) | Done |
| 2 | Database, models, roles/permissions, seeders | Delivered, run `phase2-setup.sh` and the tests to confirm |
| 3 | Course management (admin CRUD, audio upload) | Next |
| 4 | Core study engine (audio player, dictation, scoring) | Planned |
| 5 | Notebooks (vocabulary, mistakes, review, notes) | Planned |
| 6 | Dashboard and gamification | Planned |
| 7 | Admin completion and QA | Planned |

## Phase 2 contents
* 7 migrations covering all tables in `docs/ERD.md`
* 22 Eloquent models in `app/Modules/*/Models`, 6 enums, `HasLearningProfile` trait for `User`
* Roles and permissions (Spatie), admin seeder driven by `ADMIN_EMAIL` / `ADMIN_PASSWORD`
* Seeders: roles, settings, badges, admin, user defaults, sample content (2 courses, 4 lessons, 32 sentence rows, 13 vocabulary entries)
* Pest tests in `tests/Feature/Phase2`

## Known notes
* Sample sentences have no audio yet; admins upload audio in Phase 3.
* Email verification links are written to `storage/logs/laravel.log` in local development.
