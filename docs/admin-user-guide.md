# Filament Hotel

## Administrator User Guide

**Audience:** Administrators  
**Application:** Filament Hotel hospitality management system  
**Admin URL:** `/admin`  
**Guide date:** 29 August 2026

## 1. Administrator role at a glance

The administrator operates the hotel, conference, restaurant, and kitchen workflows through the Filament panel. The role can create and update standard users, manage bookings and restaurant operations, maintain menu and kitchen data, review payments, view reports, and inspect activity logs.

The administrator is intentionally below the super admin for access control. An administrator cannot edit or delete super-admin accounts or other administrator accounts, cannot manage roles and permissions, and does not have accountant-only payment-management/refund permissions. Escalate privileged access changes, refunds, and system-level exceptions to a super admin or accountant as appropriate.

## 2. Sign in and use the admin dashboard

1. Open the admin URL, normally `https://your-domain.com/admin`.
2. Sign in with your administrator email and password.
3. The root admin route redirects to **Admin Dashboard**.
4. If access is denied, confirm the account has the `admin` role and `view admin dashboard` permission.
5. Sign out from the profile menu when finished on a shared device.

The **Dashboard period** section appears above the widgets and now spans the page width. Choose **Daily**, **Weekly**, **Monthly**, **Quarterly**, or **Yearly**, or provide custom start and end dates. Date-aware widgets refresh to the selected period.

## 3. What administrators can access

| Area | Administrator capability |
|---|---|
| Dashboard | Admin service/finance summary, corporate billing, kitchen stock, operations, payments, and kitchen queue widgets |
| Users | View/create/edit standard staff and guest accounts; cannot edit or delete privileged accounts |
| Hotel operations | Create/update bookings, cancellations, check-in/check-out, rooms, and room types |
| Conferences | Manage conference rooms, facilities, and conference bookings |
| Restaurant | Manage restaurant setup, facilities, tables, reservations, menu, and food orders |
| Kitchen | Manage kitchen orders, production, recipes, ingredients, stock, and stock movements |
| Finance | View the Payments page and corporate receivables; accountant handles payment management/refunds |
| Reports | View operational reports and restaurant reports permitted by the panel |
| Audit/content | View activity logs and manage approved public content where enabled |
| System settings | Manage hotel branding, billing settings, corporate organisations, and promotions |

The exact sidebar visibility follows the seeded role permissions and resource authorization. A missing menu item is normally an authorization or navigation issue, not a missing record.

## 4. Recommended setup and handoff order

Use this sequence when preparing a new installation or property:

1. Confirm **Hotel Branding** is configured: name, logo, primary color, accent color, and footer/dark-mode color.
2. Confirm **Billing Settings**: VAT, NHIL, and service-charge percentages.
3. Create standard staff and guest accounts under **Users**.
4. Create **Corporate Organisations** and credit terms if deferred billing is offered.
5. Configure room types and individual rooms.
6. Configure conference rooms and facilities.
7. Configure the restaurant, facilities, tables, menu categories, and menu items.
8. Create **Promotions** and test a discount code.
9. Configure ingredients, recipes, kitchen stock, and production units.
10. Publish approved content and test each public page.

After setup, run one representative hotel booking, conference booking, table reservation, food order, payment, and cancellation test before opening the system to guests.

## 5. Users, departments, and guest accounts

### Create a standard user

1. Open **System → Users → Create**.
2. Enter first name, last name, email, phone number, department, password, status, and shift.
3. Optionally select an enabled **Corporate Account**. Leave it as **Personal / pay immediately** for a personal guest.
4. Save and verify the record.

The department maps to the application role. Standard administrator work should use manager, receptionist, accountant, kitchen manager, kitchen staff, or guest accounts as appropriate. A guest department also creates the related guest profile used by bookings and restaurant transactions.

### Edit or suspend a user

Open the user record and change status to **Suspended** when access must stop while preserving history. Administrators may edit standard users and guests, but privileged administrator and super-admin records are protected. Do not delete the currently signed-in account.

### Link a guest to a corporate organisation

1. Open **System → Corporate Organisations** and create or edit the organisation.
2. Set credit limit, payment-term days, and **Allow deferred payment**.
3. Edit the guest’s user record and choose that enabled organisation in **Corporate Account**.
4. Ask the guest to sign in again and verify that eligible checkout flows show the corporate billing option.

Corporate linking authorizes deferred billing; it does not mark a transaction as paid. Outstanding transactions remain in corporate receivables until a payment is recorded.

## 6. Hotel and conference operations

### Rooms and room types

Create room types before individual rooms. Maintain name, description, price, capacity, amenities, main image, and gallery images. Then add each room with its room number and room type. Mark rooms out of service through the appropriate status rather than deleting historical records.

The availability calendar uses active bookings and current/future dates. It should block only dates covered by a booking; expired holds are released by scheduled commands. If a date appears unavailable, check overlapping bookings, holds, status, and scheduler health before changing data.

### Hotel bookings

Use **Bookings** to review guest, room, check-in/check-out, status, payment status, balance, and corporate billing. Use the booking calendar for a cross-workflow view. Before changing dates, verify the replacement range is available. Use check-in/check-out actions according to reception procedure and preserve an audit trail for cancellations or exceptional edits.

### Conference rooms and bookings

Create conference facilities and venues with capacity, price, images, and gallery content. Review the conference booking list and calendar for date/slot conflicts. Check the payment or corporate state before confirming the booking and avoid deleting a record to resolve a conflict.

## 7. Restaurant operations

