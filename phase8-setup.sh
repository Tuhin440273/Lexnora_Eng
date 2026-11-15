#!/usr/bin/env bash
# Lexnora phase 8 setup. Run from the project root:  bash phase8-setup.sh
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"
[ -f artisan ] || { echo "ERROR: artisan not found. Run this from the Laravel project root."; exit 1; }

echo "==> 1/6  Clearing caches"
php artisan optimize:clear

echo "==> 2/6  Adding the ready-made courses and vocabulary (safe to repeat)"
php artisan db:seed --class=Database\\Seeders\\ContentPackSeeder --no-interaction

echo "==> 3/6  Generating route helpers (Wayfinder)"
php artisan wayfinder:generate --with-form

echo "==> 4/6  Building the frontend (needs internet once, for the Inter font)"
npm run build

echo "==> 5/6  Type check, frontend tests, lint"
npm run types:check
npx vp test run
npx vp check

echo "==> 6/6  Running tests"
php artisan test

echo
echo "Done. Start the app with:  composer run dev"
echo "Learners: open /courses.  Staff: open /admin and then Vocabulary."
