# Kitchen Production Resource Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (- [ ]) syntax for tracking.

**Goal:** Split the oversized Kitchen Production Filament resource into focused form and table configuration classes without changing its behavior.

**Architecture:** KitchenProductionResource remains the authorization, navigation, page-registration, and delegation boundary. KitchenProductionForm owns the complete editable schema and form-only helpers, while KitchenProductionsTable owns the register query, columns, filters, actions, and table-only helpers; the existing KitchenProductionInfolist remains unchanged.

**Tech Stack:** PHP 8.2+, Laravel 12, Filament 5.2, Livewire, PHPUnit 11, Laravel Pint

**Spec:** docs/superpowers/specs/2026-09-06-kitchen-production-resource-refactor-design.md

## Global Constraints

- Preserve every current UI state path, validation rule, query, permission, service boundary, action, notification, and responsive behavior.
- Do not implement issue 13's operational summary, shortcuts, empty states, or CSV export.
- Do not add a database migration, route change, permission change, model change, or service API change.
- Do not extract calculations into another support service.
- Keep KitchenProductionInfolist unchanged.
- Keep KitchenProductionRecipeService authoritative for recipe estimates.
- Keep KitchenStockService authoritative for locked ingredient deductions and void reversals.
- Keep canVoid() on KitchenProductionResource as the single authorization owner.
- Move existing schema and table definitions without opportunistic copy, layout, validation, or query changes.
- Preserve unrelated working-tree changes if any appear during implementation.

## File Structure

- Create app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionForm.php
  - Owns the complete production form schema and every helper used only by form callbacks.
- Create app/Filament/Admin/Resources/KitchenProductions/Tables/KitchenProductionsTable.php
  - Owns the production register query, columns, filters, actions, and table-only helpers.
- Modify app/Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php
  - Retains metadata, authorization, page registration, the existing infolist delegation, and new form/table delegation.
- Create tests/Feature/KitchenProductionResourceStructureTest.php
  - Protects the class boundaries, delegation, stable component identifiers, responsive table configuration, and removal of obsolete commented schema code.
- Keep app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionInfolist.php unchanged.
- Keep all existing Kitchen Production feature tests unchanged unless a test contains a source-location assertion that must follow the extracted owner.

---

### Task 1: Extract the Kitchen Production form configuration

**Files:**

- Create: app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionForm.php
- Modify: app/Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php:105-512
- Create: tests/Feature/KitchenProductionResourceStructureTest.php

**Interfaces:**

- Consumes: Filament\Schemas\Schema, KitchenProductionRecipeService::estimate(int $menuItemId, int $restaurantId, float $quantityProduced): array, and the current KitchenProductionResource form component definitions.
- Produces: KitchenProductionForm::configure(Schema $schema): Schema and KitchenProductionResource::form(Schema $schema): Schema delegating to it.
- Preserves these form identifiers: restaurant_id, menu_item_id, production_unit_display, production_date, quantity_produced, quantity_wasted, live_yield_summary, recipe_prefill_status, recipe_estimate_actions, ingredients, ingredient_deduction_mode, recorded_ingredients, notes, and produced_by.

- [ ] **Step 1: Add the failing form-boundary regression test**

Create tests/Feature/KitchenProductionResourceStructureTest.php with this test:

~~~php
<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Filament\Admin\Resources\KitchenProductions\Schemas\KitchenProductionForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Tests\TestCase;

class KitchenProductionResourceStructureTest extends TestCase
{
    public function test_resource_delegates_its_form_to_the_dedicated_configuration(): void
    {
        $dedicatedSchema = KitchenProductionForm::configure(Schema::make());
        $resourceSchema = KitchenProductionResource::form(Schema::make());

        self::assertCount(2, $dedicatedSchema->getComponents());
        self::assertContainsOnlyInstancesOf(Grid::class, $dedicatedSchema->getComponents());
        self::assertSame(
            array_keys($dedicatedSchema->getFlatComponents(withHidden: true)),
            array_keys($resourceSchema->getFlatComponents(withHidden: true)),
        );

        foreach ([
            'restaurant_id',
            'menu_item_id',
            'production_unit_display',
            'production_date',
            'quantity_produced',
            'quantity_wasted',
            'live_yield_summary',
            'recipe_prefill_status',
            'recipe_estimate_actions',
            'ingredients',
            'ingredient_deduction_mode',
            'recorded_ingredients',
            'produced_by',
        ] as $componentKey) {
            self::assertNotNull(
                $resourceSchema->getComponent($componentKey, withHidden: true),
                "Expected delegated form component [{$componentKey}].",
            );
        }

        $source = file_get_contents(
            app_path('Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php'),
        );

        self::assertStringContainsString(
            'return KitchenProductionForm::configure($schema);',
            $source,
        );
    }
}
~~~

