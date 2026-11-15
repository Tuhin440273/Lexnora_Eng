#!/usr/bin/env bash
# Lexnora phase 9 setup. Run from the project root:  bash phase9-setup.sh
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"
[ -f artisan ] || { echo "ERROR: artisan not found. Run this from the Laravel project root."; exit 1; }

echo "==> 1/4  Clearing caches"
php artisan optimize:clear

echo "==> 2/4  Building the frontend (needs internet once, for the Inter font)"
npm run build

echo "==> 3/4  Type check, frontend tests, lint"
npm run types:check
npx vp test run
npx vp check

echo "==> 4/4  Running tests"
php artisan test

echo
echo "Done. Start the app with:  composer run dev"
echo "Open a lesson in Easy mode: the audio starts by itself and the faint guide words appear one by one."
