# Filament Admin UI Remediation Design

**Date:** 2026-08-31

**Status:** Approved in chat on 2026-08-31

## Objective

Implement the ten findings from the Filament admin UI/UX audit in the approved order:

1. Retire unsafe legacy admin pages.
2. Prevent invalid hotel and restaurant reservation selections.
3. Remove the obsolete room-calendar implementation.
4. Add operational search, sorting, and filters to high-use resources.
5. Make dense tables manageable on smaller screens.
6. Replace eager Payment filter refreshes with explicit Apply and Reset actions.
7. Standardize reporting-period controls and vocabulary.
8. Improve occupancy-report scalability.
9. Make Users table filters and visible information agree.
10. Standardize custom-page hierarchy and operational empty states.

The implementation order is `1 -> 2 -> 3 -> 4 -> 5 -> 6 -> 8 -> 7 -> 9 -> 10`, exactly as recommended in the audit.

## Project-wide constraints

- Preserve all currently supported public booking, conference, restaurant, payment, and corporate-credit flows.
- Do not add a database migration or a new Composer or npm dependency.
- Use Filament-native fields, filters, actions, sections, empty states, and responsive column controls wherever possible.
- Maintain light and dark theme compatibility.
- Preserve role-based access rules.
- Use test-driven development for every behavioral change.
- Commit each numbered remediation independently with a descriptive Git commit message.
- Do not bundle unrelated refactors into these commits.

## Architecture

The work is split into four independently testable stages. Access and reservation correctness come first because they can expose broken interfaces or allow invalid records. Resource-table usability follows, then reporting behavior and performance, and finally presentation consistency. Shared availability and report-period behavior will live in focused services or concerns instead of being copied between Filament pages and public controllers.

## Stage A: Access and reservation correctness

### 1. Legacy page retirement

The supported admin landing page remains `RoleDashboard`, and the supported reservation calendar remains `BookingCalendar`.

Legacy page classes become lightweight compatibility shims:

- `Dashboard` redirects to `RoleDashboard`, which already resolves the correct dashboard for the signed-in role.
- `RoomCalendar` redirects to `BookingCalendar`.
- `RoomTimeline` redirects to `BookingCalendar`.
- `LiveStaffDashboard` redirects to the Users resource, which is the maintained staff-management interface.

All four shims remain excluded from navigation. They must not query models or render the obsolete templates. Authorization is delegated to the target page or resource, so a user cannot use a compatibility URL to bypass the target's policy.

Regression tests will assert that authenticated requests redirect to the expected maintained destination and that none of the shims registers a sidebar item.

### 2. Reservation selection correctness

#### Hotel bookings

The admin booking form will place check-in and check-out before room selection. Room choices will:

- exclude rooms whose status is `maintenance`;
- exclude rooms with an overlapping booking whose status is not `cancelled` or `no_show` and whose hold has not expired;
- include the current room while editing the booking itself;
- display room number, room type, and operational status;
- refresh when either stay date changes.

Validation will require check-out to be after check-in and will repeat the availability check during submission. This prevents a stale browser selection from creating an overlapping booking.

#### Restaurant reservations

Restaurant, reservation date, reservation time, and party size become reactive dependencies of the table selector. Table choices will:

- belong to the selected restaurant;
- exclude `maintenance` and `cleaning` tables;
- have capacity greater than or equal to the party size;
- have no overlapping active reservation during the standard 120-minute duration;
- ignore the reservation currently being edited;
- display table number, capacity, location, and reservation fee.

Changing a dependency clears a selected table that no longer qualifies. Submission repeats restaurant ownership, status, capacity, and overlap validation.

The existing conflict calculation in `RestaurantReservationController` will be extracted into a focused availability service and reused by the public and Filament flows. Hotel room selection will similarly reuse a focused room-availability query rather than copying overlap conditions into multiple closures.

Tests will cover maintenance exclusions, overlapping and adjacent stays, expired holds, table ownership, capacity, active two-hour conflicts, adjacent table slots, and edit exclusions.

### 3. Obsolete room-calendar removal

After the compatibility redirects are tested, the obsolete room-calendar and room-timeline templates and their model-querying JavaScript/PHP paths will be removed. The legacy page shims will use a minimal shared redirect fallback view but will normally redirect during `mount()`.

This removes:

- corrupted legend characters;
- direct model queries from Blade;
- CDN-loaded duplicate calendar assets;
- drag-and-drop behavior without adequate feedback;
- missing timeline endpoint requests;
- the per-day occupancy query loop.

`BookingCalendar` and its Vite-managed calendar asset remain the only maintained calendar implementation.

## Stage B: Operational resource tables

### 4. Search, sorting, and filters

The following resources receive task-oriented controls:

- Restaurants: searchable/sortable name and capacity; open and published filters.
- Restaurant Tables: searchable table number and location; restaurant, status, and capacity filters.
- Menu Items: category, availability, featured, and published filters.
- Menu Categories: active and published filters.
- Restaurant Order Items: order and menu-item search plus relationship filters.
- Guests: name/email search plus corporate-account and creation-date filters.
- Restaurant Reservations: reservation date, restaurant, table, status, and payment-status filters.