- [ ] **Step 2: Run the focused test and confirm the missing class is the failure**

Run:

~~~bash
php artisan test tests/Feature/KitchenProductionResourceStructureTest.php
~~~

Expected: FAIL because KitchenProductionForm does not exist and the resource has not delegated to it.

- [ ] **Step 3: Create the dedicated form class by moving the maintained schema verbatim**

Create app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionForm.php with the form-related imports currently held by the resource and this class boundary:

~~~php
<?php

namespace App\Filament\Admin\Resources\KitchenProductions\Schemas;

use App\Models\Ingredient;
use App\Models\KitchenProduction;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Services\KitchenProductionRecipeService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
~~~

Declare a final KitchenProductionForm class. Relocate the complete active return expression from KitchenProductionResource::form() into public static function configure(Schema $schema): Schema. This includes both responsive Grid definitions and the active Production guide.

Relocate these exact private static methods, including their docblocks and bodies, from KitchenProductionResource to KitchenProductionForm:

~~~text
selectedInventoryConsumptionMode(Get $get): ?string
selectedProductionUnit(Get $get): string
productionUnitForMenuItem(mixed $menuItemId): string
productionUnitLabel(Get $get, mixed $quantity): string
formattedProductionQuantity(Get $get, mixed $quantity): string
hasInvalidProductionWaste(Get $get): bool
netProductionYield(Get $get): string
productionWasteRate(Get $get): string
recipePrefillStatus(Get $get): string
canLoadRecipeEstimate(Get $get): bool
selectedProductionIngredient(Get $get): ?Ingredient
productionStockAvailability(Get $get): string
~~~

The class has one public method, KitchenProductionForm::configure(Schema $schema): Schema, followed by the twelve private static helpers in the manifest. During the relocation, resolve every existing static:: helper call against KitchenProductionForm without changing its arguments or return types. Exclude both obsolete commented-out definitions: the earlier Production guide and the duplicate Kitchen Production Batch.

- [ ] **Step 4: Make the resource delegate its form and remove form-only code**

Add the new import and replace KitchenProductionResource::form() with:

~~~php
use App\Filament\Admin\Resources\KitchenProductions\Schemas\KitchenProductionForm;

public static function form(Schema $schema): Schema
{
    return KitchenProductionForm::configure($schema);
}
~~~

Remove these methods from KitchenProductionResource after their unchanged bodies are present as private static methods on KitchenProductionForm:

~~~text
selectedInventoryConsumptionMode
selectedProductionUnit
productionUnitForMenuItem
productionUnitLabel
formattedProductionQuantity
hasInvalidProductionWaste
netProductionYield
productionWasteRate
recipePrefillStatus
canLoadRecipeEstimate
selectedProductionIngredient
productionStockAvailability
~~~

Remove imports used only by the extracted form while retaining imports still required by the table until Task 2.

- [ ] **Step 5: Run focused form and workflow regression tests**

Run:

~~~bash
php artisan test \
  tests/Feature/KitchenProductionResourceStructureTest.php \
  tests/Feature/KitchenProductionConsumptionModeTest.php \
  tests/Feature/KitchenProductionRecipePrefillTest.php \
  tests/Feature/KitchenProductionRestaurantScopeTest.php \
  tests/Feature/KitchenProductionUnitTest.php \
  tests/Feature/KitchenProductionYieldSummaryTest.php \
  tests/Feature/KitchenProductionEditProtectionTest.php
~~~

Expected: PASS. This verifies delegation plus creation, restaurant scoping, consumption modes, shortages, canonical units, recipe loading, responsive yield presentation, validation, and protected editing.

- [ ] **Step 6: Format, check the diff, and commit the form extraction**

Run:

~~~bash
vendor/bin/pint \
  app/Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php \
  app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionForm.php \
  tests/Feature/KitchenProductionResourceStructureTest.php
