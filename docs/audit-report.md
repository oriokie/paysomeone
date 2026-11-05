# Audit report

Date: 5 November 2026. Scope: the whole application, with payment lifecycle, sidebar layout, payments UI, and the journeys that can be proven locally. `READ_ONLY_LEGACY` and `SAFE_MODE` stay on by default. No `prox_` table was altered. Tests use invented rows only.

## A. Executive summary

The four checkout providers that actually charge customers (M-Pesa, PayPal, Stripe, Pesapal) did not share one lifecycle. PayPal never captured a payment and verified webhooks with a header PayPal does not send. Pesapal marked an order paid without asking Pesapal whether the payment completed. Wallet top-ups were stored as spending, which the current office would show as money leaving. Those are fixed and covered by tests.

The desk and office sidebars were `position: sticky` and `height: 100vh` inside a page that grew with the content. On a long page the bar did not stay with the viewport, and anything past the fold was clipped. The shell is now the viewport: the sidebar stays put and scrolls its own list; the main column scrolls on its own. Measured on the gateway page, the sidebar stayed at `y = 0` and `height = 900` after the main column had scrolled 2,083px.

Verdict: **ready after minor fixes**. Local payment, permission, and browser tests pass. A live sandbox charge has not been made, and the extra legacy gateways (Paystack, Flutterwave, and the rest) are settings screens only.

## B. Payment integration

The old office writes a successful charge to `prox_payment` with `payment_method` set to the module name (`mpesa`, `paypal`, `stripe`, `pesapal`, `wallet`) and a `transaction_id`. It has no status column. Wallet direction is not a sign on the amount. `source` of `deposit` or `refund` is money in. Any other source is money out. Amounts stay positive.

| | M-Pesa (the pattern) | PayPal before | PayPal now | Stripe | Pesapal |
|---|---|---|---|---|---|
| Start | STK push, intent `pending`, provider id stored | Orders API, no id stored, return and cancel were the same URL | Intent stored, PayPal order id kept, return captures, cancel marks `cancelled` | Checkout Session. Success URL no longer says `?paid=1` | Redirect. Intent reference is the merchant reference |
| Confirm | Callback secret, then settle | Homemade `X-PayPal-Signature` HMAC | Transmission signature (cert must be on `paypal.com`) plus classic IPN posted back for `VERIFIED` | Stripe-Signature, unchanged | Status is read from GetTransactionStatus. Paid only on `COMPLETED` |
| Failure | Left the intent pending | Not recorded | `failed` or `cancelled` | Expired, async failure, and payment_intent failure mark `failed` | `FAILED`, `INVALID`, `REVERSED` mark `failed` |
| Duplicate | Same transaction id is ignored | Same, when it settled at all | Same | Same | Same |
| Refund | Not a card refund | Not handled | Intent `refunded`. Wallet is not credited, because the money returns to PayPal | `charge.refunded` does the same | Reversal marks `failed` if it was not already paid |
| Amount | Receipt amount checked | Not checked before settle | Mismatch marks `failed` and writes no `prox_payment` | Same check | Provider status is the gate |
| Records | `prox_payment.payment_method = mpesa` | Would have been `paypal` if it had settled | `paypal`, and IPN uses `txn_id` | `stripe`, `payment_intent` id | `pesapal`, tracking id |
| Wallet top-up | — | — | `source = deposit` | same | same |

A turned-off gateway in the office settings is refused before any HTTP call. Safe mode still blocks every outbound call. `CHECKOUT.ORDER.APPROVED` is ignored on purpose: the old button and the Orders API both treat capture, not approval, as the paid moment.

Not built: checkout for Paystack, Flutterwave, Skrill, Authorize.net, 2Checkout, Peach, IntaSend, FasterPay, EazzyPay, Jenga, Safepay, Secure Trading, Cybersource, and T1. The office can store their enable flag, test mode, currency, and fees. They do not charge. The old modules for those names exist; copying them was out of bounds, and they are not required for the four providers the desk offers.

## C. Issues by module

| Module | Sev | Issue | Fix |
|---|---|---|---|
| PayPal | P0 | Webhook check was not PayPal's. Return URL never captured. Cancel looked like success | Signature verification, capture on return, cancel route, IPN verify |
| Pesapal | P0 | A fake signature header settled the order with no status query | Query the provider. Settle only on `COMPLETED` |
| Wallet | P0 | Credits used `source = wallet`, which the old office treats as money out | Credits use `deposit` |
| M-Pesa | P0 | A declined STK left the intent pending, so the desk could not show failure | Result code other than 0 marks `failed` and writes nothing |
| All providers | P0 | A wrong amount threw inside the webhook, which invites retries, and a single charge was compared with the order total instead of `total_paid` | Mismatch marks `failed`. Paid when `total_paid` covers the total |
| Stripe | P1 | `?paid=1` could be read as success before the webhook. An unpaid session could settle | Neutral return URL. Unpaid sessions stay pending. Cancel route |
| Gateways | P1 | A disabled method could still be started | `gatewayClosed()` |
| Desk / office layout | P1 | Sticky `100vh` sidebar did not stay on long pages and clipped its own list | Viewport shell, internal nav scroll |
| Office mobile | P1 | Bottom bar kept only the first four links | Every permitted link is on a bar that scrolls inside itself |
| Desk mobile | P1 | Payments, referrals, and settings were only in the desktop sidebar | More sheet uses the same link list |
| Payments UI | P1 | No history, no status, no paging | `/account/payments` with search, method, status, page size, detail |
| Earnings | P1 | A writer opening earnings threw `Undefined array key` when the name map had no row | `Collection::get`, which returns null |
| Refunds | P2 | No automatic card refund from the desk | Provider refund notices update the intent only |

