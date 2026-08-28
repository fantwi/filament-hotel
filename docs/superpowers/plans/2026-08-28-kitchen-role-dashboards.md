# Kitchen Role Dashboards Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add separate, date-filtered Filament dashboards for kitchen managers and kitchen staff using role-specific stats overview widgets.

**Architecture:** Create two `TimeFilteredDashboard` pages with role-and-permission guards, two `StatsOverviewWidget` classes with shared date filtering, and explicit role routing from `RoleDashboard`. Reuse the existing kitchen queue, production, and stock widgets according to each role’s permissions.

**Tech Stack:** Laravel, Filament dashboards and `StatsOverviewWidget`, Eloquent, Spatie Permission, PHPUnit/Pest test runner, Laravel Pint.

**Spec:** `docs/superpowers/specs/2026-08-28-kitchen-role-dashboards-design.md`

## Global Constraints

- No database tables, migrations, or kitchen business rules change.
- Both dashboards remain in the existing Filament admin panel.
- Both dashboards use the shared `TimeFilteredDashboard` date-range and breakdown filters.
- Existing widget-level permissions remain authoritative.
- Preserve unrelated pending admin-dashboard files in the working tree.

---

### Task 1: Add failing dashboard registration and routing tests

**Files:**
- Create: `tests/Feature/KitchenRoleDashboardTest.php`
- Read: `app/Filament/Admin/Pages/Dashboards/RoleDashboard.php`

**Interfaces:**
- Consumes: expected `KitchenManagerDashboard`, `KitchenStaffDashboard`, `KitchenManagerStats`, and `KitchenStaffStats` class names.
- Produces: regression coverage that fails until the pages, widgets, and role mappings exist.

- [ ] **Step 1: Write the failing tests**

```php
<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\Dashboards\KitchenManagerDashboard;
use App\Filament\Admin\Pages\Dashboards\KitchenStaffDashboard;
use App\Filament\Admin\Pages\Dashboards\TimeFilteredDashboard;
use App\Filament\Admin\Widgets\KitchenManagerStats;
use App\Filament\Admin\Widgets\KitchenStaffStats;
use Filament\Widgets\StatsOverviewWidget;
use Tests\TestCase;

class KitchenRoleDashboardTest extends TestCase
{
    public function test_kitchen_dashboards_use_shared_filters_and_role_specific_stats_first(): void
    {
        foreach ([
            KitchenManagerDashboard::class => [KitchenManagerStats::class, 'KitchenOrderQueue', 'KitchenProductionStats', 'KitchenStockStats'],
            KitchenStaffDashboard::class => [KitchenStaffStats::class, 'KitchenOrderQueue', 'KitchenProductionStats'],
        ] as $dashboardClass => [$statsClass, ...$detailNames]) {
            self::assertTrue(is_subclass_of($dashboardClass, TimeFilteredDashboard::class));
            self::assertSame(['default' => 1, 'md' => 2, 'xl' => 3], (new $dashboardClass)->getColumns());
            self::assertSame($statsClass, (new $dashboardClass)->getWidgets()[0]);
            self::assertTrue(is_subclass_of($statsClass, StatsOverviewWidget::class));
            self::assertContains(InteractsWithDashboardDateRange::class, class_uses_recursive($statsClass));

            $widgets = (new $dashboardClass)->getWidgets();
            foreach ($detailNames as $detailName) {
                $detailClass = 'App\\Filament\\Admin\\Widgets\\'.$detailName;
                self::assertContains($detailClass, $widgets);
                self::assertLessThan(array_search($detailClass, $widgets, true), array_search($statsClass, $widgets, true));
            }
        }
    }

    public function test_role_dashboard_routes_kitchen_roles_to_separate_pages(): void
    {
        $source = file_get_contents(app_path('Filament/Admin/Pages/Dashboards/RoleDashboard.php'));

        self::assertStringContainsString("hasRole('kitchen_manager')", $source);
        self::assertStringContainsString('KitchenManagerDashboard::class', $source);
        self::assertStringContainsString("hasRole('kitchen_staff')", $source);
        self::assertStringContainsString('KitchenStaffDashboard::class', $source);
    }
}
```

- [ ] **Step 2: Run the tests to verify the expected failure**

Run: `php artisan test tests/Feature/KitchenRoleDashboardTest.php`

Expected: FAIL because the two dashboard pages and stats widget classes do not yet exist and the role mappings are absent.

### Task 2: Implement the role-specific stats overview widgets

**Files:**
- Create: `app/Filament/Admin/Widgets/KitchenManagerStats.php`
- Create: `app/Filament/Admin/Widgets/KitchenStaffStats.php`

**Interfaces:**
- Consumes: `InteractsWithDashboardDateRange`, `RestaurantOrder::kitchenQueue()`, `KitchenProduction`, and `KitchenStockMovement`.
- Produces: protected `getStats(): array` methods returning `Stat` instances and role-specific `canView(): bool` guards.

