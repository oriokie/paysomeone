# Email

Messages use one branded layout, HTML and plain text, in `resources/views/mail/studio.blade.php` and `studio-text.blade.php`. `App\Services\Desk\OutboundMail` decides whether a message leaves the server.

Essential mail always sends when safe mode is off: password resets, payment receipts, failed payments, wallet top-ups, dispute updates, staff sign-in alerts, staff invites, and the office test email. Order updates, messages, and deadline notes follow the preference for that address. A signed link in the footer opens `/email/preferences/{email}`. Customers can change the same switches under Settings.

Safe mode (`SAFE_MODE=true`) does not send. The attempt is stored in `app_mail_logs` with status `suppressed`. A password reset link is shown on the page instead. A preference that is off is stored as `skipped`. A thrown mailer error is stored as `failed` and the page does not crash. There is no bounce webhook until a provider is connected.

The office test button is on Settings and sends to the signed-in admin.

## What Edwin configures before real mail

1. Set `SAFE_MODE=false` only when a real mailer is ready.
2. Set `MAIL_MAILER` to `smtp`, `mailgun`, or `ses`, and the matching host, port, username, and password. The local default is `log`.
3. Set `MAIL_FROM_ADDRESS` to a real address on the studio domain, and `MAIL_FROM_NAME` to the studio name. `hello@example.com` must not go to production.
4. Publish DNS for that domain:
   - SPF that names the provider you actually send through.
   - DKIM from that provider (Mailgun or SES gives you the record).
   - DMARC, starting at `p=none` while you watch reports, then `quarantine` or `reject`.
5. Run the test button from office Settings and confirm the message arrives, including the plain-text part.
6. Point the scheduler at `php artisan schedule:run` every minute so `alerts:deadlines` runs hourly and the queue worker drains.

Customer sign-up sends a welcome. It does not block sign-in on an unverified address, because the existing customer table was never verified.
