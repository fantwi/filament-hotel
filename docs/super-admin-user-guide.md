# Filament Hotel

## Super Admin User Guide

**Audience:** Super administrators  
**Application:** Filament Hotel hospitality management system  
**Admin URL:** `/admin`  
**Guide date:** 29 August 2026

## 1. What a super admin can do

The super-admin account has the broadest access in the application. It can configure the hotel, manage staff and guests, operate bookings and restaurant services, review payments and receivables, manage kitchen stock and production, publish website content, and view every operational and financial dashboard.

Use the super-admin account for configuration, access control, approvals, reconciliation, and exceptional corrections. Give day-to-day operational work to the appropriate department role whenever possible.

## 2. Sign in and open the correct dashboard

1. Open the Filament admin URL, normally `https://your-domain.com/admin`.
2. Sign in with your super-admin email and password.
3. The root admin route redirects you to **Super Admin Dashboard**.
4. If the dashboard is unavailable, confirm that the account has the `super_admin` role and the `view super admin dashboard` permission.
5. Use the profile menu to sign out when finished, especially on shared devices.

The dashboard period control appears at the top of the dashboard. Select **Daily**, **Weekly**, **Monthly**, **Quarterly**, or **Yearly**, or choose custom start and end dates. The widgets refresh to the selected period.

## 3. Recommended first-time setup order

Complete setup in this order so later workflows have the data they need:

1. **Hotel Branding** — set the hotel name, logo, primary color, accent color, and footer/dark-mode color.
2. **Billing Settings** — confirm VAT, NHIL, and service-charge percentages.
3. **Users** — create staff accounts, assign departments, and verify roles.
4. **Corporate Organisations** — create enabled organisations, credit limits, and payment terms if corporate billing is used.
5. **Rooms and Room Types** — configure room categories, prices, capacities, images, and galleries; then add individual rooms.
6. **Conference Rooms and Facilities** — configure venues, capacities, prices, images, and facilities.
7. **Restaurants, Facilities, Tables, Menu Categories, and Menu Items** — configure the restaurant catalogue and reservation resources.
8. **Promotions** — add active discount codes with percentage or fixed-amount rules.
9. **Ingredients, Recipes, and Kitchen Stock** — create raw ingredients and recipes before recording production.
10. **Content** — publish approved public-facing pages and sections.

## 4. Navigation and resource groups

The sidebar groups resources by operational purpose. Names can vary slightly with the current panel configuration, but the main areas are:

| Group | Typical resources and pages |
|---|---|
| Dashboards | Super Admin Dashboard, reports, booking calendar, room calendar/timeline, corporate receivables, transaction dashboard |
| System | Users, Hotel Branding, Billing Settings, Corporate Organisations, Promotions, Content |
| Hotel | Guests, bookings, rooms, room types |
| Conference | Conference rooms, facilities, conference bookings |
| Restaurant | Restaurant, facilities, tables, reservations, menu categories/items, food orders, ingredients/kitchen stock, stock movements, kitchen production |
| Finance | Payments and finance-oriented reports |
| Audit | Activity Logs |

Use the sidebar search where available instead of opening duplicate pages in multiple browser tabs.

## 5. Manage users, departments, and corporate links

### Create a staff or guest account

1. Open **System → Users → Create**.
2. Enter first name, last name, email, phone number, department, password, status, and shift.
3. Select a **Corporate Account** only when the user should be allowed to bill eligible transactions to that organisation. Leave it as **Personal / pay immediately** for an unlinked user.
4. Save, then reopen the record to verify the department and corporate link.

The department drives the default role mapping. Available operational roles include super admin, admin, manager, receptionist, accountant, kitchen manager, and kitchen staff. A guest department also creates the related guest profile used by bookings and orders.

### Edit or suspend access

Open the user record and change status to **Suspended** when access must stop without deleting the audit history. Change department only after confirming the new role and dashboard. Never delete the currently signed-in account; preserve at least one tested super-admin account.

### Link a guest to a corporate organisation

1. Create or edit the organisation under **System → Corporate Organisations**.
2. Set its credit limit, payment-term days, and **Allow deferred payment** toggle.
3. Edit the guest’s user account and select the enabled organisation in **Corporate Account**.
4. Ask the guest to sign out and back in before testing the deferred-payment option.

