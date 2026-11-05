# Putting the new site on cPanel

This is the path for a subdomain such as `new.paysomeonetowrite.com` on ordinary shared hosting (PHP 8.4, MariaDB 11.4). The current website stays where it is. This site uses the **same database** and, until you say otherwise, does not change it.

You do not need Node on the server. The compiled styles are already in `public/build`. You do need SSH or cPanel’s Terminal for Composer, once.

## 1. Make the subdomain

In cPanel, create the subdomain. Set its document root to the `public` folder of this project, for example:

```text
/home/USERNAME/new.paysomeonetowrite.com/public
```

If the host will only point the subdomain at a folder you cannot rename, put this project in that account and set the subdomain’s document root to the project’s `public` directory. Do not point the document root at the project folder itself. The `.env` file must stay **above** the folder the web can see.

## 2. Upload the code

Upload the project, without the `vendor` folder and without `.env`. On the server:

```bash
cd /home/USERNAME/new.paysomeonetowrite.com
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

PHP on the command line must be 8.3 or newer (`php -v`). If `php` is an older version, use the 8.4 binary cPanel provides, often `ea-php84` or a path under `/opt/cpanel`.

## 3. Point `.env` at the live database

In `.env`:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://new.paysomeonetowrite.com

DB_DATABASE=tutoress_paysomeone
DB_USERNAME=the_cpanel_database_user
DB_PASSWORD=the_database_password

READ_ONLY_LEGACY=true
SAFE_MODE=true
LEGACY_ALLOW_SEED=false
LEGACY_SITE_ID=1
```

Set `LEGACY_SITE_ID` to the `site_id` of this brand in the `prox_site` table. If you are unsure, look at that table in phpMyAdmin before changing the number. `LEGACY_ADMIN_SITE_ID` is the same value unless writer pay settings are stored under a different site id.

Do not run `php artisan db:seed` here. The seeder refuses this database name, and you should not override that.

## 4. Add only the new tables

```bash
php artisan migrate --force
php artisan filament:assets
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`migrate` creates `app_posts`, `app_contact_messages`, `app_project_requests`, `app_password_resets`, and the small Laravel tables (`app_sessions`, `app_cache`, `app_jobs`). It does not alter or drop any `prox_` table.

`filament:assets` publishes the staff office styles into `public`. It does not need Node.

Do **not** run `php artisan legacy:import-schema` against the live database. That command is for an empty local copy, and it refuses the live database name.

## 5. Check it

Open the subdomain. You should see the new home page and a preview banner. Sign in with a real client account. The password should work, and the client’s row in `prox_customer` should be unchanged (look at `password` and `last_login` before and after).

Calculate a price on `/order`. Save a brief. It should land in `app_project_requests` and **not** create a row in `prox_order`.

Open `/office` and sign in as an administrator (staff group 1 or 4). A writer account should not get in. An account that has the extra sign-in check switched on (the two-factor column) should not get in either, until that check is built.

## 6. When you are ready for this site to file orders

Do this in one sitting. Do not leave both sites creating orders.

1. Put the current site in maintenance, or stop its checkout, so it cannot create customers or orders for a few minutes.
2. Set `READ_ONLY_LEGACY=false` in this site’s `.env`.
3. Run `php artisan config:cache` again.
4. Place one test enquiry while signed in. Confirm it appears in the current office as an inquiry, with the price you were quoted.
5. Leave `SAFE_MODE=true` until email and each payment method have been tested on purpose. Turning it off allows real email. Payment calls are still not connected in this release, so a charge will not go out, but the switch should stay on until that work is finished.
6. Point the main domain at this site’s `public` folder only after that test enquiry looks right.

If something is wrong, set `READ_ONLY_LEGACY=true`, cache the config again, and turn the current site back on. Nothing in this release removes an old order.

## Cron

Shared hosting does not keep a queue worker running. This project uses `QUEUE_CONNECTION=sync`, so there is nothing to cron for mail queues. If a later release adds scheduled tasks, the single cron line is:

```text
* * * * * /usr/local/bin/php /home/USERNAME/new.paysomeonetowrite.com/artisan schedule:run
```

Use the PHP 8.4 binary if `php` is older.

## What not to copy onto the server

- The old order system’s updater, installer, or any address that signs a staff member in without a password.
- A second `.env` committed to git. The real file stays only on the server.
- A database user that is allowed to `DROP` or `ALTER`. A user that can `SELECT`, `INSERT`, `UPDATE`, and `DELETE` is enough. The read-only switch is in the application. A database user limited to `SELECT` is even safer for the preview, with a second user later when you allow writes. The `app_` tables need a user that can create them once, during `migrate`.
