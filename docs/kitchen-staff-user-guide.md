# Filament Hotel

## Kitchen Staff User Guide

**Audience:** Kitchen staff  
**Application:** Filament Hotel hospitality management system  
**Admin URL:** `/admin`  
**Guide date:** 29 August 2026

## 1. Kitchen staff role at a glance

Kitchen staff use the Filament panel to prepare restaurant orders and record the food produced by the kitchen. The role can manage the live kitchen queue, create and edit kitchen production batches, view kitchen stock and stock movements, and review production reports.

Kitchen staff work within the production process. They do not receive or adjust stock, maintain recipes, manage menu items, handle payments, manage guest bookings, or change roles and settings. Ask the kitchen manager for stock, recipe, menu, or production corrections, and ask the manager/accountant for guest or payment issues.

## 2. Sign in and use the kitchen staff dashboard

1. Open the admin URL, normally `https://your-domain.com/admin`.
2. Sign in with your kitchen staff email and password.
3. Open **Dashboards → Kitchen Staff Dashboard**.
4. If access is denied, confirm the account has the `kitchen_staff` role and `view kitchen dashboard` permission.
5. Sign out from the profile menu when finished on a shared device.

The **Dashboard period** section spans the page width. Choose **Daily**, **Weekly**, **Monthly**, **Quarterly**, or **Yearly**, or provide custom start and end dates. The widgets use that range:

- **KitchenStaffStats** — orders waiting, preparing, ready to serve, and served.
- **KitchenOrderQueue** — live confirmed, preparing, and ready orders with status actions.
- **KitchenProductionStats** — tracked food items, low stock, negative variance, and food revenue.

Use **Daily** during service. The date range changes the dashboard view only; it does not change an order or production record.

## 3. What kitchen staff can access

| Area | Kitchen staff capability |
|---|---|
| Dashboard | Staff task counts, live queue, and production overview |
| Kitchen orders | Move orders from confirmed to preparing, ready, and served |
| Kitchen production | Create, edit, and review production batches, ingredients consumed, and waste |
| Kitchen stock | View ingredient balances and stock information |
| Stock movements | View the audited movement ledger |
| Production reports | View production-versus-sales, stock, and variance signals |
| Recipes and menu setup | View/use configured items; changes belong to the kitchen manager |
| Stock receiving/adjustments | Not available; ask the kitchen manager |
| Payments, refunds, guests, bookings | Not available; ask accounting or reception/manager |
| Users, roles, promotions, corporate accounts | Not available; ask an administrator/manager |

The kitchen manager has additional permissions to maintain stock and recipes. Do not use another person’s account to perform a restricted action.

## 4. Service shift routine

### Before service

1. Set the dashboard to **Daily** and confirm the service date.
2. Review the order counts and open the Kitchen Order Queue.
3. Check visible stock and low-stock signals; report shortages before cooking begins.
4. Confirm the menu item and production unit for planned batches.
5. Ask the kitchen manager about substitutions, recipe changes, or unavailable menu items.

### During service

1. Keep the queue open; it polls for new kitchen work.
2. Start an order by using **Prepare** when cooking begins.
3. Add short kitchen notes for delays, substitutions, allergies, or special handling according to kitchen policy.
4. Use **Ready** only after the order has passed the kitchen’s quality check.
5. Use **Served** after handoff to the service team or guest.
6. Record production batches and waste as they occur.

### Close of service

1. Confirm no order is left in the wrong status.
2. Record every batch produced and the ingredients consumed.
3. Record finished-food waste separately from raw ingredient consumption.
4. Review production and variance signals, then hand exceptions to the kitchen manager.

## 5. Work the kitchen order queue

Open **Restaurant → Food Orders** or use the queue on the Kitchen Staff Dashboard.

### Status workflow

1. **Confirmed** — order accepted and waiting to start.
2. **Preparing** — cooking or assembly is in progress.
3. **Ready** — complete and waiting for service handoff.
4. **Served** — handed to the guest or service team.

The queue shows order number, menu items, quantities, table/channel, status, waiting time, kitchen notes, and assigned chef. Keep table and channel details intact so service staff can find the order.

Do not change payment or corporate-billing state to move an order. Send payment failures, Paystack questions, and corporate-account issues to the manager/accountant.

## 6. Record a production batch

Open **Restaurant → Kitchen Production → Create**.