Linking does not pay an order. It allows the guest to choose **Bill to corporate account** during eligible checkout flows. Outstanding balances remain visible in corporate receivables until paid or cleared offline.

## 6. Configure hotel identity and billing

### Hotel Branding

Open **System → Hotel Branding**. Create the single setting if it does not exist, or edit the existing record. Upload a JPG, PNG, or WebP logo up to 5 MB; a square image works best. The hotel name and logo appear in guest navigation and footers. Color settings affect shared guest navigation, links, calls to action, and footer/dark-mode styling.

### Billing Settings

Open **System → Billing Settings** and maintain:

- VAT percentage
- NHIL percentage
- Service Charge percentage

These values are applied by the shared billing service to hotel bookings, conference bookings, restaurant table reservations, and food-order checkout totals. Test one checkout after changing rates and record the effective date in the operational change log.

## 7. Configure rooms and conference venues

### Room types and rooms

Create room types first. Set name, description, price, capacity, amenities, main image, and gallery images. Then create individual rooms and assign each to a room type. Verify room numbers are unique and that inactive or out-of-service rooms are not offered for booking.

Availability calendars consider current and future dates and block only dates covered by active bookings. Expired holds should be released by the scheduled command before investigating an apparent availability issue.

### Conference rooms and facilities

Create facilities and conference room types/venues with capacity, pricing, images, and gallery content. Verify that the gallery link and images display on the public conference page. Use the conference booking calendar to check slot conflicts before approving exceptional changes.

## 8. Configure restaurant services

1. Create the restaurant record and its facilities.
2. Create restaurant tables, reservation fees, and QR tokens where table ordering is used.
3. Create menu categories, then menu items with price, description, image, availability, and featured status.
4. Configure recipes and ingredients before kitchen production is recorded.
5. Use **Promotions** to create discount codes. Choose **Percentage** or **Fixed amount**, set minimum spend/date bounds if needed, and activate the code.

The public restaurant index shows configured featured meals, selected facilities, tables, and gallery content. The dedicated menu, tables, and gallery pages expose the complete catalogue.

## 9. Operate bookings, reservations, and orders

### Hotel and conference bookings

Use the relevant resource list to review status, payment state, guest, dates, room/venue, and balance. The booking calendar provides a cross-workflow view. For changes, verify the new dates/slot are free, preserve the original audit trail, and avoid editing a checked-in or completed transaction without an approved exception.

### Restaurant table reservations

Review the table, guest, reservation date/time, status, payment state, and fee. Confirm that the selected slot is available before accepting walk-ins or changing a reservation.

### Food orders

Review order items, quantities, totals, discount/charges, payment state, corporate status, and kitchen status. Corporate orders remain awaiting payment until settlement; do not mark them paid without a recorded payment or offline settlement action.

## 10. Payments and corporate receivables

### Payments page

Open **Finance → Payments**. Use the top filters to choose:

- All payments
- Food orders
- Conference bookings
- Hotel bookings
- Table reservations

Choose a daily, weekly, monthly, quarterly, or annual breakdown, or specify custom dates. The summary cards and payment table use the same type/date scope. Review payment count, collected amount, pending amount, and refunded amount together; investigate mismatches before editing records.

### Corporate Receivables

Open **Corporate Receivables** to see outstanding hotel, conference, table-reservation, and food-order balances. Use **Mark paid** only after receiving money through an approved channel. Enter the amount and method accurately; the action records an offline payment, clears the remaining balance, and updates the guest dashboard. Do not settle a cancelled, expired, or already-paid transaction.

### Paystack operations

Paystack is used for immediate online payment. Keep the secret key server-side, configure the webhook endpoint in Paystack, and monitor failed or duplicate webhook attempts. Never treat a browser return alone as proof of payment; rely on server verification/webhook handling and the recorded payment status.

## 11. Kitchen stock and production

### Receive stock

Open **Restaurant → Kitchen Stock** and use the receive-stock action. Select the ingredient, enter quantity and unit, date, supplier (optional), cost/notes, and save. Confirm that the resulting stock movement appears in the ledger.

