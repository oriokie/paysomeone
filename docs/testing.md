# Tests and coverage

PHPUnit covers `app/`. Playwright covers the browser journeys in `tests/e2e`. Neither suite calls a real payment provider or uses a live customer record. Gateway HTTP is faked. The database is `paysomeone_test` from `phpunit.xml`.

PCOV (`php8.3-pcov`) is the coverage driver. `pcov.enabled` must be on. There is no GitHub Actions workflow in this repo, so the gate is the Composer script, which fails if line coverage on `app/` drops under 85%.

```bash
php artisan test
composer test:coverage
npx playwright test
```

`composer test:coverage` runs `php artisan test --coverage --min=85`.

## Baseline, 5 November 2026

Measured with `php artisan test --coverage-clover` before the tests added for this pass. 45 tests, 292 assertions.

| Area | Covered | Statements | Line coverage |
|---|---:|---:|---:|
| app/ overall | 1,849 | 3,466 | 53.3% |
| Auth | 25 | 42 | 59.5% |
| Console | 0 | 33 | 0.0% |
| Events | 1 | 1 | 100% |
| Exceptions | 1 | 1 | 100% |
| Filament | 5 | 38 | 13.2% |
| Http | 486 | 1,370 | 35.5% |
| Jobs | 21 | 23 | 91.3% |
| Models | 221 | 363 | 60.9% |
| Providers | 104 | 107 | 97.2% |
| Services | 911 | 1,406 | 64.8% |
| Support | 74 | 82 | 90.2% |

## After this pass

Measured the same way. 71 tests, 561 assertions. Two small production fixes (a failed M-Pesa login now marks the intent failed, and a fine without an order id no longer reads a missing array key) added two statements, so the denominator is 3,468.

| Area | Covered | Statements | Line coverage |
|---|---:|---:|---:|
| app/ overall | 2,988 | 3,468 | 86.16% |
| Auth | 37 | 42 | 88.1% |
| Console | 9 | 33 | 27.3% |
| Events | 1 | 1 | 100% |
| Exceptions | 1 | 1 | 100% |
| Filament | 16 | 38 | 42.1% |
| Http | 1,214 | 1,370 | 88.6% |
| Jobs | 21 | 23 | 91.3% |
| Models | 271 | 363 | 74.7% |
| Providers | 104 | 107 | 97.2% |
| Services | 1,238 | 1,408 | 87.9% |
| Services/Payments | 539 | 650 | 82.9% |
| Services/Pricing | 295 | 315 | 93.7% |
| Services/Office | 93 | 104 | 89.4% |
| Services/Orders | 183 | 200 | 91.5% |
| Http/Controllers/Office | 623 | 746 | 83.5% |
| Http controllers outside the office | 562 | 594 | 94.6% |
| Auth | 37 | 42 | 88.1% |

`composer test:coverage` exited 0 against the 86.16% figure.

## Still under 85%, and why

- **Console (27.3%).** `legacy:import-schema` stops when `prox_customer` already exists, and it refuses the live database name. The branch that pipes the SQL file into `mysql` is not run, because that would rebuild the test schema.
- **Filament (42.1%).** The journal resource's form and table are Filament builders. They are not booted in these HTTP tests. The studio count widget is.
- **Models (74.7%).** What remains is mostly unused relation methods on legacy tables the desk does not write (schedules, extra services, PayPal's staging row). Relations that the new tests save are asserted.
- **Services/Payments (82.9%) and the office controllers (83.5%).** The overall `app/` figure is over 85%. What is left is PayPal's capture-already-paid and refund-webhook branches, a few Stripe safe-mode returns, and office screens that repeat the same permission check. They are not untested paths of a different kind. They were not filled with assertions that only execute a line.
