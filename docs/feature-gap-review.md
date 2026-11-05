# Feature gap review

Reviewed against the desk, the office, and the public site on 5 November 2026. Academic ordering stays off for new briefs. Existing academic orders stay read-only in the order book. Nothing here changes a `prox_` table definition.

Effort is the size of the change. Priority is what a customer or a staff member hits first. Risk is what could touch live rows or send mail.

## Built in this pass

| Gap | Why it matters | Effort | Priority | Risk |
|---|---|---|---|---|
| Email preferences, plus mark notifications seen | People were getting every template with no way to opt out, and the notification list never cleared. | Small | High | Low. Preferences live in `app_notification_preferences`. Essential mail ignores them. |
| Revision limit of two | A delivered order could be sent back without a cap. | Small | High | Low. Count is on `prox_order_revision`. |
| File version label | Repeat uploads of the same name looked like duplicates. | Small | Medium | None. Counted in the page, no new column. |
| Public reviews only with consent | A rating of 4 or 5 was public even when the customer did not agree. | Small | High | Low. Consent is `app_review_consents`. Empty reviews page is the honest state. |
| Reorder from a saved brief | Placing a similar brief meant retyping it. | Small | Medium | Low. `app_saved_briefs`. Prefill only if the signed-in customer owns the brief. |
| Receipt PDF | Paid customers had no document they could save. | Small | High | None. Generated in the request, no new package. |
| Disputes and wallet refund | A paid order had no path to a manager decision. | Medium | High | Medium. Approving credits `prox_customer.wallet_balance` only when legacy writes are allowed. |
| Deadline and SLA mail, plus the due-soon list | Writers and customers were not told when a deadline was inside a day. | Medium | High | Low. One row per order per day in `app_deadline_alerts`. Hourly schedule. |
| Office KPIs: paid revenue, on-time, open disputes | The home page counted queues, not the book. | Small | Medium | None. On time is `—` when no order has both a delivery time and a customer deadline. |
| Account export and deletion request | There was no way to download or remove identifying profile fields. | Medium | High | Medium. Deletion anonymises name, email, and phone only when writes are allowed. Orders stay. |
| `/health`, `robots.txt`, Service JSON-LD | Operators and search engines had `/up` and a sitemap, and nothing else. | Small | Medium | None. |
| Staff password reset, sign-in alert, applicant mail, test-email button | Staff could not recover a password, and there was no way to see if mail was leaving the server. | Medium | High | Low. Reset tokens are in `app_staff_resets`. Safe mode shows the link on the page. |

## Already present, so not rebuilt

| Item | Where it already lives |
|---|---|
| In-app notification list | `prox_notification`, desk notifications page. This pass adds mark-as-seen. |
| Order messages | Desk and office message threads. This pass emails the assignee without the customer's email address. |
| Customer timeline | Placed → Finished on the order page. |
| Referrals and wallet credit | First paid order can credit the referrer. Affiliate balance is on the customer. |
| Sitemap | `/sitemap.xml`. |
| Open Graph title, description, type, and URL | `layouts/site.blade.php`. |
| Laravel health route | `/up`. `/health` adds the database and the deadline-job heartbeat. |
| Reports CSV | Office reports. |
| Password reset for customers | Kept. Safe mode still puts the link in the page status. |

## Left for later

| Gap | Why it waits | Effort | Priority | Risk |
|---|---|---|---|---|
| Confirming a customer email before sign-in | Existing `prox_customer` rows were never verified. A gate would lock the live book out. A welcome mail is sent on new registration instead. | Medium | Low until go-live | High if turned on against the current table. |
| Provider bounce webhooks | No Mailgun or SES account is connected. A failed send is logged. A later bounce needs the provider's webhook. | Medium | Medium | Low once a provider is chosen. |
| Real SMTP delivery | Safe mode and the local mailer cannot prove a message arrived. | Config | High before go-live | High if the from-address is wrong. |
| `og:image` | Needs a real studio image, not a generated one. | Small | Low | None. |
| Full GDPR erasure of order rows | Orders are the studio's record. This pass removes identifying profile fields and keeps the order. | Large | Medium | High. Needs a legal decision. |
| Academic ordering | Out of scope. New orders stay business and professional. | — | — | — |
