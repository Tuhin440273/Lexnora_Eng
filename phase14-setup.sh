#!/usr/bin/env bash
# Lexnora phase 14 setup (classical look, everything so far).
# Run from the project root:  bash phase14-setup.sh
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"
[ -f artisan ] || { echo "ERROR: artisan not found. Run this from the Laravel project root."; exit 1; }

echo "==> 1/5  Clearing caches"
php artisan optimize:clear

echo "==> 2/5  PHP style (Pint) and static analysis (PHPStan)"
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress

echo "==> 3/5  Type check, frontend tests, lint"
npm run types:check
npx vp test run
npx vp check

echo "==> 4/5  Running tests"
php artisan test

echo "==> 5/5  Building the frontend (needs internet once, for the fonts)"
npm run build

echo "Done. Expected: PHPStan 'No errors', 'Tests: 3 skipped, 446 passed', Vitest 43 passed, build finished."
