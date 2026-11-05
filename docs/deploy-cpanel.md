# Put the new site on new.paysomeonetowrite.com

This is for the subdomain only. The current website stays where it is. Both sites read the same database. Until you change two switches, the new site cannot change an old order and cannot send mail or take a payment.

You do not need to be a programmer. You do need cPanel. You do not need SSH. If cPanel shows a Terminal button, you can use it for the commands below. If it does not, use the installer page for the application key and ask the host to run the two `php artisan` commands, or use the Terminal if it appears later.

The file you upload is `deploy/new-psw-release.zip`. It already contains the program and the styled pages. The server does not need Node.

## 1. Back up the database first

In cPanel, open phpMyAdmin. Select the live database (the one the current site uses). Use Export. Choose Quick and SQL. Download the file and keep it somewhere that is not the website folder.

Do this before you create a database user and before you run any command.

## 2. Create the subdomain

In cPanel, open Domains (or Subdomains). Create `new.paysomeonetowrite.com`.

The best document root is the `public` folder inside the release, for example:

```text
/home/USERNAME/new-psw/public
```

Replace USERNAME with the name cPanel shows on the home page. If the form lets you type the document root, use that path. The rest of the files, including `.env`, stay outside the folder visitors can open.

If the host will not let you change the document root, use the layout in the next section. Do not point the subdomain at the folder that contains `.env`.

In MultiPHP Manager, set this subdomain to PHP 8.4.

## 3. If the document root cannot be changed

cPanel sometimes forces the subdomain to a folder such as:

```text
/home/USERNAME/public_html/new.paysomeonetowrite.com
```

1. Upload and extract the zip to `/home/USERNAME/new-psw` (not inside `public_html`).
2. Copy only the contents of `new-psw/public` into the subdomain folder. Copy `index.php`, `.htaccess`, `install.php`, `build`, and the other files in `public`. Do not copy `.env` there. There is no `.env` in `public`.
3. Edit the subdomain’s `index.php`. Change the three lines that mention `__DIR__.'/../` so they point at the real program. For the folders above, they become:

```php
if (file_exists($maintenance = __DIR__.'/../../new-psw/storage/framework/maintenance.php')) {
```

```php
require __DIR__.'/../../new-psw/vendor/autoload.php';
```

```php
$app = require_once __DIR__.'/../../new-psw/bootstrap/app.php';
```

4. Copy `install.php` as well, and edit its one path if it no longer sits next to the `public` folder. The simplest fix is to leave `install.php` only in `new-psw/public` and open it through the subdomain after the document root is `public`. If you had to copy it, the first lines must still load `vendor/autoload.php` from `new-psw`.

After this, a visitor who opens the subdomain gets `index.php`. They cannot open `.env` by guessing a URL.

## 4. Upload and extract the release

In cPanel File Manager, go to `/home/USERNAME`. Upload `new-psw-release.zip`. Extract it. You should see a folder `new-psw` (or the zip’s top folder) containing `artisan`, `app`, `vendor`, `public`, and `storage`.

The zip has no `.env`, no passwords, no customer list, and no test files.

## 5. Create .env

In the release folder, copy `.env.production.example` and name the copy `.env`.

Fill in the blanks. Leave these as they already are:

```text
APP_ENV=production
APP_DEBUG=false
READ_ONLY_LEGACY=true
SAFE_MODE=true
MAIL_MAILER=log
QUEUE_CONNECTION=sync
```

Set:

```text
APP_URL=https://new.paysomeonetowrite.com
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=the live database name
DB_USERNAME=the user from the next step
DB_PASSWORD=the password from the next step
LEGACY_SITE_ID=1
INSTALL_TOKEN=a long random string you invent
```

`APP_URL` has no slash at the end. `INSTALL_TOKEN` is not the office password. Use cPanel’s password generator and store the token somewhere private. The installer will not open until this is filled in.

Look at `prox_site` in phpMyAdmin before changing `LEGACY_SITE_ID`. Use the `site_id` of this brand.