php -l app/Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php
php -l app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionForm.php
git diff --check
git diff --stat
git status --short
git add \
  app/Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php \
  app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionForm.php \
  tests/Feature/KitchenProductionResourceStructureTest.php
git commit -m "refactor: extract kitchen production form schema"
~~~

Expected: the focused tests remain green, syntax checks report no errors, the diff contains no whitespace errors, and only the three listed files enter the commit.

---

### Task 2: Extract the Kitchen Production table configuration

**Files:**

- Create: app/Filament/Admin/Resources/KitchenProductions/Tables/KitchenProductionsTable.php
- Modify: app/Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php:table()
- Modify: tests/Feature/KitchenProductionResourceStructureTest.php

**Interfaces:**

- Consumes: KitchenProductionResource::canVoid(KitchenProduction $record): bool, KitchenStockService::voidProduction(KitchenProduction $production, string $reason, User $actor): void, and the existing table definition.
- Produces: KitchenProductionsTable::configure(Table $table): Table and KitchenProductionResource::table(Table $table): Table delegating to it.
- Preserves these table columns: mobile_summary, menuItem.name, restaurant.name, batch_reference, production_date, yield_summary, producer.name, inventory_status.
- Preserves these table filters: restaurant_id, menu_item, category, produced_by, inventory_status, waste_only, date_preset, production_date.
- Preserves view, edit, and void record actions, RecordActionsPosition::BeforeColumns, stackedOnMobile(), eager loading of menuItem and restaurant, and the production-date/id default sort.

- [ ] **Step 1: Extend the structural test with a failing table-boundary contract**

Add these imports to KitchenProductionResourceStructureTest:

~~~php
use App\Filament\Admin\Resources\KitchenProductions\Tables\KitchenProductionsTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
~~~

Add these two tests:

~~~php
public function test_resource_delegates_its_table_to_the_dedicated_configuration(): void
{
    $dedicatedTable = KitchenProductionsTable::configure(
        Table::make($this->createMock(HasTable::class)),
    );
    $resourceTable = KitchenProductionResource::table(
        Table::make($this->createMock(HasTable::class)),
    );

    self::assertSame(
        array_keys($dedicatedTable->getColumns()),
        array_keys($resourceTable->getColumns()),
    );
    self::assertSame(
        array_keys($dedicatedTable->getFilters()),
        array_keys($resourceTable->getFilters()),
    );

    foreach ([
        'mobile_summary',
        'menuItem.name',
        'restaurant.name',
        'batch_reference',
        'production_date',
        'yield_summary',
        'producer.name',
        'inventory_status',
    ] as $columnName) {
        self::assertNotNull($resourceTable->getColumn($columnName));
    }

    foreach ([
        'restaurant_id',
        'menu_item',
        'category',
        'produced_by',
        'inventory_status',
        'waste_only',
        'date_preset',
        'production_date',
    ] as $filterName) {
        self::assertNotNull($resourceTable->getFilter($filterName));
    }

    self::assertTrue($resourceTable->isStackedOnMobile());
    self::assertSame(
        RecordActionsPosition::BeforeColumns,
        $resourceTable->getRecordActionsPosition(),
    );
}

public function test_resource_no_longer_owns_extracted_components_or_obsolete_schema(): void
{
    $source = file_get_contents(
        app_path('Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php'),
    );

    self::assertStringContainsString(
        'return KitchenProductionsTable::configure($table);',
        $source,
    );
    self::assertStringNotContainsString('// Section::make', $source);
    self::assertDoesNotMatchRegularExpression(
        '/^use (?:'
            .'App\\\\Models\\\\(?:Ingredient|MenuCategory|MenuItem|Restaurant|User)'
            .'|App\\\\Services\\\\(?:KitchenProductionRecipeService|KitchenStockService)'
            .'|Carbon\\\\CarbonImmutable'
            .'|Closure'
            .'|Filament\\\\(?:Actions|Forms|Infolists|Notifications)'
            .'|Filament\\\\Schemas\\\\Components'
            .'|Filament\\\\Tables\\\\(?:Columns|Enums|Filters)'
            .'|Illuminate\\\\Database\\\\Eloquent\\\\Builder'
            .'|Illuminate\\\\Support\\\\Str'
            .'|Illuminate\\\\Validation\\\\ValidationException'
            .');/m',
        $source,
    );
}
~~~