## D. Missing features

- Checkout for the extra gateways listed above.
- A customer-started card refund. Provider refunds are recorded. They do not move wallet balance.
- Browser proof of `/studio` after the Filament sidebar rule. The rule is in the panel head. It was not clicked through.
- A full crawl of every marketing link in this pass. Earlier browser tests covered the public pages, order, apply, and login.

## E. Journeys

| Role | Journey | Expected | Actual | Result | Fix |
|---|---|---|---|---|---|
| Customer | Sign in with bcrypt, MD5, salted SHA1 | Desk opens | Playwright, previous run, still in the suite | Pass | — |
| Customer | Place an article order with a percent coupon | Quote matches the rate card, order is filed | Playwright | Pass | Coupon label had required a positive discount |
| Customer | Pay by M-Pesa, decline, then a good callback | Failed intent writes nothing. A good callback writes one `prox_payment` | PHPUnit | Pass | Decline now sets `failed` |
| Customer | PayPal IPN and signed webhook | Unverified notice rejected. Verified notice pays once. Refund does not credit the wallet | PHPUnit with a faked PayPal verify and a local key | Pass | New |
| Customer | Pesapal IPN | `FAILED` does not pay. `COMPLETED` writes `payment_method = pesapal` | PHPUnit, HTTP faked | Pass | New |
| Customer | Stripe webhook | Signature required. Applied once | Existing PHPUnit | Pass | — |
| Customer | Payment history | Own rows only, filters, page size, page 2, other customer's detail is 403 | PHPUnit and the screenshots | Pass | New screen |
| Customer | Wallet top-up | `source = deposit`, balance increases | PHPUnit | Pass | Source changed |
| Writer | Bid, hidden customer email | Pool, bid, no email | Playwright | Pass | — |
| Sub-admin | Approve the bid | Writer assigned | Playwright | Pass | — |
| Editor | Return work, no email | Status revision, email absent | Playwright | Pass | Editor could not reopen the order after return |
| Admin | 2FA enrolment, matrix removes writer bidding, then restores it | Pool becomes 403, then works | Playwright | Pass | — |
| Admin | Payout under safe mode | Request stored, M-Pesa not called | Playwright and PHPUnit | Pass | — |
| Writer | Forbidden `/office/roles` | 403 | Playwright | Pass | — |
| Anyone | Login hammer and a post with no CSRF token | Throttled, 419 | Playwright | Pass | — |
| Staff | Long gateway page | Sidebar remains full height while the list scrolls | Measured, screenshot | Pass | Shell rewrite |

Session timeout for staff, password reset, and customer registration exist in the app and were not re-clicked in this pass. Their controllers and routes are the ones already covered by the earlier feature tests for auth.

## F. Automated testing

| Suite | Executed | Passed | Failed |
|---|---|---|---|
| PHPUnit | 45 | 45 | 0 |
| Assertions | 292 | | |
| Playwright journeys | 8 | 8 | 0 |
| Playwright quality (axe, overflow, new screens) | 2 | 2 | 0 |

Added this pass: `tests/Feature/PaymentLifecycleTest.php` (7 tests). Coverage percentage is not collected; Xdebug is not part of this run.

Blocked without credentials, all left on mocks:

- A real M-Pesa STK prompt and a real B2C payment.
- PayPal sandbox capture in a browser (the return handler is implemented; the HTTP call was not made to PayPal).
- Stripe Checkout and Pesapal redirect against their sandboxes.
- SMTP delivery.
- The old Proxim PHP office running beside this one. Column compatibility is asserted. It was not clicked.

## G. UI

- Sidebar: `.app-shell` / `.app-side` / `.app-side-nav` / `.app-main`. Same structure on the desk and the office. Filament gets a head style so `.fi-sidebar` is a viewport column and `.fi-sidebar-nav` scrolls.
- Payments: balance, filters, status badges, server pagination that keeps the query string, page sizes 10 / 25 / 50, a detail page, card list on a 390px screen (horizontal overflow measured at 0).
- Wallet amounts show `+` for `deposit` and `refund`, and `−` otherwise. The list is paginated.
- Mobile office nav includes every item the role is allowed to open.

Screenshots: `payments-desktop.png`, `payments-mobile.png`, `payments-pagination-desktop.png`, `payments-pagination-mobile.png`, `payment-detail-desktop.png`, `sidebar-long-page-desktop.png`, `sidebar-long-page-mobile.png`.

## H. Still open

- Confirm PayPal capture, Stripe Checkout, Pesapal, and M-Pesa STK against sandbox keys before taking a real payment.
- Decide whether the extra gateways should be built or removed from the customer-facing list. They are not on the customer form today.
- Click through `/studio` on a long resource page. The sidebar rule is in the panel head and was not opened in a browser.
- The payment rows in the screenshots are local `app_payment_intents` created for the preview account. They are not live charges and they did not write `prox_payment`.

## I. Readiness

**Ready after minor fixes.**

Evidence: 45 PHPUnit tests and 292 assertions passed, including the new lifecycle tests. The sidebar measurement and the payments screenshots are in the artifact folder. Safe mode and the read-only switch still default to on, so a deploy of this code does not call Safaricom or write live orders until those switches are turned off on purpose.

Not ready to take a live card or M-Pesa payment until one sandbox payment per provider has been watched through to a single `prox_payment` row.
