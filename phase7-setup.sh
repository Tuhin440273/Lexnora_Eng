#!/usr/bin/env bash
# Lexnora phase 7 setup. Run from the project root:  bash phase7-setup.sh
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"
[ -f artisan ] || { echo "ERROR: artisan not found. Run this from the Laravel project root."; exit 1; }

echo "==> 1/6  Updating the database (one new column on users)"
php artisan optimize:clear
php artisan migrate

echo "==> 2/6  Generating route helpers (Wayfinder)"
php artisan wayfinder:generate --with-form

echo "==> 3/6  Building the frontend (needs internet once, for the Inter font)"
npm run build

echo "==> 4/6  Type check and frontend tests"
npm run types:check
npx vp test run

echo "==> 5/6  Lint and formatting"
npx vp check

echo "==> 6/6  Running tests"
php artisan test

echo
echo "Phase 7 is ready. Start the app with:  composer run dev"
echo "Sign in as the admin and open /admin (Users, Roles, Settings, Analytics)."
