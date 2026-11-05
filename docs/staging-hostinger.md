# Hostinger Staging Guide

## Security remediation deployment

The application now enforces a nonce-based CSP by default. Deploy the changed
Blade views, JavaScript, configuration, middleware, `public/.htaccess`, and all
files produced by `npm ci` followed by `npm run build`. Include
`public/vendor/sienna` (fonts and translations as well as JavaScript) and
`public/build`. Run Composer install from the existing lockfile as usual.

Sienna's `locales/` and `fonts/` folders are generated assets and are ignored by
Git. Both `npm run build` and `npm run dev` recreate them from the pinned npm
package. They remain required in the deployment artifact; include them when
uploading a local build to Hostinger even though they are excluded from commits.

On publicly reachable staging use `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`,
`SESSION_HTTP_ONLY=true`, and `SESSION_SAME_SITE=lax`. Keep local HTTP settings
separate. Do not make Laravel's `XSRF-TOKEN` HttpOnly; its frontend CSRF use is intentional.

Clear stale route/view/config caches before warming the deployment caches:

```sh
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Additional browser origins are configured through comma-separated
`CSP_CONNECT_SOURCES`, `CSP_FRAME_SOURCES`, and `CSP_IMAGE_SOURCES`. Use exact
HTTPS/WSS origins. The public Reverb origin and configured IdP logout/login
origins are included automatically. `TRUSTED_PROXIES` accepts verified proxy
IPs/CIDRs; it intentionally does not accept `*`. Do not add arbitrary origins
from violation reports to the allowlist.

`CSP_REPORT_ONLY=true` is a temporary diagnostic option, not a completed
remediation. Restore `false` for enforcement. Avoid caching nonce-bearing HTML
in a CDN or full-page cache. Do not add another server-level CSP that replaces
the application's dynamic nonce or leaves the old permissive policy present.

`public/.htaccess` supplies HSTS for HTTPS static files as well as dynamic
responses, if the host supports these directives. The initial duration is one
day (`86400`). Laravel uses the same default through `HSTS_MAX_AGE`. Keep the
server value and application setting aligned when increasing the duration.
`includeSubDomains` is off, and preload is not enabled. Verify every affected
host before changing either. If TLS terminates at a proxy/CDN, configure that
layer to send HSTS as well; do not blindly trust forwarded headers.

In the hosting PHP configuration set `expose_php=Off` where available. Verify
that the hosting panel redirects HTTP to HTTPS. On managed/shared hosting,
coordinate ports 21, 3306 and 65002 with the provider: application code cannot
close those services. Ports 80 and 443 can remain approved web services.

After upload verify real GET responses for `/`, `/login`, an authenticated page,
an error page and `/build/assets/*.css`. Check CSP nonce matching, HSTS,
`X-Powered-By` suppression and session-cookie flags. Redirects should retain
Location/status/cookies with an empty body. Test SSO login/logout, chatbot,
accessibility, booking, modals, realtime notifications, document previews and
PDF export for every role, then repeat the original ZAP/Nmap scans. These code
changes alone do not establish closure of the historical scan findings.

Developer checks:

```sh
npm run build
php artisan test tests/Feature/SecurityHeadersTest.php tests/Feature/SecurityPageTest.php
npm audit
```

Template events use the explicit registry in `resources/js/core/action-handlers.js`;
attributes contain handler identifiers and JSON data, never executable expressions.
Do not reintroduce inline event attributes or runtime string evaluation.
`scripts/vendor-assets.mjs` maintains the pinned Sienna asset, local resources
and its narrowly scoped CSP style compatibility patches. Review these patches
when changing the dependency version; the build fails if the expected API changes.

This guide is for the `dcms` Laravel app on a shared Hostinger plan.

Goal: create a staging copy that is separate from production and from the other group's project.

## Recommended Setup

- Staging URL: `staging.your-domain.com`
- Staging project folder: `domains/your-domain.com/staging-dcms`
- Staging document root: `domains/your-domain.com/staging-dcms/public`
- Staging database: a new MySQL database, not the production database
- Staging `.env`: a separate file on Hostinger, not committed to Git

If Hostinger does not let you point a subdomain directly to `staging-dcms/public`, use:

- Subdomain folder: `domains/your-domain.com/public_html/staging`
- Upload only Laravel's `public` contents there
- Put the rest of the app outside the public web folder
- Update `index.php` paths to point to the real app folder

The direct document-root-to-`public` option is cleaner and safer.

## 1. Coordinate With The Other Group

Before touching Hostinger:

- Agree on the subdomain name.
- Agree on the folder name.
- Confirm who owns the production database and production files.
- Do not reuse their database, `.env`, cron jobs, or app folder.
- Make a backup before changing anything in hPanel/File Manager.

Suggested names:

- Subdomain: `dcms-staging.your-domain.com`
- Folder: `dcms-staging`
- Database name suffix: `_dcms_staging`

## 2. Prepare The Project Locally

From the `dcms` folder:

```bash
composer install
npm install
npm run build
php artisan test
```

For deployment builds, use:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Do not upload:

- `.env`
- `node_modules`
- local `vendor` if you will run Composer on Hostinger
- `storage/logs/*.log`
- `public/hot`

Upload `public/build` because this app uses Vite.

## 3. Create The Subdomain In Hostinger

In Hostinger hPanel:

1. Open the shared hosting account.
2. Go to Domains or Subdomains.
3. Create a subdomain such as `dcms-staging`.
4. Set the subdomain folder/document root to the Laravel `public` directory if hPanel allows it.

Preferred target:

```text
domains/your-domain.com/dcms-staging/public
```

If hPanel creates a default folder, keep note of it. You may need to move the app or adjust the document root after creation.

## 4. Create A Separate Staging Database

In hPanel:

1. Go to MySQL Databases.
2. Create a new database for staging.
3. Create or assign a database user.
4. Save the DB name, username, password, host, and port.

Never point staging at the production database unless you intentionally want staging users to edit production data.

## 5. Upload Or Clone The App

Best option if SSH/Git is available:

```bash
cd domains/your-domain.com
git clone <your-repo-url> dcms-staging
cd dcms-staging
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Alternative if using File Manager/FTP:

1. Zip the Laravel project after building assets.
2. Upload it into the staging folder.
3. Extract it.
4. Confirm `artisan`, `app`, `bootstrap`, `config`, `public`, `resources`, `routes`, `storage`, and `vendor` exist.

If Composer is not available on Hostinger, run this locally before zipping:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Then upload the generated `vendor` and `public/build` folders.

## 6. Create The Staging `.env`

On Hostinger, create:

```text
dcms-staging/.env
```

Start from `.env.example` or the production `.env`, but change these at minimum:

```env
APP_NAME="DCMS Staging"
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://dcms-staging.your-domain.com

DB_CONNECTION=mysql
DB_HOST=your_hostinger_db_host
DB_PORT=3306
DB_DATABASE=your_staging_database
DB_USERNAME=your_staging_database_user
DB_PASSWORD=your_staging_database_password

SESSION_DOMAIN=dcms-staging.your-domain.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
QUEUE_CONNECTION=database
CACHE_STORE=file
FILESYSTEM_DISK=local
```

Important integration notes for this project:

- `OIDC_REDIRECT_URI` must use the staging URL.
- The identity provider/Auth0 app must allow the staging callback URL.
- `FLSS_*`, `OGOS_*`, and `OCMS_*` should use test endpoints or safe credentials if available.
- `OPENAI_API_KEY` can be reused only if the team accepts staging usage costs.
- `OPENAI_CHATBOT_MODEL`, `OPENAI_SIGNATURE_MODEL`, and `OPENAI_REPORT_MODEL` can be set separately in staging if you want lower-cost test models.
- `MAIL_*` should use a test mailbox or safe mail service so staging does not email real users by accident.
- `JWT_SECRET` should be separate from production when possible.

Generate a staging app key after `.env` exists:

```bash
php artisan key:generate
```

## 7. Set Permissions

Laravel needs write access to:

```text
storage
bootstrap/cache
```

On SSH:

```bash
chmod -R 775 storage bootstrap/cache
```

On File Manager, set write permissions for these folders if the app cannot write cache/log files.

## 8. Run Migrations And Seeders

For an empty staging database:

```bash
php artisan migrate --force
php artisan db:seed --force
```

If you need production-like test data, export a sanitized production database and import it into staging. Remove or anonymize patient data before sharing access.

## 9. Link Public Storage

Run:

```bash
php artisan storage:link
```

If symlinks are not allowed on the shared plan, copy public storage files manually to:

```text
public/storage
```

Then be careful to recopy uploaded test files when needed.

## 10. Optimize Laravel

After the `.env` is final:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If you edit `.env`, run:

```bash
php artisan config:clear
php artisan config:cache
```

## 11. Configure Cron And Queue

This project has scheduled commands and notifications. Add a cron job in Hostinger if your plan supports it:

```bash
* * * * * cd /home/YOUR_HOSTINGER_USER/domains/your-domain.com/dcms-staging && php artisan schedule:run >> /dev/null 2>&1
```

For queues on shared hosting, the simplest option is usually database queue plus a cron-triggered worker:

```bash
* * * * * cd /home/YOUR_HOSTINGER_USER/domains/your-domain.com/dcms-staging && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

If Hostinger does not allow every-minute cron jobs, use the smallest interval available.

## 12. Test The Staging Site

Check:

- `/` loads without a 500 error.
- Login works.
- OIDC callback returns to the staging URL.
- Admin pages load CSS/JS from `public/build`.
- Database writes go to the staging database.
- File uploads work.
- Appointment reminders or mail features do not contact real users unintentionally.
- Logs appear in `storage/logs`.

Useful commands:

```bash
php artisan about
php artisan migrate:status
php artisan route:list
php artisan config:show app.url
```

## 13. Suggested Deployment Routine

For each staging update:

```bash
cd /home/YOUR_HOSTINGER_USER/domains/your-domain.com/dcms-staging
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If you cannot build on Hostinger, build locally, upload the changed files, then run:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Rollback Plan

Before every staging update:

- Export the staging database.
- Zip or copy the current staging folder.
- Note the current Git commit hash.

Rollback options:

```bash
git log --oneline -5
git checkout <previous_commit>
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate:status
php artisan config:cache
```

If a migration changed the database, restore the staging database backup.
<<<<<<< HEAD

=======
>>>>>>> fixdev