`LEGACY_UPLOADS_PATH` is the folder where the current site already stores order files, if you know it. It must be outside `public_html`. Leave it empty if you are not sure. The new site will then use its own private `storage` folder, and it will not see the old files until you set the path.

Leave every payment key empty. Leave `MAIL_FROM_ADDRESS` empty for now.

## 6. Create the database user

The database already exists. Do not create a second empty database and do not import the zip into it.

In cPanel, open MySQL Databases.

1. Create a new user. Give it a new password. This password goes in `.env` and nowhere else.
2. Add that user to the existing live database.
3. Tick SELECT, INSERT, UPDATE, DELETE, CREATE, and INDEX. Tick ALTER as well if the list has it, because new tables need indexes. Leave DROP unticked if the list lets you. The new tables are created once. The old `prox_` tables do not need to be dropped or altered.
4. If the host only offers All Privileges, you can use that. The site still refuses to change `prox_` rows while `READ_ONLY_LEGACY=true`. The backup from step 1 is what you restore if something is wrong.

## 7. Set the application key

Open this address in the browser:

```text
https://new.paysomeonetowrite.com/install.php
```

If the padlock is not there yet, do the HTTPS step first, or use the Terminal method below.

Paste the installer token. You should see a list of checks. Nothing on that page is your database password, your key, or the token you will save again.

If “Application key” says fail, click **Write the application key**. The key is written into `.env`. The page does not show it. Do not click the button a second time. It will refuse to replace a key that is already there.

Read the other rows. PHP should be 8.4. The database should be connected. `prox_customer`, `prox_employee`, `prox_order`, and `prox_site` should say present. Migration files should say they do not name a `prox_` table. Mail will say it is set to log. That is correct for today. The scheduler will say it has not run yet. That is correct until the cron is added.

Click **Turn the installer off**. Open `/install.php` again. It should say the installer is off. Leave it off. The lock file is `storage/framework/install.lock`. Remove that file only if you are the owner and you mean to open the installer again.

Terminal alternative, if you prefer not to use the page:

```text
/opt/cpanel/ea-php84/root/usr/bin/php artisan key:generate --force
```

Run that from the release folder (the one that contains `artisan`). Then run:

```text
/opt/cpanel/ea-php84/root/usr/bin/php artisan deploy:check
```

`deploy:check` prints the same list. `ok` can still have `warn` on the same run. `fail` means do not continue until that row is fixed. The command never prints the key or the database password.

If `php` on its own is an old version, always use the `ea-php84` path above. Some servers put it at `/usr/local/bin/ea-php84`.

## 8. Run only the new tables

Still in the release folder. This command prints the SQL and does not run it:

```text
/opt/cpanel/ea-php84/root/usr/bin/php artisan migrate --pretend --force
```

Read the output. Every statement should be a `CREATE TABLE` for a name that starts with `app_`, plus one `CREATE TABLE` for `migrations`. That `migrations` table is only a list of which files have run. It is not a `prox_` table.

Stop if you see `ALTER`, `DROP`, `RENAME`, or the text `prox_`. Do not continue. Nothing in this release should do that.

When the dry run looks right:

```text
/opt/cpanel/ea-php84/root/usr/bin/php artisan migrate --force
```

Do not run `migrate:fresh`, `migrate:rollback`, `db:wipe`, or `db:seed`.

These are the files, in order, and the tables they create. They only add tables. The `down` side of each file drops the same `app_` tables and is not used on the server.

