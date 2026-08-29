# Filament Hotel

## Kitchen Manager User Guide

**Audience:** Kitchen managers  
**Application:** Filament Hotel hospitality management system  
**Admin URL:** `/admin`  
**Guide date:** 29 August 2026

## 1. Kitchen manager role at a glance

The kitchen manager is responsible for the restaurant production cycle: receiving ingredients, maintaining recipes, recording batch production and waste, supervising the live order queue, and reconciling produced food with sales. The role can manage kitchen orders, production, stock, stock movements, and menu-item recipes, and can view the kitchen manager dashboard and production reports.

The kitchen manager is not a finance, front-desk, or system-administration role. Payment records, refunds, guest accounts, corporate billing, promotions, hotel bookings, and user roles are handled by other roles. Escalate financial, guest-account, access, and cross-department exceptions to the manager, accountant, admin, or super admin.

## 2. Sign in and use the kitchen manager dashboard

1. Open the admin URL, normally `https://your-domain.com/admin`.
2. Sign in with your kitchen manager email and password.
3. Open **Dashboards → Kitchen Manager Dashboard**.
4. If access is denied, confirm the account has the `kitchen_manager` role and `view kitchen dashboard` permission.
5. Sign out from the profile menu when finished on a shared device.

The **Dashboard period** section spans the page width. Choose **Daily**, **Weekly**, **Monthly**, **Quarterly**, or **Yearly**, or provide custom start and end dates. The widgets use that range:

- **KitchenManagerStats** — orders waiting, preparing, ready to serve, production batches, and stock movements.
- **KitchenOrderQueue** — live confirmed, preparing, and ready orders with actions.
- **KitchenProductionStats** — tracked items, low-stock items, negative variances, and food revenue.
- **KitchenStockStats** — ingredients moved, stock received, stock consumed, and stock wasted.

Use **Daily** for service control and a custom range for cost or production review. The date range is a reporting scope; it does not change order or production records.

## 3. What kitchen managers can access

| Area | Kitchen manager capability |
|---|---|
| Dashboard | Kitchen manager stats, live queue, production, and stock overview widgets |
| Kitchen orders | Manage order status from confirmed through preparing, ready, and served |
| Kitchen production | Create, edit, and review production batches and waste |
| Ingredients / stock | Create and maintain ingredients, receive stock, and review stock balances |
| Stock movements | View and manage the audited movement ledger |
| Recipes | Create and maintain menu-item ingredient recipes |
| Production reports | View production-versus-sales, stock, and variance reports |
| Menu items | Select tracked menu items for production; menu catalogue changes follow restaurant permissions |
| Payments | Not available; send payment questions to accounting |
| Guest bookings | Not available; send room, conference, and table issues to reception/manager |
| Users, roles, corporate accounts, promotions | Not available; ask admin/manager/super admin |

The kitchen manager role has more stock and recipe control than kitchen staff. If an item or action is missing, verify both the role and the permission rather than bypassing the panel.

## 4. Daily kitchen operating rhythm

### Before service

1. Set the dashboard to **Daily** and confirm the service date.
2. Review stock received, low-stock signals, and ingredients below reorder level.
3. Confirm tracked menu items, recipes, and production units are correct.
4. Review planned or existing production batches and expected portions.
5. Check the live order queue for orders waiting to start.

### During service

1. Keep the Kitchen Order Queue open; it polls for new work.
2. Move orders from **confirmed** to **preparing** when cooking begins.
3. Add concise kitchen notes for substitutions, delays, or special handling.
4. Mark food **ready** only after the batch passes the kitchen’s quality check.
5. Mark food **served** after the handoff to service staff or the guest.
6. Record production and waste as they occur rather than estimating at close.

### Close of service

1. Ensure no order is left in the wrong status.
2. Record every production batch and ingredient quantity consumed.
3. Record spoiled, burnt, returned, or discarded finished food as waste.
4. Review stock movements and negative variance.
5. Hand unresolved shortages, recipe errors, and delayed orders to the manager.

## 5. Manage the live kitchen order queue

Open **Restaurant → Food Orders** or use the queue on the Kitchen Manager Dashboard.

### Order status workflow

1. **Confirmed** — accepted and waiting to be prepared.
2. **Preparing** — cooking or assembling is in progress.
3. **Ready** — completed and waiting for service handoff.
4. **Served** — handed to the guest or service team.

Use **Prepare**, **Ready**, and **Served** actions only when the physical kitchen state matches the record. The queue shows order number, items, table/channel, status, waiting time, notes, and assigned chef.

The queue supports website, table QR, and staff channels. Keep the channel and table information intact so service staff can find the guest. Do not alter payment status to move an order through production; payment and corporate-billing issues belong to accounting/management.

## 6. Create and maintain ingredients

Open **Restaurant → Kitchen Stock**.

### Create an ingredient

1. Choose **Create**.
2. Select the restaurant.
3. Enter ingredient name, optional SKU/category, stock unit, reorder level, and unit cost.
4. Leave the ingredient active if it can be selected in recipes or production.
5. Save and verify the ingredient appears in the stock list.

Supported units include kilograms, grams, litres, millilitres, pieces, bottles, packs, trays, and bags. Use one consistent unit for receipts, recipes, and production consumption.

### Receive stock

1. Use the receive-stock action on the Kitchen Stock page.
2. Select the ingredient, quantity, unit/date, and optional supplier.
3. Enter cost or notes when required by the property’s stock policy.
4. Save and confirm the inbound movement and resulting balance.

