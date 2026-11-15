#!/usr/bin/env bash
# Lexnora phases 4-6 setup. Run from the project root:  bash phase456-setup.sh
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"
[ -f artisan ] || { echo "ERROR: artisan not found. Run this from the Laravel project root."; exit 1; }

echo "==> 1/7  Installing the chart library (recharts)"
npm install --no-audit --no-fund

echo "==> 2/7  Updating the database (one new migration)"
php artisan optimize:clear
php artisan migrate

echo "==> 3/7  Making sure the default settings and badges exist"
php artisan db:seed --class=Database\\Seeders\\SettingsSeeder --no-interaction
php artisan db:seed --class=Database\\Seeders\\BadgeSeeder --no-interaction

echo "==> 4/7  Generating route helpers (Wayfinder)"
php artisan wayfinder:generate --with-form

echo "==> 5/7  Building the frontend (needs internet once, for the Inter font)"
npm run build

echo "==> 6/7  Type check and frontend tests"
npm run types:check
npx vp test run

echo "==> 7/7  Running tests"
php artisan test

echo
echo "Phases 4-6 are ready. Start the app with:  composer run dev"
echo "Then open http://localhost:8000/courses and start a lesson."
