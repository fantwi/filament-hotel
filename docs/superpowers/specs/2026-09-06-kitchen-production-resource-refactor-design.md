# Kitchen Production Resource Refactor Design

Date: 2026-09-06  
Status: Approved

## Context

The Kitchen Production Filament resource has grown to more than 1,000 lines while implementing batch creation, restaurant-scoped ingredient use, recipe estimates, live stock feedback, production-unit and yield guidance, protected editing, batch voiding, filtering, and responsive table presentation.

The resource currently mixes three responsibilities:

1. Resource metadata, authorization, navigation, and page registration.
2. Form schema construction and form-specific presentation helpers.
3. Table construction, filters, record actions, and table-specific presentation helpers.

It also contains an obsolete commented-out form definition. This duplication makes the maintained schema harder to identify and increases the chance that future changes are applied to dead code.

## Goals

- Make KitchenProductionResource a small coordinator.
- Move form construction and its private helpers into a dedicated schema class.
- Move table construction and its private helpers into a dedicated table class.
- Remove the obsolete commented-out form definition.
- Preserve every current UI state path, validation rule, query, permission, service boundary, action, notification, and responsive behavior.
- Add structural regression coverage for the new boundaries.
- Retain the existing behavioral test suite as the authoritative workflow protection.

## Non-goals

- No user-visible redesign or new Kitchen Production feature.
- No implementation of issue 13's operational summary, shortcuts, empty states, or CSV export.
- No database migration, route change, permission change, model change, or service API change.
- No broader extraction of calculations into additional support services.
- No refactoring of the existing KitchenProductionInfolist.

## Architecture

### KitchenProductionResource

The resource remains responsible for:

- Model, record-title, navigation, and navigation-group metadata.
- canViewAny(), canCreate(), canEdit(), and canVoid() authorization.
- Delegating form construction to KitchenProductionForm::configure().
- Delegating infolist construction to the existing KitchenProductionInfolist::configure().
- Delegating table construction to KitchenProductionsTable::configure().
- Registering Filament resource pages.

The resource will not import individual form or table components after the refactor.

### KitchenProductionForm

A new class at:

app/Filament/Admin/Resources/KitchenProductions/Schemas/KitchenProductionForm.php

This class owns:

- Responsive form sections and field definitions.
- Restaurant, menu-item, date, quantity, notes, and producer state.
- Mode-aware ingredient entry and explanations.
- Recipe-prefill action and recipe status guidance.
- Canonical production-unit and ingredient-unit presentation.
- Live produced, wasted, net-output, and waste-rate summaries.
- Ingredient availability, remaining-stock, and shortage feedback.
- All private helper methods used only by form component callbacks.

The helpers remain private static methods on this class. No new service is introduced because these methods configure presentation or retrieve state needed only by this schema. Existing domain services remain unchanged.

### KitchenProductionsTable

A new class at:

app/Filament/Admin/Resources/KitchenProductions/Tables/KitchenProductionsTable.php

This class owns:

- Table query modification and eager loading.
- Desktop columns and mobile summary presentation.
- Restaurant, menu-item, category, producer, status, waste, quick-period, and custom-date filters.
- Stable default sorting.
- View, edit, and void record actions.
- Table-only formatting helpers for production quantities, yield summaries, colors, and quick-period bounds.

The void action continues to call KitchenProductionResource::canVoid() so authorization has one owner. The action continues to inject and call KitchenStockService for the transactional reversal.

### Existing pages and infolist

The create, edit, list, and view pages retain their current behavior. The existing KitchenProductionInfolist remains the read-only details implementation. Page URLs and resource registration do not change.

## Data and control flow

### Form

1. Filament calls KitchenProductionResource::form().
2. The resource passes the supplied Schema to KitchenProductionForm::configure().
3. The form class returns the configured schema with the same component names and Livewire state paths used today.
4. Existing create and edit pages receive and mutate the same data shapes.
5. KitchenProductionRecipeService continues to calculate recipe estimates.
6. The create page and KitchenStockService remain authoritative for persistence and locked stock deductions.

### Table

1. Filament calls KitchenProductionResource::table().
2. The resource passes the supplied Table to KitchenProductionsTable::configure().
3. The table class returns the same query, columns, filters, sort, and record actions.
4. View and edit actions continue routing through the resource pages.
5. The void action delegates authorization to the resource and inventory reversal to KitchenStockService.

## Error handling and security

- Existing Filament validation messages and field error paths remain unchanged.
- Cross-restaurant, inactive-ingredient, canonical-unit, insufficient-stock, and waste-over-production protections remain in place.
- Stock mutations remain inside the existing transaction-locked service workflow.
- Protected edit fields remain immutable in both the UI and server-side page mutation logic.
- Void authorization remains centralized in the resource and rechecked when the action executes.
- No callable helper needs broader visibility solely to support the split.

## Test strategy

### Structural regression test

Add a focused test that verifies:

- The resource delegates to KitchenProductionForm and KitchenProductionsTable.
- Both dedicated classes configure the expected Filament schemas.
- The resource no longer imports individual form or table component classes.
- The obsolete commented form definition is absent.
- Component names and key responsive/table contracts remain resolvable through the delegated resource methods.

The structural assertions will target stable architectural boundaries and component identifiers rather than exact line counts or formatting.

### Existing behavioral coverage

Run the complete Kitchen Production test surface, including:

- Creation and ingredient deductions.
- Restaurant scoping and stock ownership.
- Consumption-mode behavior.
- Canonical units and shortage validation.
- Live stock availability.
- Recipe prefill and manual adjustment.
- Yield and waste calculations.
- Editing protection and void reversal.
- Details, filters, permissions, and responsive table presentation.

These existing integration tests remain the main protection against behavior drifting during file movement.

### Final verification

- Run the focused structural test.
- Run every test matching Kitchen Production.
- Run the full application suite.
- Run Pint on every changed PHP file.
- Run PHP syntax checks on the resource and new configuration classes.
- Run git diff --check.
- Obtain an independent read-only review before the implementation commit.

## Rollout

The change is code-only. No migration, seeding, cache invalidation, permission synchronization, or deployment-time data operation is required.

The implementation will be committed as:

refactor: split kitchen production resource configuration
