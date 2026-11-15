#!/usr/bin/env bash
# Lexnora Phase 3 setup. Run from the project root:  bash phase3-setup.sh
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"
[ -f artisan ] || { echo "ERROR: artisan not found. Run this from the Laravel project root."; exit 1; }

echo "==> 1/5  Generating route helpers for the new controllers (Wayfinder)"
php artisan wayfinder:generate --with-form

echo "==> 2/5  Clearing caches"
php artisan optimize:clear

echo "==> 3/5  Building the frontend (needs internet once, to download the Inter font)"
npm run build

echo "==> 4/5  Type check"
npm run types:check

echo "==> 5/5  Running tests"
php artisan test

echo
echo "Phase 3 setup finished. Start the app with:  composer run dev"
echo "Then log in as the admin and open http://localhost:8000/admin"
