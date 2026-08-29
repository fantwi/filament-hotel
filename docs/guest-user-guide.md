# Filament Hotel

## Guest User Guide

**Audience:** Guests and guest accounts  
**Application:** Filament Hotel hospitality management system  
**Website:** `https://your-domain.com`  
**Guide date:** 29 August 2026

## 1. Welcome

The Filament Hotel website lets you browse rooms, conference venues, restaurant facilities, and menus; book a stay or event; reserve a table; order food; pay securely; and follow every transaction from your guest dashboard.

You can browse public pages without an account. Sign in or create a guest account before completing a hotel booking, conference booking, or any action that must appear in your dashboard. A guest account keeps your bookings, reservations, orders, invoices, payment history, and corporate-billing status together.

## 2. Create an account and sign in

### Create a guest account

1. Open the website and choose **Sign Up**.
2. Enter your first name, last name, email, phone number, and password.
3. Confirm the password and submit the form.
4. Verify your email if the site asks you to do so.
5. Sign in and open **Dashboard**.

Use an email address you can access. It is used for booking and payment receipts and must be unique.

### Sign in or reset your password

1. Choose **Login** and enter your email and password.
2. Use **Forgot password?** if you cannot sign in.
3. Follow the password-reset link sent to your email.
4. Sign out from the account menu on a shared device.

If you change your email in your profile, the account may require email verification again.

## 3. Navigate the guest website

The main menu provides:

- **Rooms** — browse room types and availability calendars.
- **Restaurant** — restaurant home, menu, tables, gallery, table reservation, and food cart.
- **Conferences** — browse conference rooms and book a venue.
- **Contact** — send a question to the hotel.
- **Cart** — view food items selected for ordering.
- **Dashboard** — manage your bookings, reservations, orders, and outstanding payments after sign-in.
- **Profile** — update your contact information, photo, and password.

The site is mobile-first. On a phone, open the menu button to expand Rooms, Restaurant, and Conferences. Use the sun/moon appearance toggle to switch light and dark mode; the site otherwise follows a daytime light/night-time dark schedule until you choose a preference.

## 4. Book a hotel room

1. Open **Rooms** and choose a published room type.
2. Review the room description, price per night, facilities, photos, and gallery overlay.
3. Open the availability calendar to check current and future dates.
4. Choose a room and continue to **Complete Your Booking**.
5. Enter check-in date, check-out date, and number of guests.
6. Review the Booking Summary: room, room number, nights, subtotal, discount, discounted subtotal, service charge, VAT, NHIL, and estimated total.
7. Enter an optional **Discount code** and choose **Apply discount** to validate it and refresh the estimate.
8. If your account is linked to an enabled corporate organisation, choose either **Pay now** or **Bill to corporate account**.
9. Choose **Continue**.

The availability check blocks only dates covered by active bookings or valid holds. Past dates cannot be selected for a new stay. If a room appears unavailable unexpectedly, refresh the calendar and try another room/date range.

### Pay now

After continuing, the room is temporarily held while you complete payment. The payment page shows the amount due and a hold timer. Choose **Pay securely with Paystack**, complete the payment on Paystack, and wait for the verified return to the site.

The server verifies the payment reference, amount, and booking before confirming the stay. A browser return or bank notification alone is not proof of payment.

### Bill to corporate account

When an enabled corporate account is linked to your guest profile, choose **Bill to corporate account**. The booking is confirmed without a short hold timer and appears as **Awaiting payment** until the organisation settles it. You will not be sent to the Paystack payment page for this option.

## 5. Manage a hotel booking

Open **Dashboard** to see upcoming and past hotel stays.

- Choose **Pay now** for an unpaid personal booking while its hold is active.
- Choose **Invoice** to download the hotel invoice.
- Check the status, payment status, room, dates, and corporate-billing label.
- Contact the hotel if the booking details are wrong.

To cancel an unpaid booking, use the booking’s **Cancel booking** action on the payment page or the dashboard when available. The room dates and hold are released. Paid bookings cannot be cancelled through the guest flow; ask the hotel about its cancellation/refund policy.

## 6. Book a conference room