- [ ] **Step 2: Run the structural test and confirm the table class is missing**

Run:

~~~bash
php artisan test tests/Feature/KitchenProductionResourceStructureTest.php
~~~

Expected: the form test passes and the new table test fails because KitchenProductionsTable does not exist.

- [ ] **Step 3: Create the table class and move the table configuration unchanged**

Create app/Filament/Admin/Resources/KitchenProductions/Tables/KitchenProductionsTable.php with this ownership boundary:

~~~php
<?php

namespace App\Filament\Admin\Resources\KitchenProductions\Tables;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\User;
use App\Services\KitchenStockService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
~~~

Declare a final KitchenProductionsTable class. Relocate the complete active return chain from KitchenProductionResource::table() into public static function configure(Table $table): Table.

Relocate these exact private static methods, including their docblocks and bodies, from KitchenProductionResource to KitchenProductionsTable:

~~~text
productionDatePresetBounds(?string $preset): array
productionTableYieldSummary(KitchenProduction $record): array
productionTableQuantity(KitchenProduction $record, float $quantity): string
productionTableYieldColor(string $state, KitchenProduction $record): string
~~~

The configure method must retain, in order:

~~~text
modifyQueryUsing
columns
filters
defaultSort
recordActions with RecordActionsPosition::BeforeColumns
stackedOnMobile
~~~

Change only these authorization calls inside the void action:

~~~php
->visible(
    fn (KitchenProduction $record): bool => KitchenProductionResource::canVoid($record),
)
->authorize(
    fn (KitchenProduction $record): bool => KitchenProductionResource::canVoid($record),
)
~~~

Keep KitchenStockService injection, the transactional reversal call, notification text, confirmation copy, validation, eager loading, filter semantics, default sort, record-action position, and stacked mobile behavior unchanged.

- [ ] **Step 4: Make the resource delegate its table and finish import cleanup**

Add the new import and reduce KitchenProductionResource::table() to:

~~~php
use App\Filament\Admin\Resources\KitchenProductions\Tables\KitchenProductionsTable;

public static function table(Table $table): Table
{
    return KitchenProductionsTable::configure($table);
}
~~~

Remove productionDatePresetBounds(), productionTableYieldSummary(), productionTableQuantity(), and productionTableYieldColor() from the resource after their unchanged bodies are present on KitchenProductionsTable.

The resource's final non-page imports should be limited to:

~~~php
use App\Filament\Admin\Resources\KitchenProductions\Schemas\KitchenProductionForm;
use App\Filament\Admin\Resources\KitchenProductions\Schemas\KitchenProductionInfolist;
use App\Filament\Admin\Resources\KitchenProductions\Tables\KitchenProductionsTable;
use App\Filament\Admin\Resources\SecureResource;
use App\Models\KitchenProduction;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
~~~

Retain the four page-class imports as well. Keep canViewAny(), canCreate(), canEdit(), canVoid(), infolist(), and getPages() behavior unchanged.

- [ ] **Step 5: Run focused structural, table, action, authorization, and responsive tests**

Run:

~~~bash
php artisan test \
  tests/Feature/KitchenProductionResourceStructureTest.php \
  tests/Feature/KitchenProductionDetailsPageTest.php \
  tests/Feature/KitchenProductionEditProtectionTest.php \
  tests/Feature/KitchenProductionRestaurantScopeTest.php \
  tests/Feature/KitchenProductionTableFiltersTest.php \
  tests/Feature/KitchenProductionTableReadabilityTest.php \
  tests/Feature/KitchenProductionVoidPermissionTest.php
~~~

Expected: PASS. These tests cover page/action registration, editing, voiding, permission checks, filter semantics, eager loading, semantic yield colors, action placement, and mobile presentation.

- [ ] **Step 6: Format, inspect, and commit the completed split**

Run:

~~~bash
vendor/bin/pint \
  app/Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php \
  app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionForm.php \
  app/Filament/Admin/Resources/KitchenProductions/Tables/KitchenProductionsTable.php \
  tests/Feature/KitchenProductionResourceStructureTest.php
php -l app/Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php
php -l app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionForm.php
php -l app/Filament/Admin/Resources/KitchenProductions/Tables/KitchenProductionsTable.php
php -l tests/Feature/KitchenProductionResourceStructureTest.php
git diff --check
git diff --stat HEAD
git status --short
git add \
  app/Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php \
  app/Filament/Admin/Resources/KitchenProductions/Tables/KitchenProductionsTable.php \
  tests/Feature/KitchenProductionResourceStructureTest.php
