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
| 5 | Applicant | No office access until an admin approves the application and the group becomes writer. |

Permissions live in `app_role_permissions`. Until a role is saved, `App\Services\Office\Permissions::DEFAULTS` applies. Every staff write is copied to `app_audit_logs`.

## What is mirrored

Orders board (table and kanban), filters, saved views, bulk internal notes, order timeline, assignment, bid approval, customer and writer deadlines, files, internal notes, messages, editor approve/return, writer submit-for-review, fines and credits, staff suspend/category/pool, customers, payout requests with an M-Pesa row, news, notifications count, reports with CSV, rate card and coupons as readouts, gateway status with masked keys, activity log, TOTP and recovery codes, device log, session timeout.

## Added after the first office pass

Gateway screens store enable, test mode, currency, and fees in `app_gateway_settings`. Secret keys stay in the environment and are masked. Writer applications are public at `/apply` (grammar check and a sample). Admins approve them into group 3 with a category and level, or reject them. Fine types are the old `prox_fine_type` catalogue. Writers can appeal a fine on their own row (`requested` / `requested_at`). A writer may message the customer; the message stays unverified until someone with `messages.customer` releases it, which is how the old office showed writer chat. Admins can open a read-only “view as” with a banner. It is logged and cannot write. With safe mode off and Daraja keys set, payout approval calls M-Pesa B2C. `MPESA_B2C_MOCK=true` records a conversation id and the `/payments/mpesa/b2c` callback marks it paid once. The old site still has modules this office does not operate: pre-written papers, SMS, affiliate payout screens beyond the referral balance, and the remote updater. Those stay off on purpose.

## Cut-over

Keep `READ_ONLY_LEGACY=true` until this office should change live rows. `SAFE_MODE=true` blocks M-Pesa. Staff sign in at `/office/login` with their existing password, then must enrol an authenticator. Point people at `/office`, not the old `/authorize` URLs. The journal editor, if still needed, is `/studio` and uses the same staff session after two-factor.
