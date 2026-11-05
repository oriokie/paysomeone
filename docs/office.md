# Staff and writer office

The office is at `/office`. It is a custom Blade application on the same design system as the customer desk. Filament stays at `/studio` for the journal only, and it now requires the same two-factor session.

## Role mapping

`prox_employee.employee_group` is the role, the same integers the current office uses.

| Group | Role | Office |
|---|---|---|
| 1 | Admin | Everything. The matrix cannot remove this. |
| 4 | Sub-admin | Orders, assignment, bids, deadlines, cancellations under a limit, messages, fines, customers. No roles, gateways, or global settings unless an admin grants the permission. |
| 2 | Editor | Review queue, files, approve or return, messages with support and writers. No customer contact details and no payment data. |
| 3 | Writer | Pool, bids, assigned orders, files, messages with support, earnings, payout requests, fines on their own row, news. No customer personal data. |
| 5 | Applicant | No office access. |

Permissions live in `app_role_permissions`. Until a role is saved, `App\Services\Office\Permissions::DEFAULTS` applies. Every staff write is copied to `app_audit_logs`.

## What is mirrored

Orders board (table and kanban), filters, saved views, bulk internal notes, order timeline, assignment, bid approval, customer and writer deadlines, files, internal notes, messages, editor approve/return, writer submit-for-review, fines and credits, staff suspend/category/pool, customers, payout requests with an M-Pesa row, news, notifications count, reports with CSV, rate card and coupons as readouts, gateway status with masked keys, activity log, TOTP and recovery codes, device log, session timeout.

## Gaps

- The old office has dozens of payment gateways, writer applications, and fine-type catalogues. This office records fines and payout requests and does not recreate every gateway screen.
- M-Pesa B2C is not called. Safe mode leaves the payout unpaid and says so. With safe mode off, approval marks the request paid in the database and still does not send B2C.
- There is no impersonation and no auth-key switch. There is no “view as”.
- Writer applications and the old applicant test stay out. Group 5 cannot sign in here.
- Customer messages are allowed only for roles with `messages.customer` (admin, and sub-admin by default). Writers message support, which matches the old rule that customer-facing writer chat is verified by the office.

## Cut-over

Keep `READ_ONLY_LEGACY=true` until this office should change live rows. `SAFE_MODE=true` blocks M-Pesa. Staff sign in at `/office/login` with their existing password, then must enrol an authenticator. Point people at `/office`, not the old `/authorize` URLs. The journal editor, if still needed, is `/studio` and uses the same staff session after two-factor.