- [ ] **Step 1: Implement `KitchenManagerStats`**

Use `InteractsWithDashboardDateRange`; set `columnSpan` to `full`; require the `kitchen_manager` role plus `view kitchen dashboard`; return five stats for confirmed queue orders, preparing orders, ready orders, production batches filtered on `production_date`, and stock movements filtered on `occurred_at`. Every stat description must use `dashboardDateRangeLabel()`.

- [ ] **Step 2: Implement `KitchenStaffStats`**

Use the same trait and full-width layout; require the `kitchen_staff` role plus `view kitchen dashboard`; return four stats for confirmed queue orders, preparing orders, ready orders, and served orders filtered on `served_at`. Use `RestaurantOrder::kitchenQueue()` for the first three counts and exclude cancelled orders from served counts.

- [ ] **Step 3: Run the focused test**

Run: `php artisan test tests/Feature/KitchenRoleDashboardTest.php`

Expected: Still FAIL only on missing dashboard pages and role mappings; the stats class and trait assertions should pass once the classes are autoloadable.

### Task 3: Add separate dashboard pages and role routing

**Files:**
- Create: `app/Filament/Admin/Pages/Dashboards/KitchenManagerDashboard.php`
- Create: `app/Filament/Admin/Pages/Dashboards/KitchenStaffDashboard.php`
- Modify: `app/Filament/Admin/Pages/Dashboards/RoleDashboard.php`

**Interfaces:**
- Consumes: `TimeFilteredDashboard`, the two stats widgets, and existing kitchen widgets.
- Produces: dashboard routes `kitchen-manager-dashboard` and `kitchen-staff-dashboard`, each with role-specific access checks and widget composition.

- [ ] **Step 1: Implement `KitchenManagerDashboard`**

Extend `TimeFilteredDashboard`; use title/navigation label `Kitchen Manager Dashboard`; set navigation group `Dashboards`; implement `canAccess()` as the authenticated user having role `kitchen_manager` and permission `view kitchen dashboard`; return columns `['default' => 1, 'md' => 2, 'xl' => 3]`; return widgets in this order: `KitchenManagerStats`, `KitchenOrderQueue`, `KitchenProductionStats`, `KitchenStockStats`.

- [ ] **Step 2: Implement `KitchenStaffDashboard`**

Use the same page structure with title/navigation label `Kitchen Staff Dashboard`; require role `kitchen_staff` and `view kitchen dashboard`; return widgets in this order: `KitchenStaffStats`, `KitchenOrderQueue`, `KitchenProductionStats`.

- [ ] **Step 3: Update `RoleDashboard::mount()`**

Import both pages and add these match branches before administrative roles:

```php
$user?->hasRole('kitchen_manager') => KitchenManagerDashboard::class,
$user?->hasRole('kitchen_staff') => KitchenStaffDashboard::class,
```

Leave all existing role branches unchanged.

- [ ] **Step 4: Run focused tests**

Run: `php artisan test tests/Feature/KitchenRoleDashboardTest.php tests/Feature/DashboardDateRangeTest.php tests/Feature/TransactionDashboardTest.php`

Expected: PASS with no failures.

### Task 4: Verify, review, and commit implementation

**Files:**
- Verify: all files created or modified in Tasks 1–3

- [ ] **Step 1: Run PHP lint**

Run: `php -l app/Filament/Admin/Pages/Dashboards/KitchenManagerDashboard.php && php -l app/Filament/Admin/Pages/Dashboards/KitchenStaffDashboard.php && php -l app/Filament/Admin/Widgets/KitchenManagerStats.php && php -l app/Filament/Admin/Widgets/KitchenStaffStats.php && php -l app/Filament/Admin/Pages/Dashboards/RoleDashboard.php && php -l tests/Feature/KitchenRoleDashboardTest.php`

Expected: every file reports no syntax errors.

- [ ] **Step 2: Run Laravel Pint**

Run: `vendor/bin/pint --test`

Expected: PASS with no style issues.

- [ ] **Step 3: Run the complete test suite**

Run: `php artisan test`

Expected: all tests pass, including the existing admin and accountant dashboard regressions.

- [ ] **Step 4: Review the working tree**

Run: `git status --short`

Confirm only the kitchen dashboard implementation files are staged; preserve unrelated pending admin-dashboard files unstaged.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Admin/Pages/Dashboards/KitchenManagerDashboard.php app/Filament/Admin/Pages/Dashboards/KitchenStaffDashboard.php app/Filament/Admin/Pages/Dashboards/RoleDashboard.php app/Filament/Admin/Widgets/KitchenManagerStats.php app/Filament/Admin/Widgets/KitchenStaffStats.php tests/Feature/KitchenRoleDashboardTest.php
git commit -m "Add separate kitchen role dashboards"
```
