#!/usr/bin/env bash
# Lexnora phase 10 setup (style fixes + docs). Run from the project root:  bash phase10-setup.sh
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"
[ -f artisan ] || { echo "ERROR: artisan not found. Run this from the Laravel project root."; exit 1; }

echo "==> 1/5  Clearing caches"
php artisan optimize:clear

echo "==> 2/5  PHP style check (Pint)"
vendor/bin/pint --test

echo "==> 3/5  Type check, frontend tests, lint"
npm run types:check
npx vp test run
npx vp check

echo "==> 4/5  Running tests"
php artisan test

echo "==> 5/5  Done. Expected last test line: Tests: 3 skipped, 425 passed"
