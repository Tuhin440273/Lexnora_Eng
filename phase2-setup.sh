#!/usr/bin/env bash
# Lexnora Phase 2 setup. Run from the project root:  bash phase2-setup.sh
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"
[ -f artisan ] || { echo "ERROR: artisan not found. Run this from the Laravel project root."; exit 1; }

echo "==> 1/7  Installing spatie/laravel-permission"
composer require spatie/laravel-permission --no-interaction

echo "==> 2/7  Publishing the Spatie migration and config"
if ls database/migrations/*create_permission_tables.php >/dev/null 2>&1; then
  echo "    migration already published, skipping"
else
  php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction
fi

echo "==> 3/7  Adding traits to app/Models/User.php"
USER_FILE="app/Models/User.php"
if grep -q "HasLearningProfile" "$USER_FILE"; then
  echo "    already patched, skipping"
else
  perl -0pi -e 's/(class\s+User\s+extends\s+Authenticatable[^{]*\{)/$1\n    use \\Spatie\\Permission\\Traits\\HasRoles;\n    use \\App\\Modules\\Learning\\Concerns\\HasLearningProfile;\n/' "$USER_FILE"
  if ! grep -q "HasLearningProfile" "$USER_FILE"; then
    echo "ERROR: could not patch $USER_FILE automatically."
    echo "Open it and add these two lines just after the 'class User ... {' line:"
    echo "    use \\Spatie\\Permission\\Traits\\HasRoles;"
    echo "    use \\App\\Modules\\Learning\\Concerns\\HasLearningProfile;"
    exit 1
  fi
fi

echo "==> 4/7  Registering LearningServiceProvider"
PROVIDERS="bootstrap/providers.php"
if grep -q "LearningServiceProvider" "$PROVIDERS"; then
  echo "    already registered, skipping"
else
  perl -0pi -e 's/(App\\Providers\\AppServiceProvider::class,)/$1\n    App\\Modules\\Learning\\LearningServiceProvider::class,/' "$PROVIDERS"
  if ! grep -q "LearningServiceProvider" "$PROVIDERS"; then
    echo "ERROR: could not patch $PROVIDERS automatically."
    echo "Open it and add this line inside the returned array:"
    echo "    App\\Modules\\Learning\\LearningServiceProvider::class,"
    exit 1
  fi
fi

echo "==> 5/7  Admin account settings"
if ! grep -q '^ADMIN_EMAIL=' .env; then
  printf '\nADMIN_EMAIL=admin@lexnora.test\nADMIN_PASSWORD=Lexnora-Admin-2026\nADMIN_NAME="Lexnora Admin"\n' >> .env
  echo "    added ADMIN_EMAIL / ADMIN_PASSWORD to .env (local use only, change the password before deploying)"
fi
if [ -f .env.example ] && ! grep -q '^ADMIN_EMAIL=' .env.example; then
  printf '\nADMIN_EMAIL=admin@example.com\nADMIN_PASSWORD=\nADMIN_NAME="Admin"\n' >> .env.example
fi

echo "==> 6/7  Migrating and seeding"
php artisan optimize:clear
php artisan migrate
php artisan db:seed --no-interaction

echo "==> 7/7  Running tests"
php artisan test

echo
echo "Phase 2 setup finished."