1. Create the restaurant record and facilities.
2. Create tables, reservation fees, and QR tokens if table ordering is enabled.
3. Create menu categories, then menu items with price, image, description, availability, and featured status.
4. Review table reservations for table, guest, date/time, status, and payment state.
5. Review food orders for items, quantities, discounts, charges, payment state, corporate status, and kitchen state.

The public restaurant index shows configured featured meals, selected facilities, tables, and gallery images. The dedicated menu, tables, and gallery pages expose the full catalogue.

### Promotions

Open **System → Promotions** to create discount codes. Select **Percentage** or **Fixed amount**, set minimum spend and start/end dates if needed, activate the promotion, and test it in a checkout. The discount is applied before service charge, VAT, and NHIL are calculated.

## 8. Kitchen stock and production

### Receive stock

Open **Restaurant → Kitchen Stock** and use the receive-stock action. Select the ingredient, quantity, unit, date, optional supplier, cost/notes, and save. Verify the movement in the stock ledger.

### Record production

Open **Kitchen Production → Create**. Select the menu item, production date, quantity produced, quantity wasted, and units where shown. Add each ingredient consumed for the batch. Saving deducts raw stock and adds finished portions to production balance. Quantity wasted must not exceed quantity produced.

### Monitor kitchen work

Use the kitchen order queue to monitor order status and handoff. Use Stock Movements and Kitchen Production Report to compare receipts, consumption, wastage, production, and sales. Investigate negative variance; it generally means sales/consumption exceed recorded production or available stock for the selected period.

## 9. Payments and corporate receivables

### Payments page

Open **Finance → Payments**. The page provides a full-width filter section and summary cards. Filter by:

- All payments
- Food orders
- Conference bookings
- Hotel bookings
- Table reservations

Choose daily, weekly, monthly, quarterly, or annual breakdowns, or set custom dates. The payment table and metrics share the same date/type scope. Administrators can review payment records, references, guests, methods, and statuses; payment edits and refunds follow the role permissions and should be escalated when unavailable.

### Corporate Receivables

Open **Corporate Receivables** to review unpaid hotel, conference, table-reservation, and food-order transactions. If your permission set includes settlement, use **Mark paid** only after receiving an approved offline payment and enter the amount/method accurately. Otherwise send the transaction to the accountant. A successful settlement updates the transaction and guest dashboard.

### Paystack

Paystack is used when a guest chooses immediate online payment. Never copy secret keys into notes or browser code. A browser return is not sufficient proof of payment; rely on server verification/webhook status and the recorded payment reference. Escalate duplicate, failed, or mismatched payments to the accountant.

## 10. Reports and dashboard interpretation

The Admin Dashboard includes role overview, service and finance stats, corporate billing, kitchen stock, operations, recent payments, and kitchen queue information. Related pages include:

- **Revenue Report** — collected revenue, pending values, refunds, and trends.
- **Occupancy Report** — room use and booking-night activity.
- **Guest Report** — guest counts and spend summaries.
- **Restaurant Report** — order and sales performance.
- **Kitchen Report** — production versus sales and stock signals.
- **Transaction Dashboard** — unified breakdown of hotel, conference, table, and food workflows.

Always use the same period when comparing reports. Transaction creation dates and payment-record dates can differ, so a payment may settle a transaction created in an earlier period.

## 11. Content, branding, and audit logs

Use **Hotel Branding** to maintain the name, logo, and shared color scheme. Use **Content** to publish or unpublish public sections. Published records are visible to guests; unpublished records remain for authorized administration use.

Use **Activity Logs** to verify who created, edited, cancelled, paid, refunded, or settled a record. Add concise operational notes without passwords, payment secrets, or other credentials.

## 12. Routine maintenance

The application schedules:

- Expired hotel booking holds every minute.
- Expired guest checkout holds every minute.
- Restaurant reservation reminders daily at 08:00.

Production must run the Laravel scheduler and queue worker. Check failed jobs, storage capacity, database backups, and Paystack webhook delivery. Before a bulk edit or migration, confirm a recent backup and test on staging when possible.

## 13. Troubleshooting

### A resource or dashboard is missing

Confirm your role and permission, then clear application/Filament caches through the approved deployment procedure. If the item is still absent, ask a super admin to verify panel discovery and authorization.

### A room, venue, or table is unavailable

Check active bookings/reservations for the exact dates or slot, stale holds, status, and scheduled release commands. Do not delete the conflicting record.

### A payment is not visible

Check transaction reference, payment status, linked transaction ID, server verification/webhook state, and whether it was corporate/offline. Send accounting discrepancies to the accountant.

### A food order is not in the kitchen queue

Check order status, payment/corporate state, menu/recipe configuration, stock, queue worker health, and failed jobs.

### Images or branding are missing

Verify the public disk/storage link, file visibility, accepted image type/size, and the relevant branding or resource record.

## 14. Administrator safety rules

- Use your own named account; never share credentials.
- Do not attempt to edit or delete privileged accounts.
- Reconcile operational totals with accounting before changing payment records.
- Prefer status changes and audit-preserving corrections over deletes.
- Keep Paystack keys and production secrets out of source control and notes.
- Back up before migrations or bulk updates.
- Escalate refunds, role/permission changes, orphaned payments, and database errors.

## Appendix — Escalation matrix

| Situation | Escalate to |
|---|---|
| Role, permission, or privileged-user change | Super admin |
| Refund or payment-method correction | Accountant / super admin |
| Corporate credit-limit or settlement dispute | Accountant / super admin |
| Room/venue availability conflict | Manager / reception lead |
| Menu, recipe, stock, or production discrepancy | Kitchen manager / manager |
| Paystack webhook or duplicate-payment issue | Accountant / super admin |
| Migration, backup, queue, scheduler, or production outage | Super admin / technical owner |
