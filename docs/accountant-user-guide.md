# Filament Hotel

## Accountant User Guide

**Audience:** Accountants  
**Application:** Filament Hotel hospitality management system  
**Admin URL:** `/admin`  
**Guide date:** 29 August 2026

## 1. Accountant role at a glance

The accountant owns the payment, receivables, refund, and finance-reporting workflows in the Filament panel. The role can review transactions across hotel bookings, conference bookings, table reservations, and food orders; record or correct payments; process refunds when authorized; reconcile corporate credit; and review stock/production reports that affect financial controls.

The accountant is a finance-focused role. It is not a general administrator role: accountants do not manage user roles, create or edit corporate organisations, maintain promotions, or change hotel branding and billing configuration. Escalate those changes to a super admin, admin, or manager.

## 2. Sign in and use the accountant dashboard

1. Open the admin URL, normally `https://your-domain.com/admin`.
2. Sign in with your accountant email and password.
3. Open **Dashboards → Accountant Dashboard**.
4. If access is denied, confirm the account has the `accountant` role and `view accountant dashboard` permission.
5. Sign out from the profile menu when finished on a shared device.

The **Dashboard period** section spans the page width. Choose **Daily**, **Weekly**, **Monthly**, **Quarterly**, or **Yearly**, or provide custom start and end dates. Date-aware widgets use the selected range:

- **AccountantStats** — completed revenue, outstanding balance, refunds, and payment count.
- **AccountantReceivablesStats** — hotel, conference, table-reservation, food-order, and corporate-billed receivables.
- **Corporate billing** — credit exposure and outstanding corporate balances.
- **Revenue chart** — restaurant revenue trend for the selected period.
- **Recent payments** — latest payment records in the active scope.

The dashboard period is a reporting scope, not a settlement action. A payment created today can settle a booking created in an earlier period.

## 3. What accountants can access

| Area | Accountant capability |
|---|---|
| Dashboard | Accountant cash-position and receivables stats, corporate billing, revenue chart, and recent payments |
| Payments | View, create, edit, reconcile, and manage payment records within assigned permissions |
| Refunds | Process authorized refunds and preserve the reason/audit trail |
| Hotel transactions | View bookings and payment status; do not perform reception check-in/out tasks |
| Conferences | View conference bookings and their payment state |
| Restaurant | View table reservations and food orders, including payment/corporate state |
| Financial reports | View revenue, guest, restaurant, and transaction reporting permitted by the panel |
| Kitchen controls | View kitchen stock, stock movements, and production-versus-sales reports for cost/reconciliation checks |
| Corporate receivables | Review open credit transactions and record approved offline settlements |
| System settings | View billing settings; configuration changes are reserved for super admin/admin/manager |
| Users and setup | No role/permission, promotion, corporate-organisation, or branding administration |

Sidebar visibility follows the seeded permissions and resource authorization. A missing item usually indicates an authorization or navigation issue rather than missing data.

## 4. Daily reconciliation routine

Use this sequence at the start and end of each accounting day:

1. Set the accountant dashboard and Payments page to **Daily** (or the exact custom range).
2. Compare **Total collected**, **Pending amount**, **Refunded amount**, and **Payment count** with the approved cash, mobile-money, card, and bank statements.
3. Open Payments and filter one transaction type at a time: food orders, conference bookings, hotel bookings, and table reservations.
4. Confirm every payment has a recognizable transaction ID, guest, method, reference, amount, and status.
5. Review **Corporate Receivables** for unpaid credit transactions and follow up with the organisation contact.
6. Check refunds, failed payments, duplicates, and offline settlements; attach or record a useful reference.
7. Export or archive the approved reconciliation outside the application according to finance policy.

## 5. Payments page

Open **Finance → Payments**. The page uses a full-width filter area and matching summary cards.

### Filter the payment list

1. Choose a **Transaction type**:
   - **All payments**
   - **Food orders**
   - **Conference bookings**
   - **Hotel bookings**
   - **Table reservations**
2. Choose a **Breakdown**: daily, weekly, monthly, quarterly, or annually.
3. Optionally set custom **Start date** and **End date**.
4. Review both the table and the metrics; they share the same type and date scope.

The metrics are:

- **Payment count** — number of payment records in scope.
- **Total collected** — records with `paid` or `completed` status.
- **Pending amount** — records with `pending` or `unpaid` status.
- **Refunded amount** — records with `refunded` or `refund` status.

### Review or correct a payment

1. Open the payment record from the table.
2. Verify the linked transaction ID, guest, amount, method, transaction reference, and status.
3. For an approved offline receipt, record the actual method (`cash`, `momo`, `card`, or `bank_transfer`) and a bank/slip/reference number when available.
4. Save only after checking the source statement and the related booking/order/reservation.
5. Reopen the record to confirm the saved state and keep the activity log entry.

Do not create a second payment to compensate for an uncertain browser response. First check the existing reference and Paystack/webhook state.

## 6. Refunds and reversals

Refunds change financial totals and should follow the property’s approval policy.

1. Confirm the original payment, amount, guest, and transaction reference.
2. Confirm the refund reason and the approval record outside the application where required.
3. Use the available refund action and enter a concise reason.
4. Verify the payment is marked refunded and the related transaction reflects the correct balance/status.
5. Confirm the refund appears in the selected reporting period and in the activity log.

Never refund an unknown transaction or rely on a guest screenshot as proof of settlement. Escalate duplicate charges, partial refunds, chargebacks, and mismatched Paystack references to the super admin/technical owner.

## 7. Corporate receivables and offline settlement

Open **Finance → Corporate Receivables** to see active credit transactions across room bookings, conference bookings, table reservations, and food orders. The page shows outstanding balance, open transactions, corporate accounts, and receivables by service.