| File | Tables it creates |
|---|---|
| `0001_01_01_000000_create_users_table.php` | `app_sessions` |
| `0001_01_01_000001_create_cache_table.php` | `app_cache`, `app_cache_locks` |
| `0001_01_01_000002_create_jobs_table.php` | `app_jobs`, `app_job_batches`, `app_failed_jobs` |
| `2026_11_05_000001_create_app_tables.php` | `app_posts`, `app_contact_messages`, `app_project_requests`, `app_password_resets` |
| `2026_11_05_000002_create_payment_intents.php` | `app_payment_intents` |
| `2026_11_05_000003_create_office_tables.php` | `app_role_permissions`, `app_audit_logs`, `app_recovery_codes`, `app_staff_devices`, `app_order_notes`, `app_saved_views`, `app_office_settings` |
| `2026_11_05_000004_create_gateway_settings.php` | `app_gateway_settings` |
| `2026_11_05_000005_product_and_mail_tables.php` | `app_notification_preferences`, `app_mail_logs`, `app_saved_briefs`, `app_disputes`, `app_account_requests`, `app_review_consents`, `app_job_heartbeats`, `app_deadline_alerts`, `app_staff_resets` |

Laravel also creates `migrations` to remember that these ran.

After it finishes, run `deploy:check` again. Migrations should say they have all run.

## 9. Folders and the storage link

In File Manager, these folders need to be writable by the web server. Set the folders to `775` and leave files at `644`:

```text
storage
storage/framework
storage/framework/cache
storage/framework/sessions
storage/framework/views
storage/logs
bootstrap/cache
```

Then, from the release folder:

```text
/opt/cpanel/ea-php84/root/usr/bin/php artisan storage:link
```

That creates a shortcut `public/storage`. Order files are not served from that shortcut. A customer downloads them through the account page. The shortcut is for journal images. If the host blocks shortcuts, the rest of the site still works and those images stay hidden until the host allows the link. Do not copy order files into `public`.

Then cache the settings, now that `.env` is filled in:

```text
/opt/cpanel/ea-php84/root/usr/bin/php artisan config:cache
/opt/cpanel/ea-php84/root/usr/bin/php artisan route:cache
/opt/cpanel/ea-php84/root/usr/bin/php artisan view:cache
```

If you change `.env` later, run `config:cache` again or the site keeps the old values.

## 10. Cron

In cPanel, open Cron Jobs. Add one job that runs every minute. Use the PHP 8.4 program:

```text
* * * * * /opt/cpanel/ea-php84/root/usr/bin/php /home/USERNAME/new-psw/artisan schedule:run >> /home/USERNAME/new-psw/storage/logs/cron.log 2>&1
```

Change both paths if your folder is not `new-psw`.

That one line is enough. Each minute it runs the schedule. The schedule runs the deadline check once an hour. While `QUEUE_CONNECTION=sync`, mail is handled during the page request (and, with `MAIL_MAILER=log`, it is only written to the log). The schedule does not start a second worker in that case.

When you later set `QUEUE_CONNECTION=database` and run `config:cache`, the same cron starts the worker, lets it finish what is waiting, and stops it:

```text
php artisan queue:work --stop-when-empty --max-time=55
```

Do not add that worker as a second cron job. Two workers at once can send the same message twice. The schedule already starts it once the queue is `database`.

## 11. HTTPS

In cPanel, open SSL/TLS Status. Include `new.paysomeonetowrite.com` and run AutoSSL. Wait until the certificate is issued. Open the site with `https://`. The padlock should be there.

`APP_URL` must be the `https` address. `SESSION_SECURE_COOKIE` is already true in the example file.

## 12. Payment addresses to register later

Do this only when you are ready to turn `SAFE_MODE` off. Until then the site does not call these companies, so you can register the addresses now or later.

Use the live address, not the sandbox, when you take real payments. Sandbox keys stay in `.env` until that day.

| Company | What to register |
|---|---|
| M-Pesa STK / Daraja callback | `https://new.paysomeonetowrite.com/payments/mpesa/callback` |
| M-Pesa B2C result | `https://new.paysomeonetowrite.com/payments/mpesa/b2c` |
| PayPal webhook | `https://new.paysomeonetowrite.com/payments/paypal/webhook` |
| PayPal IPN | `https://new.paysomeonetowrite.com/payments/paypal/ipn` |
| Stripe webhook | `https://new.paysomeonetowrite.com/payments/stripe/webhook` |
| Pesapal IPN | `https://new.paysomeonetowrite.com/payments/pesapal/ipn` |