### Record batch production

Open **Kitchen Production → Create**. Select the menu item, production date, quantity produced, quantity wasted, and units where shown. Add each ingredient consumed for the batch (for example, rice, chicken, oil, and spices). Saving production deducts raw-ingredient stock and adds finished portions to the production balance. Quantity wasted cannot exceed quantity produced.

### Reconcile

Use **Stock Movements** and **Kitchen Production Report** to compare receipts, consumption, wastage, production, and sales. Investigate negative variance rather than silently adjusting the ledger; it indicates recorded sales/consumption exceed recorded production or available stock for the selected scope.

## 12. Reports and dashboards

All time-filtered dashboards have the same period controls. The super-admin dashboard includes role overview, finance, operations, corporate billing, kitchen stock, restaurant revenue/order status, best-selling items, and kitchen queue widgets. Other useful pages include:

- **Revenue Report:** collected, pending, refunds, and financial trends.
- **Occupancy Report:** room usage and booking-night activity.
- **Guest Report:** guest counts and spend summaries.
- **Restaurant Report:** order, sales, and menu performance.
- **Kitchen Report:** production versus sales and stock signals.
- **Transaction Dashboard:** unified hotel, conference, table, and food transaction breakdown.

When comparing reports, use the same period and remember that transaction creation dates and payment-record dates can differ.

## 13. Content, galleries, and activity logs

Use **Content** to create or update public content. Set the publication toggle deliberately: published content is visible to guests; unpublished content is limited to authorized administration views. Check room/conference galleries after changing uploads.

Use **Activity Logs** to review who created, changed, paid, refunded, or settled records. Treat the log as the audit source for exceptional corrections. Do not store passwords, payment secrets, or other credentials in notes.

## 14. Scheduled operations and maintenance

The application schedules:

- Expired hotel booking holds: every minute.
- Expired guest checkout holds: every minute.
- Restaurant reservation reminders: daily at 08:00.

Production must run a Laravel scheduler process and a queue worker because notifications, mail, and background tasks may depend on them. Monitor failed jobs, storage capacity, database backups, and webhook delivery. Before a release, clear/rebuild caches only through the approved deployment procedure and verify migrations on staging first.

## 15. Troubleshooting checklist

### A dashboard or resource is missing

Confirm the user has the expected role and permission, clear Filament/config caches through the deployment procedure, and check that the panel provider discovers the page/resource.

### A room, venue, or table appears unavailable

Check active bookings/reservations for the exact dates or slot, look for an unexpired hold, and verify the scheduled release command is running. Do not delete records to force availability.

### A payment is not reflected

Check the transaction reference, server verification/webhook logs, payment status, and linked foreign key. For corporate payments, confirm whether the accountant recorded an offline settlement rather than expecting Paystack.

### A food order is not reaching the kitchen

Check order status, payment/corporate billing state, kitchen queue permissions, stock/recipe configuration, queue worker health, and failed jobs.

### Public images or branding are missing

Verify the public disk/storage link, file visibility, accepted MIME type/size, and the current Hotel Branding or resource record.

## 16. Super-admin safety rules

- Use named staff accounts; never share the super-admin password.
- Enable HTTPS and keep Paystack secrets out of source control.
- Prefer status changes and audit-preserving corrections over deletes.
- Reconcile payments and corporate receivables daily.
- Back up the database and uploaded files before migrations or bulk edits.
- Test one representative booking, reservation, order, payment, and refund path after major configuration changes.
- Keep at least one second, tested super-admin recovery account.

## Appendix — Role handoff guide

| Task | Recommended owner |
|---|---|
| Staff/guest account creation and role changes | Super admin or admin |
| Corporate organisation and guest linking | Super admin, admin, or manager |
| VAT/NHIL/service-charge configuration | Super admin, admin, manager, or accountant |
| Offline payment settlement/refunds | Accountant or authorized administrator |
| Daily room/venue/table operations | Reception/manager |
| Menu and kitchen stock/production | Kitchen manager, manager, or admin |
| Public content and branding | Super admin, admin, or manager |
| Cross-department reporting and escalation | Super admin |
