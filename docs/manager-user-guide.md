# Filament Hotel

## Manager User Guide

**Audience:** Managers  
**Application:** Filament Hotel hospitality management system  
**Admin URL:** `/admin`  
**Guide date:** 29 August 2026

## 1. Manager role at a glance

The manager coordinates hotel operations across rooms, conferences, restaurant service, and the kitchen. The role can supervise bookings, manage conference and restaurant workflows, maintain the menu, monitor kitchen orders and stock, manage corporate organisations and promotions, review operational reports, and inspect activity logs.

The manager is an operations role, not an accounting or system-administration role. Managers can view payments and billing settings, but they do not manage payment records or process refunds. They cannot manage roles and permissions, edit privileged users, or change hotel branding. Escalate payment corrections, refunds, privileged access, and branding changes to the accountant, admin, or super admin.

## 2. Sign in and use the manager dashboard

1. Open the admin URL, normally `https://your-domain.com/admin`.
2. Sign in with your manager email and password.
3. Open **Dashboards → Manager Dashboard**.
4. If access is denied, confirm the account has the `manager` role and `view manager dashboard` permission.
5. Sign out from the profile menu when finished on a shared device.

The **Dashboard period** section spans the page width. Choose **Daily**, **Weekly**, **Monthly**, **Quarterly**, or **Yearly**, or provide custom start and end dates. Date-aware widgets use the selected range:

- **ManagerStats** — arrivals, conference events, restaurant reservations, food orders, and active kitchen orders.
- **ManagerOperationsStats** — active stays, kitchen orders, production batches, stock movements, and corporate outstanding balance.
- **Corporate billing** — credit exposure and billed activity.
- **Kitchen production and stock stats** — production, variance, receipts, consumption, and waste signals.
- **Operations and order charts** — activity trends and restaurant order status.
- **Kitchen queue** — live confirmed, preparing, and ready orders.

The selected range scopes reporting widgets; it does not authorize changes outside your role.

## 3. What managers can access

| Area | Manager capability |
|---|---|
| Dashboard | Manager operations, corporate billing, kitchen, stock, charts, and live queue widgets |
| Hotel bookings | View and update booking records; coordinate room operations and exceptions |
| Conferences | Manage conference rooms, facilities, bookings, dates, and slots |
| Restaurant | Manage tables, reservations, orders, menu categories, and menu items |
| Kitchen | Manage kitchen orders, production batches, recipes, stock, and stock movements |
| Corporate accounts | Create/update organisations, credit limits, payment terms, and deferred billing toggle |
| Promotions | Create/update percentage or fixed-amount discount codes and validity windows |
| Payments | View payment records and status; no payment editing or refunds |
| Reports | View revenue, guest, restaurant, operational, transaction, and kitchen production reports permitted by the panel |
| Audit | View activity logs for operational changes |
| System settings | Edit billing rates; no hotel branding or role/permission administration |

Sidebar visibility follows the seeded permissions and resource authorization. A missing item usually indicates an authorization or navigation issue rather than missing data.

## 4. Recommended operating rhythm

### Start-of-shift checks

1. Set the dashboard to **Daily** or the exact shift date.
2. Review arrivals, active stays, conference events, restaurant reservations, and food orders.
3. Check the live kitchen queue for confirmed or delayed orders.
4. Review active kitchen production, low stock, and negative-variance signals.
5. Check corporate outstanding balances and any urgent organisation follow-up.

### End-of-shift handoff

1. Confirm room, conference, and table statuses are current.
2. Confirm restaurant orders are served or clearly handed over to the next shift.
3. Review stock receipts, consumption, waste, and production batches entered during the shift.
4. Record unresolved exceptions in the activity log or approved handoff channel.
5. Escalate refunds, payment mismatches, and access requests instead of changing them outside your permissions.

## 5. Hotel room operations

### Maintain rooms and room types

Create room types before individual rooms. Maintain name, description, price, capacity, amenities, main image, and gallery images. Add each room with its room number and room type. Use an out-of-service status for maintenance rather than deleting a room with history.

### Review and update bookings

1. Open **Bookings** and verify guest, room, check-in/check-out, status, payment state, and corporate account.
2. Update dates or room assignment only after checking the availability calendar.
3. Use the booking calendar to see room, conference, and table activity together.
4. Coordinate check-in/check-out with reception; the manager role does not replace reception’s check-in/out permissions.
5. Preserve the record and audit trail when cancelling or correcting an operational error.

Availability should block only booked dates and active holds. Expired holds are released by scheduled commands. If an apparently free date is unavailable, check overlapping records, holds, room status, and scheduler health before changing data.

## 6. Conference operations

1. Configure conference venues, facilities, capacity, prices, images, and gallery content.
2. Review conference bookings by date, slot, guest, status, payment state, and corporate account.
3. Confirm the requested slot is available before approving an update.
4. Coordinate setup, facilities, and handoff with the responsible team.
5. Keep cancellations and exceptional changes auditable; do not delete historical bookings to resolve a conflict.

## 7. Restaurant operations

### Menu and table setup

1. Maintain restaurant facilities and table records.
2. Create menu categories, then menu items with price, image, description, availability, and featured status.
3. Keep QR/table identifiers and capacity accurate when table ordering is enabled.
4. Create promotions only when the approval and validity window are confirmed.

### Reservations and food orders

1. Review table reservations for table, guest, date/time, status, payment state, and corporate billing.
2. Review food orders for order number, items, quantities, table/channel, status, payment state, and kitchen handoff.
3. Use the live kitchen queue to move orders from confirmed to preparing, ready, and served when the workflow requires it.
4. Escalate payment failures or refunds to the accountant; do not mark a payment settled without evidence.