1. Select the menu item from the Menu Items table. Only items with kitchen production tracking enabled appear.
2. Enter today or the correct past production date. Future production dates are not allowed.
3. Enter the quantity produced using the configured production unit.
4. Enter quantity wasted; use `0` when no finished food was discarded.
5. Add each ingredient consumed and the actual quantity used.
6. Add notes for batch reference, unusual yield, substitutions, or quality checks.
7. Save and confirm the batch appears in the production list.

Saving a batch deducts the recorded raw ingredients from kitchen stock and adds finished-food production balance. Quantity wasted must not exceed quantity produced. For example, an 80-portion jollof rice batch can include rice, chicken, oil, and spices as consumed ingredients, with discarded portions recorded as waste.

## 7. Production units and waste

Use the configured unit consistently:

- Portions for plated meals.
- Pieces for individual items.
- Trays for baked goods.
- Kilograms or grams for weight-based food.
- Litres or millilitres for liquids.
- Bottles for bottled drinks.

Finished-food waste is food discarded after preparation. It is not the same as an ingredient quantity. If raw ingredients are spilled, spoiled, or discarded before becoming a batch, tell the kitchen manager so the correct stock movement can be recorded.

## 8. Stock visibility and reporting

Kitchen staff can view **Kitchen Stock**, **Stock Movements**, and **Production vs Sales / Kitchen Production Report**.

Use them to answer:

- Which ingredients are available for the next batch?
- What stock was received, consumed, or wasted in the selected period?
- How many portions were produced and sold?
- Which item shows a negative variance or unusually high waste?

Do not receive stock, edit a stock balance, delete a movement, or change a recipe. Capture the ingredient, quantity, unit, date, and reason and give it to the kitchen manager.

## 9. Understanding variance

**Negative variance** is a warning that recorded sales or consumption exceed recorded production or available stock for the selected period. Check the following with the kitchen manager:

- The report date range and late entries.
- Missing or duplicate production batches.
- Cancelled or refunded orders.
- Recipe quantities and unit conversions.
- Unrecorded waste or stock movements.

Do not add a compensating batch or waste record simply to make the report balance. Correct the source event with approval.

## 10. Handoffs and communication

- Tell service staff when an order is ready and include the table/channel.
- Tell the kitchen manager about shortages, substitutions, recipe questions, and unusual waste.
- Tell the manager about repeated delays, equipment issues, or menu availability concerns.
- Send payment, refund, guest, booking, and corporate-account questions outside the kitchen team.
- Include order/batch ID, menu item, quantity, unit, time, and what has already been done in every escalation.

## 11. Troubleshooting

### A new order is not visible

Refresh the queue and check that the order is in a kitchen-queue status. If the queue is not polling, report the issue to the kitchen manager or technical owner.

### The Prepare/Ready/Served action is missing

The action depends on the current order status. Refresh the row and ask the kitchen manager if the record is stuck or incorrectly configured.

### A menu item is missing from production

The item may not have kitchen production tracking enabled. Give the item name to the kitchen manager; do not change menu configuration yourself.

### Production save fails

Check the menu item, date, positive quantity produced, non-negative waste, at least one ingredient, and quantity-wasted limit. Ask the kitchen manager to confirm stock and recipe configuration.

### The stock balance looks wrong

Review the visible movement history and report the ingredient, unit, date, and difference. Do not create an unapproved receipt or adjustment.

### Negative variance is unexplained

Align dates, check cancelled/refunded orders, compare recipes and units, and ask the kitchen manager to investigate missing batches or waste.

## 12. Kitchen safety and data rules

- Use your own named account; never share credentials.
- Follow food-safety, allergen, temperature, and waste procedures in addition to this guide.
- Record actual quantities promptly and use the same unit as the ingredient stock.
- Never store passwords, card data, or Paystack secrets in kitchen notes.
- Preserve production history; use approved corrections instead of deleting records.
- Escalate missing permissions, system errors, and unexplained stock changes.

## Appendix — Escalation matrix

| Situation | Escalate to |
|---|---|
| Ingredient shortage, stock receipt, or stock adjustment | Kitchen manager |
| Recipe, menu tracking, production, or waste correction | Kitchen manager / manager |
| Order delay, table/channel, or service handoff issue | Kitchen manager / restaurant manager |
| Payment, refund, or corporate billing issue | Accountant / manager |
| Guest complaint, booking, or reservation issue | Reception / manager |
| User, role, permission, or dashboard access | Admin / super admin |
| Queue worker, database, scheduler, or production outage | Super admin / technical owner |