git commit -m "refactor: split kitchen production resource configuration"
~~~

Expected: the resource is a thin coordinator, the dedicated classes contain the moved behavior, the structural and focused workflow tests pass, and no unrelated file is committed.

---

### Task 3: Complete regression verification and independent review

**Files:**

- Verify: app/Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php
- Verify: app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionForm.php
- Verify: app/Filament/Admin/Resources/KitchenProductions/Tables/KitchenProductionsTable.php
- Verify: tests/Feature/KitchenProductionResourceStructureTest.php
- Verify: all tests/Feature/KitchenProduction*.php files

**Interfaces:**

- Consumes: the committed form and table configuration boundaries from Tasks 1 and 2.
- Produces: evidence that the refactor preserves creation, shortages, units, recipe estimates, editing, voiding, authorization, filtering, details, and responsive presentation across the focused and complete application suites.

- [ ] **Step 1: Run the complete Kitchen Production test surface**

Run:

~~~bash
php artisan test tests/Feature/KitchenProduction*.php
~~~

Expected: every Kitchen Production feature, report, migration, permission, UI, stock, and workflow test passes.

- [ ] **Step 2: Run the full application suite**

Run:

~~~bash
php artisan test
~~~

Expected: PASS with no failures or errors. Record the test and assertion totals for the final handoff.

- [ ] **Step 3: Run final formatting, syntax, and repository checks**

Run:

~~~bash
vendor/bin/pint --test \
  app/Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php \
  app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionForm.php \
  app/Filament/Admin/Resources/KitchenProductions/Tables/KitchenProductionsTable.php \
  tests/Feature/KitchenProductionResourceStructureTest.php
php -l app/Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php
php -l app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionForm.php
php -l app/Filament/Admin/Resources/KitchenProductions/Tables/KitchenProductionsTable.php
php -l tests/Feature/KitchenProductionResourceStructureTest.php
git diff --check HEAD~2..HEAD
git status --short
git log -3 --oneline
~~~

Expected: Pint and syntax checks pass, the two implementation commits have no whitespace errors, and the worktree is clean.

- [ ] **Step 4: Request an independent read-only code review**

Invoke superpowers:requesting-code-review and give the reviewer:

~~~text
Review the Kitchen Production resource refactor against
docs/superpowers/specs/2026-09-06-kitchen-production-resource-refactor-design.md.
Inspect the two implementation commits and verify that form/table behavior was
moved without semantic changes, KitchenProductionResource remains the
authorization owner, no obsolete commented schema remains, no helper visibility
was broadened, and the regression coverage protects the new boundaries.
Do not edit files. Report findings with severity and exact file/line evidence.
~~~

Expected: the reviewer either reports no actionable findings or supplies precise evidence that can be reproduced.

- [ ] **Step 5: Resolve verified review findings and repeat affected checks**

If the reviewer reports an actionable defect, first add a failing regression assertion to KitchenProductionResourceStructureTest.php or the closest existing Kitchen Production behavioral test, run that test to confirm the failure, apply the smallest behavior-preserving correction, and rerun:

~~~bash
php artisan test tests/Feature/KitchenProductionResourceStructureTest.php
php artisan test tests/Feature/KitchenProduction*.php
vendor/bin/pint --test \
  app/Filament/Admin/Resources/KitchenProductions/KitchenProductionResource.php \
  app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionForm.php \
  app/Filament/Admin/Resources/KitchenProductions/Tables/KitchenProductionsTable.php \
  tests/Feature/KitchenProductionResourceStructureTest.php
git diff --check
~~~

When a correction was required, commit only its test and fix:

~~~bash
git add \
  app/Filament/Admin/Resources/KitchenProductions \
  tests/Feature/KitchenProductionResourceStructureTest.php
git commit -m "fix: address kitchen production refactor review"
~~~

If the reviewer reports no actionable defect, make no additional commit.

- [ ] **Step 6: Capture final completion evidence**

Run:

~~~bash
git status --short
git log -4 --oneline
~~~

Expected: the worktree is clean and the implementation commits are visible. The final handoff must list the changed files, behavioral areas verified, exact test totals, and every new commit hash and subject.
