# Kitchen role dashboards design

## Context

The Filament admin panel currently has kitchen order, production, and stock widgets, but no dashboard page dedicated to either kitchen role. The shared role dashboard redirects administrative roles to their own pages, while kitchen roles are not yet routed to a dashboard. Kitchen managers and kitchen staff need separate workspaces because their responsibilities and write permissions differ.

## Goals

- Provide a dedicated dashboard for `kitchen_manager` users.
- Provide a separate, task-focused dashboard for `kitchen_staff` users.
- Use stats overview widgets as the first operational summary on each page.
- Keep both dashboards consistent with the existing date-range and breakdown filters.
- Reuse the existing kitchen queue, production, and stock widgets where their permissions allow.
- Keep manager-only management context out of the staff dashboard.

## Non-goals

- No new database tables, migrations, or changes to kitchen order/stock business rules.
- No new Filament panel; both dashboards remain in the existing admin panel.
- No changes to the permissions granted by the role seeders beyond using the existing `view kitchen dashboard` and related permissions.

## Architecture

Add two pages under `app/Filament/Admin/Pages/Dashboards`:

- `KitchenManagerDashboard` extends `TimeFilteredDashboard`, uses route path `kitchen-manager-dashboard`, and requires both the `kitchen_manager` role and `view kitchen dashboard` permission.
- `KitchenStaffDashboard` extends `TimeFilteredDashboard`, uses route path `kitchen-staff-dashboard`, and requires both the `kitchen_staff` role and `view kitchen dashboard` permission.

Both pages use the existing responsive dashboard columns (`default: 1`, `md: 2`, `xl: 3`) and the shared filters form. `RoleDashboard::mount()` will route each role to its corresponding page before the existing administrative role branches. The pages will be registered through the existing page discovery mechanism, so no panel provider registration is required.

## Widget composition

Create two role-specific `StatsOverviewWidget` classes, both using `InteractsWithDashboardDateRange`:

### Kitchen manager stats

`KitchenManagerStats` provides a full-width summary of operational health:

- Orders waiting to start
- Orders currently preparing
- Orders ready to serve
- Production batches recorded in the selected period
- Stock movements recorded in the selected period

The widget requires `view kitchen dashboard` and the `kitchen_manager` role. Queue counts use active kitchen statuses; production counts filter `production_date`; stock movement counts filter `occurred_at`.

### Kitchen staff stats

`KitchenStaffStats` provides a narrower task summary:

- Orders waiting to start
- Orders currently preparing
- Orders ready to serve
- Orders served in the selected period

The widget requires `view kitchen dashboard` and the `kitchen_staff` role. It uses the same date-range filter and excludes cancelled orders from task counts.

### Page order

`KitchenManagerDashboard` renders `KitchenManagerStats` first, followed by `KitchenOrderQueue`, `KitchenProductionStats`, and `KitchenStockStats`. `KitchenStaffDashboard` renders `KitchenStaffStats` first, followed by `KitchenOrderQueue` and `KitchenProductionStats`. Existing widget-level permissions remain authoritative; a widget is hidden when the viewer lacks its specific permission.

## Authorization and routing

The dashboard page `canAccess()` checks both role and permission, preventing a manager or staff member from opening the other role's dashboard URL. The role redirect maps:

- `kitchen_manager` → `KitchenManagerDashboard`
- `kitchen_staff` → `KitchenStaffDashboard`

Existing administrative role routing remains unchanged. No finance, corporate billing, hotel, or conference widgets are added to either kitchen dashboard.

## Error handling and data behavior

All metrics use existing Eloquent models and status constants/values; no writes occur while rendering dashboards. Date filters fall back through `InteractsWithDashboardDateRange` when filters are missing or malformed. Empty datasets render zero-valued stats, matching existing widget behavior. The live queue continues to use its existing polling and action error handling.

## Verification

Add feature tests that:

1. Confirm both dashboard pages extend `TimeFilteredDashboard` and expose the expected responsive columns.
2. Confirm each page registers its role-specific `StatsOverviewWidget` before detail widgets.
3. Confirm both stats classes use `InteractsWithDashboardDateRange`.
4. Confirm the role dashboard source maps both kitchen roles to their dedicated pages.

Run the focused kitchen dashboard tests, the complete Laravel test suite, PHP lint on changed files, and Laravel Pint before committing the implementation.