The public restaurant pages expose featured meals, tables, gallery, and the full menu. Changes to availability and featured status affect what guests can order.

## 8. Kitchen stock and production

### Receive stock

Open **Restaurant → Kitchen Stock** and use the receive-stock action. Select ingredient, quantity, unit, date, optional supplier, cost/notes, and save. Confirm the movement appears in the stock ledger.

### Record a production batch

1. Open **Kitchen Production → Create**.
2. Select the menu item from the menu-items table.
3. Enter production date, quantity produced, quantity wasted, and units where shown.
4. Add each ingredient consumed by the batch.
5. Save and verify raw stock deductions and finished portions added to the production balance.

Quantity wasted must not exceed quantity produced. Record batch quantities and ingredients promptly so food-cost and variance reports remain meaningful.

### Investigate variance

Use Production vs Sales and stock movements to compare ingredients received, consumed, wasted, portions produced, and food sold. Negative variance generally means recorded sales/consumption exceed recorded production or available stock for the selected period. Check date boundaries, cancelled orders, recipe quantities, and late entries before requesting a correction.

## 9. Corporate organisations and deferred billing

Managers can create and maintain corporate organisations:

1. Open **System → Corporate Organisations → Create**.
2. Enter organisation name and contact details.
3. Set the credit limit and payment-term days.
4. Enable **Allow deferred payment** only after approval.
5. Save, then link guest users to the organisation through their user record.

Corporate billing lets linked guests choose credit instead of immediate Paystack payment. It creates an outstanding receivable; it does not mean the transaction is paid. Review the organisation’s exposure on the manager dashboard and Corporate Receivables page, then coordinate settlement with accounting.

Do not raise a credit limit to bypass a failed payment or resolve an outstanding balance. Record the business approval and escalate disputed or overdue accounts.

## 10. Promotions and checkout charges

Open **System → Promotions** to create discount codes. Choose **Percentage** or **Fixed amount**, configure minimum spend and start/end dates, activate the promotion, and test it in a checkout. Discounts are applied before service charge, VAT, and NHIL.

Open **System → Billing Settings** to maintain VAT, NHIL, and service-charge rates. Managers can edit these rates, but changes should have an approved effective date and a test transaction. Coordinate the change with the accountant because it affects all four checkout flows: hotel bookings, conference bookings, table reservations, and food orders.

## 11. Reports and dashboard interpretation

Use the **Reports** group and manager dashboard to monitor:

- **Revenue Report** — collected revenue, refunds, net revenue, and outstanding balances.
- **Guest Report** — guest and spend summaries.
- **Restaurant Reports** — order volume, payment rate, and outstanding orders.
- **Transaction Dashboard** — hotel, conference, table, and food activity in one view.
- **Kitchen Production Report / Production vs Sales** — food production, waste, stock, sales, and variance.

Use one consistent date range when comparing pages. Operational widgets often use event or creation dates; payment reports use payment-record dates. A payment received today may settle a transaction created earlier.

## 12. Payments and settlement boundaries

Managers can view Payments and inspect references, methods, guests, transaction IDs, and statuses. They cannot manage payment records or process refunds under the seeded manager permissions.

- Do not edit a payment amount or status to make a dashboard balance.
- Send refund requests, duplicate charges, chargebacks, and method corrections to the accountant.
- For corporate offline settlement, direct the accountant to Corporate Receivables and provide the approved receipt/reference.
- Treat Paystack server verification/webhook status as authoritative rather than a browser return screen.

## 13. Activity logs and public content

Use **Activity Logs** to confirm who created, edited, cancelled, or advanced an operational record. Keep notes factual and exclude passwords, payment secrets, card data, and unnecessary personal information.

Managers do not manage hotel branding or user roles/permissions. Ask an admin or super admin to change the hotel name/logo, shared colors, privileged users, or role assignments. Public content and gallery updates should be approved before publication.

## 14. Troubleshooting

### A dashboard widget is empty or missing

Confirm the selected date range, role permission, and expected data source. If it remains missing, ask a super admin to verify panel discovery and authorization.

### A room, conference slot, or table appears unavailable

Check active bookings/reservations, stale holds, date/slot overlap, status, and scheduled release commands. Do not delete the conflicting record.

### A kitchen order is not progressing

Check order status, payment/corporate state, menu item, recipe, stock, queue worker health, and failed jobs. Keep the kitchen handoff visible to the next shift.

### A promotion or charge is wrong at checkout

Verify promotion dates/type/value and Billing Settings rates. Re-test the affected flow and notify accounting before changing a rate or code that has already been used.

### A payment or refund needs correction

Collect the transaction ID, payment reference, guest, amount, and evidence, then escalate to the accountant. Do not create a duplicate payment.

## 15. Manager safety rules

- Use your own named account and sign out on shared devices.
- Preserve operational history; prefer status changes and documented corrections over deletes.
- Do not change payment records or issue refunds outside your permissions.
- Back up before migrations or bulk updates.
- Keep Paystack keys and production secrets out of source control and notes.
- Confirm approved credit, promotion, and billing-rate changes before publishing them.

## Appendix — Escalation matrix

| Situation | Escalate to |
|---|---|
| Role, permission, privileged-user, or hotel-branding change | Admin / super admin |
| Refund, duplicate payment, or payment-method correction | Accountant / super admin |
| Corporate credit-limit or settlement dispute | Accountant / super admin |
| Room, venue, or table availability conflict | Reception lead / super admin |
| Menu, recipe, stock, or production discrepancy | Kitchen manager / super admin |
| Paystack webhook or verification issue | Accountant / technical owner |
| Billing-rate change with financial impact | Accountant / admin |
| Migration, backup, queue, scheduler, or production outage | Super admin / technical owner |
