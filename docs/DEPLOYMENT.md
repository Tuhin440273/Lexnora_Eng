# Deployment

## 1. Check it on MySQL first

The automated tests run on an in-memory SQLite database. Run them once on MySQL too, in a **separate** test database, before you deploy.

```bash
sudo mysql -e "CREATE DATABASE english_platform_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "GRANT ALL PRIVILEGES ON english_platform_test.* TO 'english_user'@'localhost'; FLUSH PRIVILEGES;"

sed -e 's#<env name="DB_CONNECTION" value="sqlite"/>#<env name="DB_CONNECTION" value="mysql"/>#' \
    -e 's#<env name="DB_DATABASE" value=":memory:"/>#<env name="DB_DATABASE" value="english_platform_test"/>#' \
    phpunit.xml > phpunit.mysql.xml

grep -n 'DB_' phpunit.mysql.xml        # must show mysql and english_platform_test
php artisan test --configuration=phpunit.mysql.xml
```

Do not run the last line unless the `grep` shows `english_platform_test`: the tests empty the database they run on. Delete `phpunit.mysql.xml` afterwards (it is not committed).

## 2. Server requirements

- PHP 8.3 or newer with the extensions mbstring, xml, curl, zip, intl, bcmath, pdo_mysql, fileinfo.
- MySQL 8 or newer, utf8mb4.
- A web server whose document root is the `public` folder, with HTTPS.
- **Upload limits.** Audio files can be up to `AUDIO_MAX_KB` (10 MB by default). PHP's own defaults are smaller, so set `upload_max_filesize` and `post_max_size` to at least 12M, and the same for the web server (`client_max_body_size` in nginx).
- **Mail.** Email verification and password reset send real emails. Configure `MAIL_*` before launch, or learners cannot verify their accounts.

## 3. Environment

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=english_platform
DB_USERNAME=...
DB_PASSWORD=...
ADMIN_EMAIL=you@your-domain
ADMIN_PASSWORD=a-long-unique-password
AUDIO_MAX_KB=10240
MAIL_MAILER=smtp
```

`ADMIN_PASSWORD` is only read when the admin user is seeded. Remove it from `.env` afterwards.

## 4. First deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --force        # roles, settings, badges and the admin user; demo courses are skipped in production
php artisan optimize
```

Optional: `php artisan db:seed --class=ContentPackSeeder --force` adds the five ready-made courses.

Make `storage` and `bootstrap/cache` writable by the web server user.

## 5. Every later deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
```

## 6. Backups

Back up two things together: the database, and `storage/app/private` (uploaded audio and course thumbnails). One without the other leaves sentences with missing audio.

## 7. After deploying

1. Sign in as the admin and open Content admin, Settings. Check the scoring, hints, review and XP values.
2. Create a category and a course, add a lesson, upload audio, publish, and practise it with a learner account.
3. Open the site on a phone and a tablet.
