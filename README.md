# PaySomeoneToWrite

This is the new website for the writing studio. It is for businesses and professionals: articles, editing, proofreading, rewriting, business documents, and resumes. It does not offer student essays or academic assignments.

The current website can keep running while this one is tested on a subdomain such as `new.paysomeonetowrite.com`. Both can use the same database. By default this site only **reads** that database, so trying the new pages does not change a live order.

## What you are looking at

| Piece | What it is |
| --- | --- |
| Public site | The pages a client sees: home, services, pricing, the order form, reviews, journal, sign-in. |
| Staff office | A private area at `/office` for administrators. Journal posts can be edited here. Writers and payouts come in a later release. |
| Existing database | The tables that already start with `prox_`. Customers, orders, writers, prices, payments, and wallets live there. This project does not change those tables. |
| New tables | A few tables that start with `app_`. They hold the journal, contact messages, saved briefs, and password-reset links. They are added beside the old tables. They do not replace them. |

## Safety switches

These live in the `.env` file on the server. Both should stay **on** until you deliberately go live.

- `READ_ONLY_LEGACY=true` — the site may look at orders and sign people in, but it will not insert, update, or delete anything in a `prox_` table. A password is not rewritten, a new account is not created, and a new order is not filed in the live order book. A brief is still saved in an `app_` table so you do not lose the enquiry.
- `SAFE_MODE=true` — the site will not send email, SMS, or a payment. A reset link, when writes are allowed, is written to the log instead of being emailed.

There is no remote update, no licence check, and no address that signs someone in as an administrator without their password.

## Passwords

People can sign in with the password they already have.

The current site stores passwords in one of three ways, and checks them in this order:

1. A modern bcrypt hash (this is what it writes today).
2. An older hash: 9 characters of salt, then SHA1 of the password plus that salt.
3. A plain MD5 hash from the earliest accounts.

This site checks the same three. When it is allowed to write, a successful sign-in replaces an older hash with bcrypt. The current site already accepts bcrypt, so that person can still sign in there afterwards.

Bcrypt is used on purpose. A newer format called Argon2 would only work on the current site if that server’s PHP understands it. Bcrypt does not have that problem.

While `READ_ONLY_LEGACY` is true, sign-in still works and the stored password is left exactly as it is.

Password reset links are kept in an `app_` table, not in the columns the current site uses. The two sites will not overwrite each other’s reset links. While this preview is read-only, ask people to reset on the current website.

New accounts, when writes are allowed, are numbered the same way as today: the first customer is `452441`, and each new one is the highest existing number plus 2. Orders start at `11233` and step by the same gap the current site uses (normally 2). Do not let both sites create accounts or orders at the same time. The numbers can collide if they do.

## Prices

The order form does not invent a price. It reads the turnaround rows already stored (`prox_deadline`) and applies the same rules the current calculator uses. The rules are described on the Pricing page and locked in by automated tests. A short version:

- Articles are price per word, taken from the article turnaround’s rate, times the word count. A senior specialist adds 25%. A native English writer adds 30%. A percent coupon comes off the word subtotal only.
- Resumes, documents, editing, and rewriting are price per page. Single spacing is twice the double-spaced rate.
- Extras, only when chosen: progressive delivery 10% (offered from $200 and 5 days, included from $600 and 7 days), copy of sources 10% with a $14.95 minimum, specialist subject 20%, expert proofreading 25%, draft or outline 25%, a named writer 10%, samples $5, VIP support $9.99, plagiarism report $9.99.
- Coupons of “percent”, “fixed amount”, and “replace the rate” are honoured. A coupon type that the current calculator stores but never applies (the “half cut deadline” type) is not applied here either.

## Run it on your computer

You need PHP 8.3 or newer, Composer, Node, and MariaDB or MySQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create an empty database (not the live one), put its name in `.env`, then:

```bash
php artisan legacy:import-schema
php artisan migrate
```

`legacy:import-schema` loads `database/schema/legacy-structure.sql`. That file is structure only. It refuses to run if the database is named `tutoress_paysomeone`.

