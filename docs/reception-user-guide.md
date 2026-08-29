# Filament Hotel

## Reception / Receptionist User Guide

**Audience:** Receptionists and front-desk staff  
**Application:** Filament Hotel hospitality management system  
**Admin URL:** `/admin`  
**Guide date:** 29 August 2026

## 1. Reception role at a glance

Reception staff manage the guest-facing arrival and departure desk. The role can create and update hotel bookings, cancel bookings, check guests in and out, manage conference bookings, and manage restaurant table reservations. The reception dashboard highlights arrivals, departures, checked-in guests, conference activity, restaurant reservations, and unpaid arrival balances.

Reception is an operational role. It does not manage users and roles, payment records, refunds, promotions, corporate accounts, menu items, kitchen orders, or system settings. Send payment, account, menu, kitchen, and corporate-credit questions to the appropriate manager, accountant, admin, or super admin.

## 2. Sign in and use the reception dashboard

1. Open the admin URL, normally `https://your-domain.com/admin`.
2. Sign in with your receptionist email and password.
3. Open **Dashboards → Reception Dashboard**.
4. If access is denied, confirm the account has the `receptionist` role and `view reception dashboard` permission.
5. Sign out from the profile menu when finished on a shared device.

The **Dashboard period** section spans the page width. Choose **Daily**, **Weekly**, **Monthly**, **Quarterly**, or **Yearly**, or provide custom start and end dates. The widgets use that range:

- **ReceptionStats** — hotel arrivals, hotel departures, conference bookings, and restaurant reservations.
- **ReceptionDeskStats** — guests currently checked in, pending arrivals, pending departures, and unpaid arrival balance.
- **ReceptionArrivals** — an auto-refreshing arrival list with guest, room, room type, time, payment status, and booking status.

For a live front desk, use **Daily** and keep the arrival list open. The date range is a reporting view; it does not change a booking’s dates.

## 3. What reception can access

| Area | Reception capability |
|---|---|
| Dashboard | Reception arrivals, departures, checked-in count, and reservation stats |
| Hotel bookings | View, create, update, cancel, check in, check out, and coordinate walk-ins |
| Guest account at booking | Create a walk-in guest account from the booking guest selector |
| Conference bookings | View and manage conference bookings and slots where exposed by the panel |
| Table reservations | View, create, update, and manage restaurant table reservations |
| Booking calendar | View hotel stays, conference bookings, and restaurant reservations together |
| Payments | View payment state on transactions; payment recording/refunds follow finance permissions |
| Users and roles | Not available; ask an admin or super admin |
| Food orders and kitchen | Not available; hand off to restaurant/kitchen staff |
| Corporate accounts/promotions | Not available; ask a manager/admin |
| Reports and settings | Only dashboard information and pages exposed to the role |

Sidebar visibility follows the seeded permissions and resource authorization. A missing item usually indicates an authorization or navigation issue rather than missing data.

## 4. Front-desk shift routine

### Start-of-shift checks

1. Set the dashboard to **Daily** and confirm today’s date.
2. Review **Today’s Hotel Arrivals** and compare names, rooms, arrival times, and payment status with the handoff.
3. Check pending departures and guests currently checked in.
4. Review conference bookings and table reservations for the day.
5. Flag unpaid arrival balances to the accountant or manager before releasing keys.

### End-of-shift handoff

1. Confirm every arrival is pending, checked in, cancelled, or otherwise explained.
2. Confirm departures and room status have been handed to housekeeping/management.
3. Record no-shows, late arrivals, room moves, and unresolved guest requests in the approved handoff channel.
4. Hand payment discrepancies, refunds, corporate-credit questions, and duplicate charges to accounting.
5. Sign out on shared front-desk devices.

## 5. Create a hotel booking

1. Open **Reservations → Bookings → Create**.
2. Select an existing guest, or use **Create walk-in guest account** in the guest field.
3. For a new walk-in account, enter first name, last name, unique email, phone/ID details, and a temporary password that meets the form rules.
4. Select the room and choose check-in and check-out dates.
5. Confirm the calculated nights and total price.
6. Choose **Reservation (Future)** for a normal upcoming stay or **Check-in Now** for an approved walk-in arrival.
7. Save, then reopen the record to verify guest, room, dates, status, payment state, and corporate/personal context.

The room selector validates date overlap. Availability should block only dates covered by active bookings or holds, and past dates should not be offered as new availability. If a free room is rejected, check overlapping records, stale holds, room status, and scheduler health before changing the booking.

## 6. Create a walk-in guest account

The booking form can create a guest account without leaving the booking flow:

1. In the **Guest** selector, click **Create walk-in guest account**.
2. Enter the guest’s legal name and a unique email address.
3. Add phone and ID details when available.
4. Set a temporary password and confirmation; give the guest the approved sign-in instructions securely.
5. Save the account and continue the booking.

The account is created as a guest profile and can be used by the guest dashboard. Do not write passwords in notes or share them with another staff member.

## 7. Update or cancel a booking

### Update a pending booking

1. Open the booking record and choose **Edit**.
2. Change only the details confirmed by the guest or manager.
3. Re-check room availability and the calculated total.
4. Save and verify the updated status/payment information.

Reception can edit bookings while they are pending. Privileged edits to other statuses are reserved for admin/super admin workflows. Never overwrite a payment record to correct a date or room mistake.

### Cancel a booking

1. Open the booking action menu and choose **Cancel Booking**.
2. Enter a concise reason and confirm.
3. Confirm the status changed to cancelled and that the dates are released for future availability.
4. Notify accounting if money was paid or a refund may be due.

Use status changes so the history remains auditable. Do not delete a booking to resolve a conflict.