Relationship filters will be searchable and preloaded only where the option set is reasonably bounded. Filter labels will use guest-facing domain vocabulary instead of database column names.

Tests will assert filter registration and representative query behavior, not just source-code strings.

### 5. Responsive table density

Bookings, Restaurant Reservations, Menu Items, Restaurants, Restaurant Tables, and Payments will identify primary and secondary information.

Primary information remains visible by default:

- human-readable transaction or record identity;
- customer, room, table, restaurant, or menu-item identity;
- operational status;
- the most relevant date or amount.

Secondary identifiers, timestamps, detailed financial columns, images, ordering fields, and less frequently used metadata become toggleable and, where appropriate, hidden by default. Related values may be combined using a column description to reduce horizontal width without hiding meaning.

Tests will assert that the agreed secondary columns are toggleable and that essential columns remain enabled.

## Stage C: Payment and report behavior

### 6. Payment filter workflow

The Payment page keeps separate draft and applied filter state. Editing transaction type, period, start date, or end date does not immediately rerun the table and widgets.

- **Apply filters** validates the complete range and publishes one applied state update.
- **Reset** restores the monthly, all-transaction default and synchronizes both date fields.
- Start date must not be later than end date; invalid input produces an inline validation message rather than silently reversing dates.
- Payment table rows and summary widgets consume only applied state.
- The active scope is summarized above the results.

Tests will cover apply, reset, invalid ranges, transaction-type filtering, and widget/table synchronization.

### 8. Occupancy-report scalability

Occupancy calculations will avoid materializing all overlapping bookings in memory. The implementation will use a database cursor or chunked iteration for portable MySQL/MariaDB and SQLite behavior while clamping each stay to the selected report bounds.

Room-status totals will be consolidated where practical. The report will expose a loading state while the selected range is recalculated. The default range remains bounded; an all-time calculation, if retained for compatibility, must use the memory-bounded path.

Tests will cover clamped room nights, cancelled/no-show exclusions, maintenance capacity, empty data, and a dataset large enough to exercise the streaming path.

### 7. Shared report-period controls

Dashboard behavior already uses a stable applied-filter pattern. Reports will adopt the same vocabulary and interaction:

- Daily
- Weekly
- Monthly
- Quarterly
- Yearly
- Custom range, where the page supports arbitrary dates

A focused reusable concern or value object will resolve period bounds and labels. A shared Filament/Blade control will provide draft state, Apply, and Reset behavior without forcing every keystroke to refresh a report.

Guest, Revenue, Occupancy, Restaurant Order, Kitchen Production, and Payments reports will display the active period consistently. Existing report calculations remain page-specific; only selection, validation, labels, and applied-state behavior are shared.

Tests will cover consistent options, calculated bounds, custom-range validation, and preservation of each report's existing metric semantics.

## Stage D: Presentation consistency

### 9. Users table clarity

Department, shift, and staff status will be restored as badge columns because filters and default department grouping already expose those concepts. The columns will be toggleable to protect table width. Status and shift filters remain only if their backing columns exist in the schema.

Obsolete commented-out table code will be removed. Existing role, corporate account, phone, email, and creation-date behavior remains intact.

Tests will assert visible/toggleable column registration, filter registration, and department grouping.

### 10. Page hierarchy and empty states

Custom Filament pages will render one semantic page title. Booking Calendar and Corporate Receivables will remove duplicate `h1` elements from their content; descriptive copy moves to the Filament page subheading or a section heading. Existing branded hero styling may remain as a supporting section but cannot repeat the page title.

Operational tables for Bookings, Restaurant Orders, Restaurant Reservations, Contact Messages, Guests, and Payments will receive domain-specific empty headings and descriptions. Where authorized, empty states may include a create action; when filters are active, the primary recovery action will reset filters instead of suggesting record creation.

Tests will assert the absence of duplicate page-level headings and the presence of the agreed empty-state copy and actions.

## Error handling and accessibility

- Availability failures must be attached to the relevant room or table field and preserve the remaining form input.
- Redirect shims must never expose a target a user cannot ordinarily access.
- Filter actions must have text labels, icons where useful, keyboard focus support, and inline validation feedback.
- Table badges must include readable text and cannot communicate status by color alone.
- Loading and empty states must use live-region or Filament-native status behavior where available.
- All new UI must support dark mode and single-column mobile layouts.

## Verification

Each numbered change follows a red-green-refactor test cycle and receives its own commit. Before completion:

1. Run targeted tests for every issue.
2. Run PHP syntax checks for modified PHP files.
3. Run Blade compilation and route listing.
4. Run the complete PHPUnit suite without result caching.
5. Build frontend assets if Blade or JavaScript calendar assets changed.
6. Confirm `git status` contains only the intended changes.

If the host filesystem again prevents Laravel from writing logs or PHPUnit cache files, rerun with stderr logging and disabled result caching, then clearly report any environment-blocked checks separately from application failures.