Optional demo rows (a sample client, an office login, a few rates, two reviews, three journal notes). This also refuses the live database name:

```bash
# .env must contain LEGACY_ALLOW_SEED=true
php artisan db:seed
```

The demo sign-in, only on that local copy, is:

- Client: `demo@paysomeonetowrite.test` / `Preview-Only-2026`
- Office: `office@paysomeonetowrite.test` / `Office-Preview-2026` at `/office`

Build the styles, then start the site:

```bash
npm install
npm run build
php artisan serve --host=127.0.0.1 --port=18473
```

Tests need the empty database `paysomeone_test` and the user in `phpunit.xml`:

```bash
php artisan test
```

## Go-live on the subdomain

See [docs/cpanel.md](docs/cpanel.md). Keep `READ_ONLY_LEGACY` and `SAFE_MODE` true for the whole preview. Turn writes on only when you are ready for this site to create enquiries in the live order book, and only after the current site is paused so the two sites are not creating orders together.

## Customer desk

Signed-in clients use `/account`. The desk follows the current site’s customer order list and order page: Recent, Unpaid, Finished, and Canceled; order tabs for details, files, messages, bids, revision, and payment; the same status names (Waiting for payment, New Paid, Looking for writer, Writer Assigned, and the rest).

The order form uses the current field order for article writing (type of content, title, instructions, keywords, deadline, words, writer category, English type, target audience, writing tone, writing style, content feel, coupon) and, for page work, pages, slides, charts, spacing, sources, and the same extras. Programming adds software and a deliverable. Academic level, citation format, subject, and student paper types are not on the form. An existing order that already has those fields still shows them, read-only.

Required columns that the form no longer collects are filled with the defaults in `App\Support\OrderDefaults`: academic level `0`, citation format `4` (“Not applicable”), subject `52` (“Professional”). Article orders store `order_type` `article`. Page and programming orders store `academic`, which is the table default the current page form leaves in place. The customer sees the service they chose, not that column. Programming is priced on the page rate card and the tool name is stored in `software`. The current programming form prices by task size; that price list is not used here. Payment is chosen on the order page, after the order exists.

- Orders, with the same statuses as the current site.
- An order page with the brief, files, messages to support or the assigned writer, writer bids, a revision request, a rating, and a tip.
- Files are stored outside the website folder. The path saved in `prox_order_file.source` is relative to `LEGACY_UPLOADS_PATH`. Point that setting at the current site’s uploads folder and both sites open the same files. A client can only open files on their own orders.
- Wallet balance and transactions, referrals, alerts, and profile or password.
- While `READ_ONLY_LEGACY` is true, the order form still shows a price and then refuses to file the order. Nothing is written to `prox_order`.
- When writes are allowed, “Place order” files a real order waiting for payment. “Save as an enquiry” files it as an enquiry. Order numbers still jump by 2, matching the current site.

Payments sit behind safe mode. M-Pesa (STK push and callback), PayPal, Stripe, and Pesapal each have sandbox settings in `.env.example`. A successful callback writes `prox_payment`. Paying from the wallet also writes `prox_wallet_transaction`. The same callback twice does not record the payment twice. Stripe, PayPal, and Pesapal callbacks are checked with a signature. The M-Pesa callback is checked with the secret in its address, which is the check Daraja gives you.

Emails use the text already stored in `prox_mail_template`. They are queued. While safe mode is on they are written to the log and not sent. On shared hosting, one cron line runs the queue. See `docs/cpanel.md`.

The staff and writer office is at `/office`. Roles come from `prox_employee.employee_group`. Two-factor sign-in is required. See [docs/office.md](docs/office.md). The journal editor remains at `/studio` for admins, after the same two-factor session. There is no impersonation.

## What comes next

1. **Staff office for the daily work.** Bids, assignments, fines, and writer payouts, including M-Pesa. Administrators and sub-administrators only, with the same password check. No impersonation link. Listen for `DeskEvent`.
2. **Cut-over.** Pause the current site, set `READ_ONLY_LEGACY=false` here, file one test order, pay it in the sandbox, confirm it in the current office, then point the main domain at this site. Leave the old files in place until you are sure.