Customers return to these after a card or PayPal payment. You do not register them as webhooks. They are already in the site:

```text
https://new.paysomeonetowrite.com/payments/paypal/return
https://new.paysomeonetowrite.com/payments/paypal/cancel
https://new.paysomeonetowrite.com/payments/stripe/cancel
```

Put the keys those companies give you into `.env`, then run `config:cache` again. Keep `SAFE_MODE=true` until one sandbox payment has been tried on purpose.

## 13. Mail, SPF, DKIM, and DMARC

Leave `MAIL_MAILER=log` until the records below are in place. Messages are written to `storage/logs` and are not delivered.

When you are ready, pick one provider (the host’s SMTP, Mailgun, or Amazon SES). Set `MAIL_MAILER` to `smtp`, `mailgun`, or `ses`, and fill in only that provider’s host, port, username, and password. Set `MAIL_FROM_ADDRESS` to a real address on `paysomeonetowrite.com`, such as `studio@paysomeonetowrite.com`. Do not use an address at `example.com`.

In cPanel, open Zone Editor for `paysomeonetowrite.com` and add what the provider shows you:

- SPF: one TXT record on the root domain that names the provider you actually send through. There should be only one SPF record.
- DKIM: the TXT record the provider gives you, usually a name like `k1._domainkey` or a name Mailgun or SES displays.
- DMARC: a TXT record on `_dmarc` . Start with `v=DMARC1; p=none; rua=mailto:studio@paysomeonetowrite.com` so you can read reports. Move to `quarantine` or `reject` only after real mail is arriving.

Then set `SAFE_MODE=false` only for mail if payments are still meant to stay off. `SAFE_MODE` turns mail and payments on together. If you are not ready for payments, leave it true and mail stays in the log. That is the safe choice.

Send the test message from Office, Settings, after an admin has signed in.

## 14. First admin sign-in and two-step setup

The new site does not create a new administrator. Use a person who is already in `prox_employee` with group 1 (admin).

Open `https://new.paysomeonetowrite.com/office/login`. Sign in with that email and the password they already use on the current site.

The office then asks for a two-step code. The first time, it shows a setup page. Scan the code with an authenticator app. Enter the six-digit code. Store the recovery codes somewhere that is not the website. The office opens after that.

A writer can sign in at the same page and only sees their own work. They do not see settings or disputes.

While `READ_ONLY_LEGACY=true`, signing in does not rewrite their password or their last-login time.

## 15. Smoke test

Tick these on the phone and on a computer.

- `https://new.paysomeonetowrite.com` opens the new home page, and the padlock is present.
- `https://new.paysomeonetowrite.com/up` and `/health` load. `/health` may say the schedule has never run until the cron has been in place for an hour.
- `/install.php` says the installer is off.
- A real customer can sign in. Their row in `prox_customer` is unchanged afterwards (check `password` in phpMyAdmin).
- They can open one of their orders. The price matches the current site.
- Calculating a price on `/order` works. With read-only still on, placing a new order must not create a `prox_order` row.
- The office home opens after the two-step code. The numbers match the order book. They are not sample figures.
- Office, Settings, the test email says it was not sent while safe mode is on. `app_mail_logs` shows `suppressed`.
- `deploy:check` prints no `fail` rows.

## 16. If you have to undo it

1. In Cron Jobs, delete the every-minute line.
2. Point the subdomain away from this `public` folder, or rename the release folder so the site no longer opens.
3. Do not drop, empty, or alter any `prox_` table.
4. The current site does not need a change. It was still using the same database.
5. If a `prox_` row did change, import the SQL backup from step 1. Do that only if you have confirmed a change. Importing an old backup over a database that has new orders from the current site will wipe those orders too. Prefer restoring a single row if you know which one changed.
6. The `app_` tables can stay. They do not affect the current site. Drop them only if you want the database to look as it did before this install, and drop only the tables listed in the table above, plus `migrations`.

To turn the new site back on later, put the folder back, add the cron again, and run `deploy:check`.