1. Open **Conferences → Conference Rooms**.
2. Review capacity, hourly rate, facilities, photos, and gallery overlay.
3. Choose **Book** for the venue.
4. Select an event date, start time, end time, and attendee count.
5. Add optional special requests.
6. Enter a **Discount code**, then choose **Apply discount** to update the summary.
7. Review subtotal, discount, net after discount, service charge, VAT, NHIL, and total.
8. Choose **Pay now** or **Bill to corporate account** when corporate credit is available.
9. Choose **Continue**.

The date and time slot must be current/future and cannot overlap another active booking. End time must be after start time and attendees cannot exceed venue capacity.

### Conference payment and cancellation

For **Pay now**, complete Paystack payment from the conference payment page. The booking is confirmed only after server-side verification. For corporate billing, the booking is confirmed and remains awaiting settlement under the organisation’s terms.

Use **Invoice** from the dashboard to download a conference invoice. Unpaid bookings can be cancelled from the payment page before settlement; paid bookings require the hotel’s cancellation policy.

## 7. Reserve a restaurant table

1. Open **Restaurant → Reserve a Table**.
2. Choose a table that seats your party.
3. Enter guest name, email, phone, reservation date, reservation time, number of guests, and optional special requests.
4. Enter an optional **Discount code** and choose **Apply discount**.
5. Review the reservation summary, including subtotal, discount, net, service charge, VAT, NHIL, and total.
6. Choose **Pay now** or **Bill to corporate account** if your account is eligible.
7. Submit the reservation.

A table reservation normally holds the selected slot for a limited period when payment is required. The restaurant prevents overlapping reservations for the same table and time. Corporate reservations do not use the short payment hold timer.

### Reservation details, payment, and cancellation

Open the reservation details link from your dashboard or confirmation. Use **Pay now** to open Paystack while an unpaid hold is active. Use **Cancel** only for an unpaid pending reservation; cancellation releases the table hold. Paid reservations cannot be cancelled through this action.

## 8. Browse the menu and use the food cart

1. Open **Restaurant → Menu**.
2. Browse categories and available menu items.
3. Review meal photos, descriptions, prices, and featured items.
4. Choose **Add to cart** for each item and adjust quantities in **Cart**.
5. Use **Update** to change a quantity or **Remove** to delete an item.
6. Choose **Add more food items** to return to the menu, or **Checkout** to continue.

You can start a standard website order without a table. A table QR order may attach the cart to a specific restaurant table; confirm the table label before checkout.

## 9. Check out a food order

1. Review every item and quantity.
2. Enter a **Discount code** and select **Apply discount** if you have one.
3. Review the full calculation:
   - **Subtotal** — menu-item line totals.
   - **Discount** — percentage or fixed promotion reduction.
   - **Net after discount** — subtotal less discount.
   - **Service charge**, **VAT**, and **NHIL** — calculated on the discounted net.
   - **Estimated total** — final amount to pay.
4. Enter the email for your receipt and optional order notes, including allergies or special instructions.
5. Choose **Pay now** or **Bill to corporate account** when shown.
6. Choose **Place order**.

### Pay now

The confirmation page shows the order number and a **Pay securely with Paystack** button. Complete payment on Paystack. After verification, the order is confirmed and sent to the kitchen.

### Bill to corporate account

The order is confirmed and sent to the kitchen immediately, but its payment status is **Awaiting payment** until the organisation settles it. You will not see or need the Paystack button for this choice.

Use **Cancel order** only while the order is still unpaid and cancellable. The confirmation page also provides **Back to menu** and **Back to dashboard** links.

## 10. Understand charges and discount codes

Promotions may be percentage-based or fixed-amount and can have a minimum spend or validity dates. Enter the code exactly as issued and press **Apply discount** before continuing.

The displayed order is:

`Subtotal - Discount = Net after discount`  
`Net after discount + Service charge + VAT + NHIL = Estimated total`

Rates are controlled by the hotel. If the estimate does not match the displayed rates or a valid code is rejected, take a screenshot and contact the hotel instead of submitting duplicate transactions.

## 11. Corporate billing and outstanding payments