Stock balances should change through audited movements, not by directly typing a new current-stock value. Investigate unusual balances before receiving a corrective quantity.

## 7. Maintain menu-item recipes

Recipes connect finished menu items to raw ingredients.

1. Open the relevant **Menu Item** and its recipe ingredients relation.
2. Add each ingredient used for one production unit or batch basis.
3. Enter the exact quantity and stock unit.
4. Review the recipe with the chef before saving.
5. Confirm the menu item is configured to **Track Kitchen Production** if it must appear in the production selector.

Keep recipes version-aware through notes or the approved change process. If a recipe changes, document the effective date so production reports can be interpreted correctly.

## 8. Record a production batch

Open **Restaurant → Kitchen Production → Create**.

1. Select the menu item from the Menu Items table. Only items with kitchen production tracking enabled are offered.
2. Enter the production date. Future dates cannot be recorded.
3. Enter the quantity produced using the configured production unit.
4. Enter quantity wasted; use `0` when no finished food was discarded.
5. Add every raw ingredient consumed and its actual quantity used.
6. Add notes for batch number, quality, substitutions, or unusual yield.
7. Save and verify the resulting stock movements.

Saving a batch deducts the recorded raw ingredients from kitchen stock and adds finished-food production balance. Quantity wasted cannot exceed quantity produced. For example, a batch of 80 portions can record 10 kg rice, chicken, oil, and spices consumed, with any discarded portions recorded separately.

## 9. Production units, waste, and variance

Use the menu item’s configured production unit consistently:

- Portions for plated meals.
- Pieces for individual items.
- Trays for baked goods.
- Kilograms or grams for weight-based foods.
- Litres or millilitres for liquids.
- Bottles for bottled drinks.

Waste is finished food discarded from the batch, not an ingredient quantity. Ingredient waste belongs in the stock movement/consumption process according to kitchen policy.

Negative variance generally means recorded sales or consumption exceed recorded production or available stock for the selected period. Check:

- Date boundaries and late entries.
- Cancelled or refunded orders.
- Recipe quantities and unit conversions.
- Missing production batches.
- Duplicate or incorrect stock movements.

Ask the manager to approve source-record corrections; do not alter figures only to make the dashboard balance.

## 10. Production versus sales report

Open **Restaurant → Production vs Sales** (labelled **Kitchen Production Report** in some panel versions).

Use the date range controls to compare tracked menu items, healthy stock, production, waste, sold quantities, revenue, and variance. Review the table with its padded columns and borders so each value remains associated with the correct item.

Interpret the report as a control signal:

- **Healthy stock** means the ingredient or tracked item is above its configured reorder threshold at the report boundary.
- **Negative variance** means the recorded output/sales relationship needs investigation.
- **Food revenue** reflects sales in the selected period, not the cost of ingredients.

Export or hand the report to the manager/accountant according to the property’s close-of-day process.

## 11. Stock movement controls

Open **Restaurant → Stock Movements** to review the audited ledger.

Check direction and type for each movement:

- Inbound receipts and approved adjustments increase stock.
- Production consumption deducts raw ingredients.
- Wastage deducts recorded waste.
- Corrections should include notes and an accountable operator.

Do not delete or backdate movements to hide a discrepancy. Use an approved correction and document the reason.

## 12. Coordination with restaurant and management

- Confirm menu-item availability and prices with the restaurant manager.
- Confirm table/channel details with service staff when an order is delayed.
- Notify the manager before disabling a menu item due to stock shortage.
- Send payment, refund, or corporate-account questions to accounting.
- Send guest complaints, booking changes, and table-reservation changes to reception/manager.
- Hand stock shortages and supplier issues to the manager with ingredient, unit, quantity, and date.

## 13. Routine maintenance and troubleshooting

### A menu item is missing from production

Open Menu Items and enable **Track Kitchen Production**. Verify the item is active and belongs to the correct restaurant/category.

### Production save fails

Check that a menu item, past-or-current production date, positive quantity produced, non-negative waste, at least one ingredient, and sufficient stock are present. Confirm quantity wasted is not greater than quantity produced.

### Stock is lower than expected

Review receipts, consumption, wastage, units, duplicate movements, and batch records for the selected date range. Do not create a compensating receipt without evidence.

### An order is stuck in the queue

Check its status, kitchen notes, order channel, menu item, and queue worker health. Ask the manager or technical owner to inspect failed jobs if the queue is not polling.

### Negative variance is unexplained

Align the report dates, confirm cancelled/refunded orders, review recipes and unit conversions, and identify missing batches before requesting a correction.

## 14. Kitchen safety and data rules

- Use your own named account; never share credentials.
- Follow food-safety, allergen, temperature, and waste procedures outside the application.
- Record actual quantities promptly and consistently.
- Never store passwords, card data, or Paystack secrets in kitchen notes.
- Preserve stock and production history; use documented corrections instead of deletes.
- Back up before migrations or bulk updates.
- Escalate system errors, missing permissions, and unexplained stock changes.

## Appendix — Escalation matrix

| Situation | Escalate to |
|---|---|
| Payment, refund, or corporate billing issue | Accountant / manager |
| Menu availability, pricing, or promotion | Restaurant manager / admin |
| Ingredient shortage, supplier, or stock discrepancy | Manager / purchasing lead |
| Recipe, production, waste, or variance dispute | Manager / executive chef |
| Guest complaint, table, room, or conference request | Reception / manager |
| User, role, permission, or dashboard access | Admin / super admin |
| Queue worker, scheduler, database, or production outage | Super admin / technical owner |