### Mark a corporate transaction paid

Use this action only after the finance team has received and verified an offline payment:

1. Locate the organisation, guest, transaction type/ID, and outstanding amount.
2. Select the actual payment method: cash, mobile money, card, or bank transfer.
3. Enter the receipt, bank, or transfer reference when available.
4. Click **Mark paid**.
5. Confirm the success message, payment record, transaction status, and guest dashboard state.

The action records a payment against the specific transaction; it does not change the organisation’s credit limit or mark unrelated transactions as paid. For partial or disputed settlement, stop and escalate rather than forcing a full payment.

## 8. Corporate billing controls

Corporate organisations, linked guests, credit limits, payment terms, and the deferred-payment toggle are managed by super admins, admins, and managers. Accountants consume these settings for reconciliation and settlement.

On the accountant dashboard, compare:

- **Corporate-billed receivables** with the channel totals.
- Active corporate accounts and linked guests.
- Outstanding exposure against the organisation’s approved credit limit.
- Billed activity for the selected date range.

If a credit transaction exceeds a limit, has the wrong organisation, or remains open after settlement, escalate it to the organisation manager and a super admin. Do not alter corporate master data from a payment correction.

## 9. Revenue and operational reports

Use the **Reports** group to support month-end and management reporting:

- **Revenue Report** — collected revenue, refunds, net revenue, outstanding balances by service, payment count, and payment-method totals.
- **Guest Report** — guest and spend summaries useful for revenue attribution.
- **Restaurant Reports** — order count, paid/outstanding values, payment rate, and average order value.
- **Transaction Dashboard** — unified hotel, conference, table, and food transaction breakdown.
- **Kitchen Production Report / Production vs Sales** — production, wastage, sales, and variance signals used for food-cost review.

Use one consistent date scope when comparing pages. Revenue reports are based on payment records, while receivable reports are based on transaction creation and payment state; the two totals will not always match for the same period.

## 10. Billing settings and promotions

The **Billing Settings** page is visible to accountants for review. It displays VAT, NHIL, and service-charge percentages used by checkout calculations. Only super admins, admins, and managers can change these rates. Before a rate change, obtain approval, record the effective date, and test one representative checkout.

The **Promotions** page is not an accountant management area. Promotions can be percentage-based or fixed-amount and are applied before service charge, VAT, and NHIL. Ask an admin/manager to correct a code or validity window, then re-run the affected reconciliation.

## 11. Paystack and online payment controls

Paystack applies when a guest selects immediate online payment. The authoritative record is the server-side verification/webhook result and the stored payment reference.

- Never place Paystack secret keys in notes, source control, or browser code.
- Match the Paystack reference, amount, currency, guest, and transaction type.
- Treat a browser return without a verified payment record as unconfirmed.
- Investigate duplicate, failed, pending, or amount-mismatch payments before editing anything.
- Ask the technical owner to check webhook delivery and queue failures when verification is delayed.

## 12. Kitchen and stock reconciliation

Accountants have read access to kitchen stock, stock movements, and production reports so food-cost figures can be reconciled with sales.

1. Compare ingredient receipts and stock movements for the selected period.
2. Review batches: menu item, production date, quantity produced, quantity wasted, units, and ingredients consumed.
3. Compare finished portions with food orders and identify unexplained negative variance.
4. Ask the kitchen manager to correct source production or waste records; do not adjust figures solely to make a report balance.

Negative variance generally means recorded sales/consumption exceed recorded production or available stock for the selected period. Check date boundaries, cancelled orders, recipe quantities, and late stock entries first.

## 13. Audit trail and data safety

Use **Activity Logs** when available to confirm who created, edited, paid, refunded, or settled a record. Keep notes factual and exclude passwords, Paystack secrets, card data, and unnecessary personal information.

- Use your own named account and sign out on shared devices.
- Preserve transaction history; prefer status changes and documented corrections over deletes.
- Back up before migrations or bulk updates.
- Reconcile before editing a payment amount or status.
- Escalate role/permission changes, refunds outside policy, orphaned payments, and database errors.

## 14. Troubleshooting

### A payment is missing from the list

Check the transaction-type filter, date range, payment creation date, transaction reference, and status. Then confirm the payment is linked through the correct foreign key (booking, conference booking, restaurant reservation, or restaurant order).

### A payment shows the wrong guest or transaction

Open the payment and compare its linked ID with the source transaction. Do not overwrite a record until the source booking/order/reservation and activity log have been reviewed.

### A corporate transaction remains outstanding after payment

Confirm the offline method/reference, mark the exact transaction paid, and verify that a completed payment was created. If it remains open, check for partial payments, duplicate records, or an invalid corporate payment state and escalate.

### Metrics do not match another report

Align the date range and understand the date field used by each report. Payments are filtered by payment `created_at`; receivables are filtered by the transaction’s creation date and current status.

### A report or dashboard is missing

Confirm the accountant role and permission, then follow the approved cache-clearing/deployment procedure. Ask a super admin to verify panel discovery and authorization if it remains unavailable.

## Appendix — Escalation matrix

| Situation | Escalate to |
|---|---|
| Role, permission, or privileged-user change | Super admin |
| Refund approval, chargeback, or payment-method correction | Finance lead / super admin |
| Corporate credit-limit, account-link, or settlement dispute | Manager / super admin |
| Paystack webhook, duplicate, or verification issue | Super admin / technical owner |
| Room, venue, or table availability conflict | Manager / reception lead |
| Menu, recipe, stock, or production discrepancy | Kitchen manager / manager |
| Billing-rate, promotion, branding, or public-content change | Admin / manager / super admin |
| Migration, backup, queue, scheduler, or production outage | Super admin / technical owner |