Corporate billing is available only when your guest account is linked to an enabled corporate organisation and the organisation has enough approved credit.

- **Pay now** charges your selected payment method immediately through Paystack.
- **Bill to corporate account** records the transaction against the organisation’s credit terms.
- A corporate transaction is not paid just because it is confirmed.
- Your dashboard labels unsettled corporate bookings, reservations, and orders **Awaiting payment**.
- When the organisation pays offline, the accountant records the settlement and your dashboard changes to **Paid**.

If the corporate option is missing, confirm with your organisation or the hotel that your account is linked correctly. Do not create a second personal transaction to work around a credit-limit error.

## 12. Guest dashboard

Open **Dashboard** after signing in. The dashboard shows:

- Total bookings, confirmed bookings, restaurant activity, and total spent.
- A payment-action alert when a balance is awaiting payment.
- Hotel stays with room, dates, status, payment state, Pay now, and invoice actions.
- Conference bookings with venue, date/time, attendees, status, payment state, Pay now, and invoice actions.
- Restaurant reservations with table, date/time, status, payment state, details, Pay now, and cancellation actions where eligible.
- Food orders with order number, items, total, kitchen status, payment/corporate state, and payment action where eligible.

Use the tabs to switch between Hotel, Conference, Reservations, and Food orders. If an order or booking is missing, confirm that you are signed in with the same email/account used to create it.

## 13. Profile and contact details

Open **Profile** to update your first name, last name, email, phone number, and profile photo. You can also change your password by entering the current password and a new confirmed password.

Keep contact details current so the hotel can reach you about reservations and payment receipts. If you change email, complete verification again if prompted.

Use **Contact** to send a question to the hotel. Do not include passwords, card numbers, Paystack secret keys, or other sensitive credentials.

## 14. Invoices and payment history

Hotel and conference invoices are available from the dashboard after the transaction is created. Payment history is available through the account menu’s **Payments** link where enabled.

Keep the transaction ID, order number, booking ID, payment reference, amount, and date when contacting support. These details help the hotel trace a payment without asking you to resend sensitive card information.

## 15. Troubleshooting

### A room or conference slot says unavailable

Refresh the page, choose current/future dates, and check another room or time. Availability blocks only active bookings and valid holds, but another guest may have just completed a reservation.

### The hold timer expired

The room, venue, or table hold has been released. Return to the relevant rooms, conference rooms, or restaurant reservation page and start again. Do not keep trying to pay an expired hold.

### Payment completed but the transaction is still pending

Wait briefly for server verification, then check your dashboard and payment reference. If it remains pending, contact the hotel with the reference and amount. Do not pay again before the hotel checks the existing attempt.

### A corporate transaction says awaiting payment

That status is expected until the organisation or accountant settles it. Ask your organisation contact or hotel accountant about the payment terms; do not use the Paystack button unless the dashboard offers it for a personal balance.

### A discount is not applied

Check spelling, minimum spend, start/end dates, and whether the code is active. Press **Apply discount** and confirm the discount row appears before submitting.

### A food order is not visible

Check the order confirmation and order number, then sign in with the same guest account used at checkout. If the order was created without an account, keep the confirmation link/session and contact the restaurant.

### Images or the gallery do not load

Refresh, check your connection, and try another browser. If the problem continues, contact the hotel with the page and room/menu item name.

## 16. Guest safety rules

- Use a unique password and never share it.
- Verify the website address before entering payment information.
- Pay only through the official Paystack page opened by the hotel site.
- Never send card numbers, passwords, or one-time codes by email or contact form.
- Keep booking and payment references private.
- Contact the hotel before retrying an uncertain or duplicate payment.

## Appendix — Quick links

| Need | Start here |
|---|---|
| Browse hotel rooms | `/rooms` |
| Check room availability | Select a room type, then open its calendar |
| Browse conference venues | `/conference-rooms` |
| Browse restaurant menu | `/restaurant/menu` |
| Reserve a table | `/restaurant/reserve` |
| View or update food cart | `/cart` |
| View bookings and orders | `/dashboard` |
| View profile | `/profile` |
| View personal payment options | `/payments` |
| Contact the hotel | `/contact` |
