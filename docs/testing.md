# Tests and coverage

PHPUnit covers `app/`. Playwright covers the browser journeys in `tests/e2e`. Neither suite calls a real payment provider or uses a live customer record. Gateway HTTP is faked. The database is `paysomeone_test` from `phpunit.xml`.

PCOV (`php8.3-pcov`) is the coverage driver. There is no GitHub Actions workflow in this repo. The gate is Composer:

```bash
php artisan test
composer test:coverage
npx playwright test
```

`composer test:coverage` runs `php artisan test --coverage --min=93`.

## Before this follow-up

Measured on 5 November 2026, after the pass that first crossed 85%. 71 tests, 561 assertions.

| Area | Covered | Statements | Line coverage |
|---|---:|---:|---:|
| app/ overall | 2,988 | 3,468 | 86.16% |
| Filament | 16 | 38 | 42.1% |
| Console | 9 | 33 | 27.3% |
| Models | 271 | 363 | 74.7% |
| Services/Payments | 539 | 650 | 82.9% |
| Http/Controllers/Office | 623 | 746 | 83.5% |
| Http | 1,214 | 1,370 | 88.6% |
| Services | 1,238 | 1,408 | 87.9% |

## After this follow-up

`php artisan test --coverage-clover` on 5 November 2026. 81 tests, 738 assertions. `composer test:coverage` (`--min=93`) exited 0.

| Area | Covered | Statements | Line coverage |
|---|---:|---:|---:|
| app/ overall | 3,234 | 3,476 | 93.04% |
| Filament | 39 | 41 | 95.1% |
| Console | 40 | 40 | 100% |
| Models | 295 | 361 | 81.7% |
| Models, excluding eight unused classes | 295 | 322 | 91.6% |
| Services/Payments | 588 | 650 | 90.5% |
| Http/Controllers/Office | 733 | 746 | 98.3% |
| Http | 1,328 | 1,370 | 96.9% |
| Services | 1,292 | 1,408 | 91.8% |
| Auth | 37 | 42 | 88.1% |
| Jobs | 21 | 23 | 91.3% |
| Providers | 104 | 107 | 97.2% |
| Support | 76 | 82 | 92.7% |

The statement count moved from 3,468 to 3,476 because the importer now delegates the mysql process to `App\Console\SchemaMysql` (so tests can substitute a process) and the journal edit page gained a delete action.

## Still under the area target

**Models, 81.7% against an 85% aim.** The gap is unused legacy code, not skipped behaviour. These classes are never referenced outside their own file (39 statements, all uncovered):

- `AdditionalService`
- `CustomerWallet` (the desk uses `prox_customer.wallet_balance`, not this table)
- `ItemPricing`
- `OrderMeta`
- `OrderPdSchedule`
- `PaymentRequestOrder`
- `PaypalPayment` (PayPal receipts are written to `prox_payment`)
- `User` (customers and staff are `prox_customer` and `prox_employee`)

Unused relation methods left on otherwise-used models, also not called from app code: `AuditLog::employee`, `Configuration::site`, `Coupon::order`, `Customer::site`, `Customer::wallet`, `Deadline::site`, `Deadline::academicLevel`, `Discipline::prices`, `Employee::pool`, `Employee::editingOrders`, `Employee::bids`, `Employee::fines`, `Order::site`, `Order::currency`, `Order::requestedWriter`, `Order::coupon`, `Order::metas`, `Order::schedules`, `Order::additionalServices`, `OrderEmployee::order`, `OrderTip::order`, `PaymentRequest::lines`, `Site::configurations`, `Site::deadlines`, `WalletTransaction::currency`.

Excluding the eight unused classes, model line coverage is 91.6% (295/322). Relations the desk and office actually load (order files, messages, bids, writer, editor, deadline, ratings, wallet transactions, payment intent to order) are asserted.

Everything else in the requested set is on target: Filament 95.1%, console importer 100% with a mocked process (no SQL is imported), payments 90.5%, office controllers 98.3%.
