#!/usr/bin/env bash
# Lexnora phase 11 setup (PHPStan baseline, so GitHub CI can pass). Run from the project root:  bash phase11-setup.sh
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"
[ -f artisan ] || { echo "ERROR: artisan not found. Run this from the Laravel project root."; exit 1; }

echo "==> 1/4  Clearing caches"
php artisan optimize:clear

echo "==> 2/4  PHP style (Pint) and static analysis (PHPStan)"
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress

echo "==> 3/4  Type check, frontend tests, lint"
npm run types:check
npx vp test run
npx vp check

echo "==> 4/4  Running tests"
php artisan test
echo "Done. Expected: PHPStan 'No errors' and 'Tests: 3 skipped, 425 passed'"