## 8. Check-in and check-out

### Check in

1. Confirm the guest identity, booking, room, dates, and payment/corporate state.
2. Confirm the room is ready through the approved housekeeping handoff.
3. For an approved walk-in, use **Walk-in Check-in** or create the booking with **Check-in Now** as exposed by the panel.
4. For a confirmed reservation, use **Check-In**.
5. Verify the status becomes `checked_in` and record any approved key/guest notes outside sensitive credentials.

If the arrival is unpaid, follow the property’s payment policy and ask the accountant/manager to confirm whether payment or corporate billing is acceptable before issuing access.

### Check out

1. Confirm the guest, room, folio/payment state, and departure date.
2. Resolve outstanding charges with the accountant or manager.
3. Use **Check-Out** on a checked-in booking.
4. Confirm the status becomes `checked_out` and hand the room to housekeeping.

## 9. No-shows, late arrivals, and extensions

- Use **Mark No Show** only when the arrival date/time has passed and the property’s no-show policy is met.
- Contact the manager before changing a no-show decision where payment, a corporate account, or a late-arrival guarantee is involved.
- A checked-in stay extension must pass the room overlap check. If the requested dates conflict, offer an approved alternative and do not force the date.
- Expired holds are released by scheduled commands. Do not manually delete a hold to make a room appear available.

## 10. Conference bookings

Reception can manage conference bookings where the conference workflow is exposed in the panel:

1. Confirm the guest or organisation, venue, booking date, slot, capacity, and facilities.
2. Check the conference calendar for an overlapping booking before creating or changing a record.
3. Verify payment or corporate billing state and communicate any amount due.
4. Coordinate room setup and handoff with the conference/operations team.
5. Preserve cancellation and change history.

If the conference booking screen is missing, report the navigation/permission issue to a manager or admin rather than creating a duplicate through another route.

## 11. Restaurant table reservations

1. Open **Restaurant → Table Reservations**.
2. Create or edit the reservation with restaurant, table, guest/contact details, date, time, number of guests, and special requests.
3. Confirm the table is free for the exact slot.
4. Set the appropriate reservation status and review payment status.
5. Notify the restaurant team of arrivals, changes, cancellations, and no-shows.

The restaurant calendar/availability logic should block only booked slots. If a slot appears unavailable despite being open, check overlapping reservations, stale holds, table status, and the exact date/time before changing the record.

## 12. Booking calendar

Open **Reservations → Booking Calendar** to see hotel stays, conference bookings, and restaurant reservations together.

- Use month view for planning and week view for daily capacity.
- Use the arrows to move between periods and **Today** to return to the current date.
- Click an event to open its record when a URL is available.
- Use the colour guide to distinguish pending, confirmed, checked-in, completed/other, and cancelled items.
- If the calendar library or events fail to load, refresh once and contact an admin if the error persists.

Do not treat the calendar as the source of payment truth. Confirm payment status on the transaction record.

## 13. Payments, corporate billing, and food orders

Reception can see payment status on relevant bookings and reservations, but finance permissions control payment edits and refunds. Do not mark a transaction paid without evidence.

- Send refunds, duplicate charges, failed Paystack payments, and offline settlement questions to the accountant.
- Send corporate-credit linking, credit-limit, or deferred-payment questions to a manager/admin.
- Send food-order status and kitchen handoff questions to restaurant/kitchen staff.
- When a guest chooses corporate billing, explain that the transaction is awaiting payment and remains outstanding until accounting settles it.

## 14. Reports and handoff information

The reception dashboard is the primary front-desk report. Managers and accountants can provide broader revenue, occupancy, guest, restaurant, transaction, and payment reports. When escalating, include:

- Booking/reservation/order ID.
- Guest name and contact.
- Date, time, room/table/venue, and current status.
- Payment status and any visible reference.
- What the guest is requesting and what action has already been taken.

## 15. Troubleshooting

### A room is unavailable for an apparently free date

Check date overlap, active holds, room status, past-date rules, and scheduled hold release. Do not delete another guest’s booking.

### The arrival list is empty

Confirm the dashboard period and check-in dates. Verify the booking status is pending or confirmed and refresh the dashboard.

### A booking action is missing

Check the booking status and your receptionist permission. Actions such as refund, privileged edits, and payment management may be intentionally hidden.

### A guest cannot be found

Search by the guest’s full name or email. If this is a walk-in, create the account from the booking form and avoid duplicate email addresses.

### A reservation or conference slot conflicts

Check the exact date/time, overlapping active records, stale holds, and cancellation status. Escalate unresolved conflicts to the manager.

## 16. Reception safety rules

- Use your own named account; never share credentials.
- Verify identity before revealing booking or room details.
- Do not store passwords, card data, or Paystack secrets in notes.
- Preserve booking history; use status changes instead of deletes.
- Confirm payment/corporate state before issuing keys or confirming exceptions.
- Escalate refunds, payment mismatches, corporate credit, privileged access, and database errors.

## Appendix — Escalation matrix

| Situation | Escalate to |
|---|---|
| Refund, duplicate payment, or payment-method correction | Accountant / super admin |
| Corporate account, credit limit, or deferred billing | Manager / admin / super admin |
| Room, venue, or table availability conflict | Manager / reception lead |
| Menu, food order, kitchen, or stock issue | Restaurant lead / kitchen manager |
| Guest account, role, or permission change | Admin / super admin |
| Branding, billing rates, promotion, or public content | Admin / manager / super admin |
| Paystack webhook or verification issue | Accountant / technical owner |
| Migration, backup, queue, scheduler, or production outage | Super admin / technical owner |
