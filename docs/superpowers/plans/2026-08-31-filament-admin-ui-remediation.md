# Filament Admin UI Remediation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Resolve the ten approved Filament admin UI/UX audit findings in the required order while preserving booking, payment, reporting, and role behavior.

**Architecture:** Implement one independently testable commit per audit issue. Legacy routes become compatibility redirects, reservation choices share availability services, tables use Filament-native controls, and reports share a portable period-range abstraction after the occupancy query is made memory-bounded.

**Tech Stack:** PHP 8.4.25, Laravel 12.67.0, Filament 5.7.6, Livewire, Blade, Tailwind CSS, PHPUnit 11.5.55, MariaDB/MySQL production database, SQLite test database.

**Spec:** `docs/superpowers/specs/2026-08-31-filament-admin-ui-remediation-design.md`

## Global Constraints

- Preserve all currently supported public booking, conference, restaurant, payment, and corporate-credit flows.
- Do not add a database migration or a new Composer or npm dependency.
- Use Filament-native fields, filters, actions, sections, empty states, and responsive column controls wherever possible.
- Maintain light and dark theme compatibility.
- Preserve role-based access rules.
- Use test-driven development for every behavioral change.
- Commit each numbered remediation independently with a descriptive Git commit message.
- Do not bundle unrelated refactors into these commits.

---

### Task 1: Retire legacy admin pages through safe redirects

**Files:**
- Modify: `app/Filament/Admin/Pages/Dashboard.php`
- Modify: `app/Filament/Admin/Pages/LiveStaffDashboard.php`
- Modify: `app/Filament/Admin/Pages/RoomCalendar.php`
- Modify: `app/Filament/Admin/Pages/RoomTimeline.php`
- Test: `tests/Feature/LegacyAdminPageAccessTest.php`
- Test: `tests/Feature/LegacyDashboardNavigationTest.php`

**Interfaces:**
- Consumes: `RoleDashboard::getUrl()`, `BookingCalendar::getUrl()`, `UserResource::getUrl('index')`.
- Produces: `mount(): void` compatibility redirects and hidden navigation for all four legacy pages.

- [ ] **Step 1: Write failing route and navigation tests**

```php
public function test_legacy_calendar_routes_redirect_to_booking_calendar(): void
{
    $admin = User::factory()->create(['department' => 'admin']);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.room-calendar'))
        ->assertRedirect(BookingCalendar::getUrl());

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.room-timeline'))
        ->assertRedirect(BookingCalendar::getUrl());
}

public function test_legacy_staff_page_redirects_to_users_resource(): void
{
    $admin = User::factory()->create(['department' => 'admin']);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.live-staff-dashboard'))
        ->assertRedirect(UserResource::getUrl('index'));
}
```

- [ ] **Step 2: Run the tests and verify the legacy pages still render or fail instead of redirecting**

Run: `php artisan test --compact tests/Feature/LegacyAdminPageAccessTest.php tests/Feature/LegacyDashboardNavigationTest.php --do-not-cache-result`

Expected: FAIL because the compatibility redirects and complete navigation assertions do not exist.

- [ ] **Step 3: Add minimal mount redirects and navigation exclusions**

```php
public static function shouldRegisterNavigation(): bool
{
    return false;
}

public function mount(): void
{
    $this->redirect(BookingCalendar::getUrl());
}
```

Use `RoleDashboard::getUrl()` for `Dashboard`, `BookingCalendar::getUrl()` for both room pages, and `UserResource::getUrl('index')` for `LiveStaffDashboard`. Remove the model-loading statement from `LiveStaffDashboard::mount()` so no legacy query runs before redirecting.

- [ ] **Step 4: Run targeted route, calendar, navigation, and authorization tests**

Run: `php artisan test --compact tests/Feature/LegacyAdminPageAccessTest.php tests/Feature/LegacyDashboardNavigationTest.php tests/Feature/BookingCalendarTest.php tests/Feature/FilamentResourceAuthorizationTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 5: Commit issue 1**

```bash
git add app/Filament/Admin/Pages tests/Feature/LegacyAdminPageAccessTest.php tests/Feature/LegacyDashboardNavigationTest.php
git commit -m "fix: retire legacy admin page access"
```

### Task 2: Enforce room and restaurant-table availability in Filament forms

**Files:**
- Create: `app/Services/RoomAvailabilityService.php`
- Create: `app/Services/RestaurantTableAvailabilityService.php`
- Modify: `app/Services/RoomAssignmentService.php`
- Modify: `app/Http/Controllers/RestaurantReservationController.php`
- Modify: `app/Filament/Admin/Resources/Bookings/Schemas/BookingForm.php`
- Modify: `app/Filament/Admin/Resources/RestaurantReservations/Schemas/RestaurantReservationForm.php`
- Test: `tests/Feature/AdminReservationAvailabilityTest.php`
- Test: `tests/Feature/RestaurantReservationAbuseProtectionTest.php`
- Test: `tests/Feature/BookingWorkflowTest.php`

**Interfaces:**
- Produces: `RoomAvailabilityService::query(string $checkIn, string $checkOut, ?int $exceptBookingId = null): Builder` and `isAvailable(int $roomId, string $checkIn, string $checkOut, ?int $exceptBookingId = null): bool`.
- Produces: `RestaurantTableAvailabilityService::availableTables(int $restaurantId, string $date, string $time, int $partySize, ?int $exceptReservationId = null): Collection` and `isAvailable(int $tableId, int $restaurantId, string $date, string $time, int $partySize, ?int $exceptReservationId = null): bool`.
- Consumes: `Booking::overlapping()`, restaurant reservation duration of 120 minutes, existing active/expired hold semantics.

- [ ] **Step 1: Write failing service tests for hotel-room availability**

```php
public function test_room_availability_excludes_maintenance_and_overlapping_rooms_but_allows_adjacent_stays(): void
{
    $service = app(RoomAvailabilityService::class);

    self::assertFalse($service->isAvailable($maintenance->id, '2026-09-10', '2026-09-12'));
    self::assertFalse($service->isAvailable($booked->id, '2026-09-11', '2026-09-13'));
    self::assertTrue($service->isAvailable($booked->id, '2026-09-12', '2026-09-14'));
}

public function test_room_availability_ignores_expired_holds_and_the_booking_being_edited(): void
{
    self::assertTrue($service->isAvailable($room->id, $checkIn, $checkOut, $booking->id));
    self::assertTrue($service->isAvailable($expiredHoldRoom->id, $checkIn, $checkOut));
}
```

- [ ] **Step 2: Write failing service tests for restaurant-table availability**

```php
public function test_table_availability_enforces_restaurant_status_capacity_and_two_hour_conflicts(): void
{
    $service = app(RestaurantTableAvailabilityService::class);

    self::assertFalse($service->isAvailable($otherRestaurantTable->id, $restaurant->id, '2026-09-10', '18:00', 2));
    self::assertFalse($service->isAvailable($smallTable->id, $restaurant->id, '2026-09-10', '18:00', 4));
    self::assertFalse($service->isAvailable($reservedTable->id, $restaurant->id, '2026-09-10', '19:00', 2));
    self::assertTrue($service->isAvailable($reservedTable->id, $restaurant->id, '2026-09-10', '20:00', 2));
}
```

- [ ] **Step 3: Run the new service tests and verify the classes are missing**

Run: `php artisan test --compact tests/Feature/AdminReservationAvailabilityTest.php --do-not-cache-result`

Expected: FAIL because both availability services are undefined.

- [ ] **Step 4: Implement portable availability services**

```php
public function query(string $checkIn, string $checkOut, ?int $exceptBookingId = null): Builder
{
    return Room::query()
        ->with('roomType')
        ->where('status', '!=', 'maintenance')
        ->whereDoesntHave('bookings', function (Builder $query) use ($checkIn, $checkOut, $exceptBookingId): void {
            $query->when($exceptBookingId, fn (Builder $query) => $query->whereKeyNot($exceptBookingId))
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->where(fn (Builder $query) => $query->whereNull('hold_status')->orWhere('hold_status', '!=', 'expired'))
                ->overlapping($checkIn, $checkOut);
        });
}
```

For tables, load only same-date active reservations for qualified restaurant tables, then use Carbon intervals to keep the conflict calculation portable across MariaDB and SQLite. Treat confirmed and checked-in reservations as active; treat pending reservations as active only while `hold_until` is in the future.

- [ ] **Step 5: Reuse the services in assignment and public reservation flows**

Replace `RoomAssignmentService`'s local overlap loop with `RoomAvailabilityService::query(...)->where('room_type_id', $roomTypeId)->orderBy('room_number')->first()`. Replace the public controller's table ownership, capacity, and overlap block with `RestaurantTableAvailabilityService::isAvailable(...)`, preserving existing field-specific error text.

- [ ] **Step 6: Make both Filament forms reactive and submit-safe**

```php
DatePicker::make('check_out')
    ->after('check_in')
    ->live()
    ->required();

Select::make('room_id')
    ->options(fn (Get $get, ?Booking $record): array => app(RoomAvailabilityService::class)
        ->query($get('check_in'), $get('check_out'), $record?->id)
        ->get()
        ->mapWithKeys(fn (Room $room) => [$room->id => "Room {$room->room_number} · {$room->roomType->name} · ".str($room->status)->headline()])
        ->all())
    ->rule(fn (Get $get, ?Booking $record) => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
        if (! app(RoomAvailabilityService::class)->isAvailable((int) $value, $get('check_in'), $get('check_out'), $record?->id)) {
            $fail('This room is unavailable for the selected dates.');
        }
    });
```

Build the restaurant table options from restaurant, date, time, and party-size state. Add `afterStateUpdated` callbacks to clear `restaurant_table_id` when a dependency changes, and repeat `isAvailable()` through a field rule.

- [ ] **Step 7: Run all reservation-flow regression tests**

Run: `php artisan test --compact tests/Feature/AdminReservationAvailabilityTest.php tests/Feature/RestaurantReservationAbuseProtectionTest.php tests/Feature/RestaurantReservationEstimateTest.php tests/Feature/BookingWorkflowTest.php tests/Feature/PublicPagesTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 8: Commit issue 2**

```bash
git add app/Services app/Http/Controllers/RestaurantReservationController.php app/Filament/Admin/Resources/Bookings/Schemas/BookingForm.php app/Filament/Admin/Resources/RestaurantReservations/Schemas/RestaurantReservationForm.php tests/Feature/AdminReservationAvailabilityTest.php tests/Feature/RestaurantReservationAbuseProtectionTest.php tests/Feature/BookingWorkflowTest.php
git commit -m "fix: enforce admin reservation availability"
```

### Task 3: Remove obsolete calendar implementation code

**Files:**
- Create: `resources/views/filament/admin/pages/redirecting.blade.php`
- Modify: `app/Filament/Admin/Pages/LiveStaffDashboard.php`
- Modify: `app/Filament/Admin/Pages/RoomCalendar.php`
- Modify: `app/Filament/Admin/Pages/RoomTimeline.php`
- Delete: `resources/views/filament/admin/pages/live-staff-dashboard.blade.php`
- Delete: `resources/views/filament/admin/pages/room-calendar.blade.php`
- Delete: `resources/views/filament/admin/pages/room-timeline.blade.php`
- Test: `tests/Feature/LegacyAdminPageAccessTest.php`

**Interfaces:**
- Consumes: redirects established in Task 1.
- Produces: one dependency-free fallback view for all redirect shims.

- [ ] **Step 1: Add a failing source regression test**

```php
public function test_legacy_page_shims_do_not_retain_obsolete_queries_or_calendar_assets(): void
{
    self::assertFileDoesNotExist(resource_path('views/filament/admin/pages/room-calendar.blade.php'));
    self::assertFileDoesNotExist(resource_path('views/filament/admin/pages/room-timeline.blade.php'));
    self::assertStringNotContainsString('getOccupancyHeatmap', file_get_contents(app_path('Filament/Admin/Pages/RoomCalendar.php')));
    self::assertStringNotContainsString('User::with', file_get_contents(app_path('Filament/Admin/Pages/LiveStaffDashboard.php')));
}
```

- [ ] **Step 2: Run the test and verify obsolete files and methods are detected**

Run: `php artisan test --compact tests/Feature/LegacyAdminPageAccessTest.php --do-not-cache-result`

Expected: FAIL because the old templates and RoomCalendar query methods still exist.

- [ ] **Step 3: Reduce redirect shims and add the fallback view**

```blade
<x-filament-panels::page>
    <div class="text-sm text-gray-500 dark:text-gray-400" role="status">Redirecting to the maintained admin page…</div>
</x-filament-panels::page>
```

Point the three shim classes at `filament.admin.pages.redirecting`; remove obsolete imports, properties, queries, event mapping, and heatmap methods; then delete the three unreachable templates.

- [ ] **Step 4: Verify routes, Vite calendar ownership, and Blade compilation**

Run: `php artisan test --compact tests/Feature/LegacyAdminPageAccessTest.php tests/Feature/BookingCalendarTest.php --do-not-cache-result`

Run: `php artisan view:cache && php artisan view:clear`

Expected: PASS.

- [ ] **Step 5: Commit issue 3**

```bash
git add -A app/Filament/Admin/Pages resources/views/filament/admin/pages tests/Feature/LegacyAdminPageAccessTest.php
git commit -m "refactor: remove obsolete admin calendar views"
```

### Task 4: Add operational resource search, sorting, and filters

**Files:**
- Modify: `app/Filament/Admin/Resources/Restaurants/Tables/RestaurantsTable.php`
- Modify: `app/Filament/Admin/Resources/RestaurantTables/Tables/RestaurantTablesTable.php`
- Modify: `app/Filament/Admin/Resources/MenuItems/Tables/MenuItemsTable.php`
- Modify: `app/Filament/Admin/Resources/MenuCategories/Tables/MenuCategoriesTable.php`
- Modify: `app/Filament/Admin/Resources/RestaurantOrderItems/Tables/RestaurantOrderItemsTable.php`
- Modify: `app/Filament/Admin/Resources/Guests/Tables/GuestsTable.php`
- Modify: `app/Filament/Admin/Resources/RestaurantReservations/Tables/RestaurantReservationsTable.php`
- Test: `tests/Feature/FilamentOperationalTableFiltersTest.php`

**Interfaces:**
- Produces filter names: `is_open`, `is_published`, `restaurant`, `status`, `capacity`, `category`, `is_available`, `is_featured`, `is_active`, `order`, `menu_item`, `corporate_account`, `created_at`, `reservation_date`, `table`, and `payment_status`.

- [ ] **Step 1: Write failing table-configuration tests**

```php
#[DataProvider('operationalFilters')]
public function test_operational_table_registers_expected_filters(string $tableClass, array $filters): void
{
    $table = $tableClass::configure(Table::make($this->createMock(HasTable::class)));

    foreach ($filters as $filter) {
        self::assertNotNull($table->getFilter($filter), "Missing {$filter} on {$tableClass}");
    }
}

public static function operationalFilters(): array
{
    return [
        [RestaurantsTable::class, ['is_open', 'is_published']],
        [RestaurantTablesTable::class, ['restaurant', 'status', 'capacity']],
        [MenuItemsTable::class, ['category', 'is_available', 'is_featured', 'is_published']],
        [MenuCategoriesTable::class, ['is_active', 'is_published']],
        [RestaurantReservationsTable::class, ['restaurant', 'table', 'reservation_date', 'status', 'payment_status']],
    ];
}

public function test_restaurant_table_status_filter_changes_the_visible_records(): void
{
    $available = RestaurantTable::factory()->create(['status' => 'available']);
    $maintenance = RestaurantTable::factory()->create(['status' => 'maintenance']);

    livewire(ListRestaurantTables::class)
        ->filterTable('status', 'maintenance')
        ->assertCanSeeTableRecords([$maintenance])
        ->assertCanNotSeeTableRecords([$available]);
}
```

- [ ] **Step 2: Run the test and verify missing filters fail**

Run: `php artisan test --compact tests/Feature/FilamentOperationalTableFiltersTest.php --do-not-cache-result`

Expected: FAIL on the first unregistered filter.

- [ ] **Step 3: Add Filament-native columns and filters**

```php
TextColumn::make('name')->searchable()->sortable();

TernaryFilter::make('is_published')->label('Published');

SelectFilter::make('restaurant')
    ->relationship('restaurant', 'name')
    ->searchable()
    ->preload();

Filter::make('reservation_date')
    ->schema([
        DatePicker::make('from'),
        DatePicker::make('until')->afterOrEqual('from'),
    ])
    ->query(fn (Builder $query, array $data): Builder => $query
        ->when($data['from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('reservation_date', '>=', $date))
        ->when($data['until'] ?? null, fn (Builder $query, string $date) => $query->whereDate('reservation_date', '<=', $date)));
```

Use a numeric capacity form for restaurant tables with `minimum` and `maximum` values. Add search and sorting only to textual/numeric columns where the backing database field is unambiguous.

- [ ] **Step 4: Run the new tests and existing contact/order tests**

Run: `php artisan test --compact tests/Feature/FilamentOperationalTableFiltersTest.php tests/Feature/ContactMessagesTableTest.php tests/Feature/RestaurantOrderReportTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 5: Commit issue 4**

```bash
git add app/Filament/Admin/Resources tests/Feature/FilamentOperationalTableFiltersTest.php
git commit -m "feat: add operational Filament table filters"
```

### Task 5: Make dense resource tables responsive through column controls

**Files:**
- Modify: `app/Filament/Admin/Resources/Bookings/Tables/BookingsTable.php`
- Modify: `app/Filament/Admin/Resources/RestaurantReservations/Tables/RestaurantReservationsTable.php`
- Modify: `app/Filament/Admin/Resources/MenuItems/Tables/MenuItemsTable.php`
- Modify: `app/Filament/Admin/Resources/Restaurants/Tables/RestaurantsTable.php`
- Modify: `app/Filament/Admin/Resources/RestaurantTables/Tables/RestaurantTablesTable.php`
- Modify: `app/Filament/Admin/Resources/Payments/Tables/PaymentsTable.php`
- Test: `tests/Feature/FilamentResponsiveTablesTest.php`

**Interfaces:**
- Consumes: columns configured by Tasks 4 and existing Payment display accessors.
- Produces: toggleable secondary columns with deterministic hidden-by-default state.

- [ ] **Step 1: Write failing toggleability tests**

```php
public function test_booking_secondary_columns_are_toggleable_and_hidden_by_default(): void
{
    $table = BookingsTable::configure(Table::make($this->createMock(HasTable::class)));

    foreach (['nights', 'total_paid', 'balance'] as $name) {
        $column = $table->getColumn($name);
        self::assertTrue($column->isToggleable());
        self::assertTrue($column->isToggledHiddenByDefault());
    }

    self::assertFalse($table->getColumn('guest.full_name')->isToggledHiddenByDefault());
}
```

- [ ] **Step 2: Run the test and verify secondary columns are not toggleable**

Run: `php artisan test --compact tests/Feature/FilamentResponsiveTablesTest.php --do-not-cache-result`

Expected: FAIL on `isToggleable()` or `isToggledHiddenByDefault()`.

- [ ] **Step 3: Configure column density**

```php
TextColumn::make('balance')
    ->money('GHS')
    ->toggleable(isToggledHiddenByDefault: true);

TextColumn::make('created_at')
    ->dateTime()
    ->toggleable(isToggledHiddenByDefault: true);
```

Keep identity, status, primary date, and primary amount columns visible. Hide image, sort order, capacity details, internal IDs, paid/balance breakdowns, and timestamps by default according to the specification.

- [ ] **Step 4: Run responsive-table and transaction-display tests**

Run: `php artisan test --compact tests/Feature/FilamentResponsiveTablesTest.php tests/Feature/PaymentTransactionDisplayTest.php tests/Feature/BookingsTableStatusColorTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 5: Commit issue 5**

```bash
git add app/Filament/Admin/Resources tests/Feature/FilamentResponsiveTablesTest.php
git commit -m "feat: improve responsive Filament table density"
```

### Task 6: Add explicit Apply and Reset behavior to Payment filters

**Files:**
- Modify: `app/Filament/Admin/Resources/Payments/Pages/ListPayments.php`
- Modify: `app/Services/PaymentReportFilters.php`
- Modify: `app/Filament/Admin/Widgets/PaymentReportStats.php`
- Test: `tests/Feature/PaymentReportStatsTest.php`

**Interfaces:**
- Produces: `ListPayments::$draftFilters`, `applyPaymentFilters(): void`, `resetPaymentFilters(): void`, and `activeFilterLabel(): string`.
- Consumes: inherited committed `$filters` state from `HasFiltersForm`; `PaymentReportFilters::dateRange()` must reject reversed ranges instead of swapping them.

- [ ] **Step 1: Write failing draft/apply/reset tests**

```php
public function test_payment_filters_change_only_after_apply_and_reset_to_monthly_defaults(): void
{
    $page = new ListPayments;
    $page->filters = ['transaction_type' => 'all', 'period' => 'monthly'];
    $page->draftFilters = ['transaction_type' => 'food_orders', 'period' => 'daily'];

    self::assertSame('all', $page->filters['transaction_type']);
    $page->applyPaymentFilters();
    self::assertSame('food_orders', $page->filters['transaction_type']);

    $page->resetPaymentFilters();
    self::assertSame('all', $page->filters['transaction_type']);
    self::assertSame('monthly', $page->filters['period']);
}
```

Add a Livewire test that sets a start date after the end date, calls `applyPaymentFilters`, and asserts validation errors for `draftFilters.start_date`.

- [ ] **Step 2: Run Payment tests and verify live-filter behavior fails the new assertions**

Run: `php artisan test --compact tests/Feature/PaymentReportStatsTest.php --do-not-cache-result`

Expected: FAIL because draft state and actions do not exist.

- [ ] **Step 3: Port the dashboard draft-filter pattern to ListPayments**

```php
public ?array $draftFilters = null;

public function applyPaymentFilters(): void
{
    $this->validate([
        'draftFilters.start_date' => ['required', 'date', 'before_or_equal:draftFilters.end_date'],
        'draftFilters.end_date' => ['required', 'date', 'after_or_equal:draftFilters.start_date'],
    ]);

    $this->filters = $this->draftFilters;
    $this->resetTable();
}
```

Set the schema state path to `draftFilters`, change all fields to `live(condition: false)`, add Filament `Actions` for Apply and Reset, and display the committed type/date label. Keep widgets bound to committed `pageFilters`.

- [ ] **Step 4: Run Payment page, widget, and query tests**

Run: `php artisan test --compact tests/Feature/PaymentReportStatsTest.php tests/Feature/PaymentTransactionDisplayTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 5: Commit issue 6**

```bash
git add app/Filament/Admin/Resources/Payments/Pages/ListPayments.php app/Services/PaymentReportFilters.php app/Filament/Admin/Widgets/PaymentReportStats.php tests/Feature/PaymentReportStatsTest.php
git commit -m "feat: defer Payment filters until apply"
```

### Task 8: Make occupancy calculations memory-bounded

**Files:**
- Modify: `app/Filament/Admin/Pages/OccupancyReport.php`
- Modify: `resources/views/filament/admin/pages/occupancy-report.blade.php`
- Test: `tests/Feature/OccupancyReportTest.php`

**Interfaces:**
- Produces: `bookedRoomNights(Carbon $start, Carbon $end): int` using `lazyById()` or `cursor()` and a `wire:loading` report state.
- Preserves: `report()` keys consumed by `OccupancyStats` and the Blade page.

- [ ] **Step 1: Write failing clamping and streaming-structure tests**

```php
public function test_occupancy_clamps_stays_to_period_and_excludes_cancelled_records(): void
{
    $page = new OccupancyReport;
    $page->period = 'this_month';

    self::assertSame(2, $page->report()['bookedRoomNights']);
}

public function test_occupancy_report_uses_a_memory_bounded_booking_iterator(): void
{
    $source = file_get_contents(app_path('Filament/Admin/Pages/OccupancyReport.php'));
    self::assertMatchesRegularExpression('/lazyById|cursor/', $source);
    self::assertStringNotContainsString("->get(['id', 'check_in', 'check_out'])", $source);
}

public function test_occupancy_report_processes_a_large_booking_set(): void
{
    Booking::query()->insert(collect(range(1, 1100))->map(fn (int $index): array => [
        'guest_id' => $guest->id,
        'room_id' => $room->id,
        'check_in' => now()->startOfMonth()->addDay(),
        'check_out' => now()->startOfMonth()->addDays(2),
        'total_price' => 100,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ])->all());

    self::assertSame(1100, (new OccupancyReport)->report()['bookedRoomNights']);
}
```

- [ ] **Step 2: Run occupancy tests and verify eager materialization is detected**

Run: `php artisan test --compact tests/Feature/OccupancyReportTest.php --do-not-cache-result`

Expected: FAIL because the report calls `get()`.

- [ ] **Step 3: Extract memory-bounded room-night calculation**

```php
private function bookedRoomNights(Carbon $periodStart, Carbon $periodEnd): int
{
    return Booking::query()
        ->whereDate('check_in', '<', $periodEnd->toDateString())
        ->whereDate('check_out', '>', $periodStart->toDateString())
        ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
        ->select(['id', 'check_in', 'check_out'])
        ->lazyById()
        ->sum(function (Booking $booking) use ($periodStart, $periodEnd): int {
            $checkIn = Carbon::parse($booking->check_in)->max($periodStart);
            $checkOut = Carbon::parse($booking->check_out)->min($periodEnd);

            return max(0, $checkIn->diffInDays($checkOut));
        });
}
```

Add `wire:loading` status text to the period control and report container. Consolidate room status counts into a single aggregate query if the generated SQL remains portable to SQLite.

- [ ] **Step 4: Run occupancy and widget regression tests**

Run: `php artisan test --compact tests/Feature/OccupancyReportTest.php tests/Feature/DashboardDateRangeTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 5: Commit issue 8**

```bash
git add app/Filament/Admin/Pages/OccupancyReport.php resources/views/filament/admin/pages/occupancy-report.blade.php tests/Feature/OccupancyReportTest.php
git commit -m "perf: stream occupancy booking calculations"
```

### Task 7: Standardize applied report-period controls

**Files:**
- Create: `app/Support/Reporting/ReportPeriod.php`
- Create: `app/Filament/Admin/Concerns/InteractsWithReportPeriod.php`
- Create: `resources/views/components/filament/report-period-controls.blade.php`
- Modify: `app/Services/PaymentReportFilters.php`
- Modify: `app/Filament/Admin/Pages/GuestReport.php`
- Modify: `app/Filament/Admin/Pages/RevenueReport.php`
- Modify: `app/Filament/Admin/Pages/OccupancyReport.php`
- Modify: `app/Filament/Admin/Pages/RestaurantOrderReport.php`
- Modify: `app/Filament/Admin/Pages/KitchenProductionReport.php`
- Modify: `resources/views/filament/admin/pages/guest-report.blade.php`
- Modify: `resources/views/filament/admin/pages/revenue-report.blade.php`
- Modify: `resources/views/filament/admin/pages/occupancy-report.blade.php`
- Modify: `resources/views/filament/admin/pages/restaurant-order-report.blade.php`
- Modify: `resources/views/filament/admin/pages/kitchen-production-report.blade.php`
- Test: `tests/Unit/ReportPeriodTest.php`
- Test: `tests/Feature/ReportPeriodControlsTest.php`
- Test: existing report feature tests.

**Interfaces:**
- Produces: `ReportPeriod::options(): array`, `ReportPeriod::range(string $period, ?string $start = null, ?string $end = null): array{Carbon, Carbon}`, and `ReportPeriod::label(...)`.
- Produces concern properties `$period`, `$draftPeriod`, `$startDate`, `$endDate`, `$draftStartDate`, `$draftEndDate`; actions `applyReportPeriod()` and `resetReportPeriod()`; helpers `periodBounds()`, `periodLabel()`, and `forReportPeriod(Builder $query, string $column = 'created_at')`.
- Consumes: existing page-specific report queries and Task 6 Payment applied filters.

- [ ] **Step 1: Write failing value-object tests**

```php
public function test_report_period_options_and_ranges_use_one_vocabulary(): void
{
    self::assertSame(
        ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'],
        array_keys(ReportPeriod::options()),
    );

    [$start, $end] = ReportPeriod::range('quarterly');
    self::assertTrue($start->isStartOfQuarter());
    self::assertSame(now()->toDateString(), $end->toDateString());
}

public function test_custom_report_period_rejects_a_reversed_range(): void
{
    $this->expectException(InvalidArgumentException::class);
    ReportPeriod::range('custom', '2026-09-20', '2026-09-10');
}
```

- [ ] **Step 2: Run unit tests and verify ReportPeriod is undefined**

Run: `php artisan test --compact tests/Unit/ReportPeriodTest.php --do-not-cache-result`

Expected: FAIL because `ReportPeriod` does not exist.

- [ ] **Step 3: Implement ReportPeriod and the reusable concern**

```php
final class ReportPeriod
{
    public static function options(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            'custom' => 'Custom range',
        ];
    }
}
```

The concern initializes draft and applied state, validates custom dates, commits only from `applyReportPeriod()`, resets to monthly, and resets a named paginator when the consuming page defines one.

- [ ] **Step 4: Write and run failing page integration tests**

```php
#[DataProvider('reportPages')]
public function test_report_pages_use_shared_applied_period_controls(string $pageClass): void
{
    self::assertContains(InteractsWithReportPeriod::class, class_uses_recursive($pageClass));
    $page = new $pageClass;
    self::assertSame('Monthly', $page->periodLabel());
}
```

Run: `php artisan test --compact tests/Feature/ReportPeriodControlsTest.php --do-not-cache-result`

Expected: FAIL until pages adopt the concern.

- [ ] **Step 5: Migrate report pages and Blade controls**

Replace page-local period label/query methods with the concern. Render `<x-filament.report-period-controls />` on all five custom report views. Keep Kitchen Production's From/Until semantics through the concern's custom period. Change Payment yearly label from `Annually` to `Yearly` and delegate preset ranges to `ReportPeriod`.

- [ ] **Step 6: Run the complete report regression group**

Run: `php artisan test --compact tests/Unit/ReportPeriodTest.php tests/Feature/ReportPeriodControlsTest.php tests/Feature/GuestReportTest.php tests/Feature/RevenueReportTest.php tests/Feature/OccupancyReportTest.php tests/Feature/RestaurantOrderReportTest.php tests/Feature/KitchenProductionReportTest.php tests/Feature/PaymentReportStatsTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 7: Commit issue 7**

```bash
git add app/Support/Reporting app/Filament/Admin/Concerns app/Filament/Admin/Pages app/Services/PaymentReportFilters.php resources/views/components/filament resources/views/filament/admin/pages tests/Unit/ReportPeriodTest.php tests/Feature/ReportPeriodControlsTest.php tests/Feature/*ReportTest.php tests/Feature/PaymentReportStatsTest.php
git commit -m "refactor: standardize admin report periods"
```

### Task 9: Align Users table columns, filters, and grouping

**Files:**
- Modify: `app/Filament/Admin/Resources/Users/Tables/UsersTable.php`
- Test: `tests/Feature/UsersTableConfigurationTest.php`

**Interfaces:**
- Produces visible/toggleable `department_label`, `shift`, and `status` badge columns while preserving `department`, `status`, and `shift` filters and department grouping.

- [ ] **Step 1: Write failing Users table tests**

```php
public function test_users_table_exposes_the_fields_it_can_filter_and_group(): void
{
    $table = UsersTable::configure(Table::make($this->createMock(HasTable::class)));

    foreach (['department_label', 'shift', 'status'] as $name) {
        self::assertNotNull($table->getColumn($name));
        self::assertTrue($table->getColumn($name)->isToggleable());
    }

    foreach (['department', 'shift', 'status'] as $name) {
        self::assertNotNull($table->getFilter($name));
    }
}
```

- [ ] **Step 2: Run the test and verify commented-out columns are missing**

Run: `php artisan test --compact tests/Feature/UsersTableConfigurationTest.php --do-not-cache-result`

Expected: FAIL because the three columns are absent.

- [ ] **Step 3: Restore concise badge columns and remove dead comments**

```php
TextColumn::make('department_label')
    ->label('Department')
    ->badge()
    ->sortable()
    ->toggleable(),

TextColumn::make('shift')
    ->badge()
    ->formatStateUsing(fn (?string $state): string => str($state ?? 'Not assigned')->headline())
    ->toggleable(isToggledHiddenByDefault: true),
```

Restore status with a text badge and icon, keep department grouping, and delete the obsolete commented column/filter/group blocks.

- [ ] **Step 4: Run Users authorization and configuration tests**

Run: `php artisan test --compact tests/Feature/UsersTableConfigurationTest.php tests/Feature/UserResourceAuthorizationTest.php tests/Feature/FilamentNavigationGroupingTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 5: Commit issue 9**

```bash
git add app/Filament/Admin/Resources/Users/Tables/UsersTable.php tests/Feature/UsersTableConfigurationTest.php
git commit -m "feat: clarify staff status in Users table"
```

### Task 10: Standardize custom-page hierarchy and operational empty states

**Files:**
- Modify: `app/Filament/Admin/Pages/BookingCalendar.php`
- Modify: `app/Filament/Admin/Pages/CorporateReceivables.php`
- Modify: `resources/views/filament/admin/pages/booking-calendar.blade.php`
- Modify: `resources/views/filament/admin/pages/corporate-receivables.blade.php`
- Modify: `app/Filament/Admin/Resources/Bookings/Tables/BookingsTable.php`
- Modify: `app/Filament/Admin/Resources/RestaurantOrders/Tables/RestaurantOrdersTable.php`
- Modify: `app/Filament/Admin/Resources/RestaurantReservations/Tables/RestaurantReservationsTable.php`
- Modify: `app/Filament/Admin/Resources/ContactMessages/Tables/ContactMessagesTable.php`
- Modify: `app/Filament/Admin/Resources/Guests/Tables/GuestsTable.php`
- Modify: `app/Filament/Admin/Resources/Payments/Tables/PaymentsTable.php`
- Test: `tests/Feature/FilamentPageHierarchyTest.php`
- Test: `tests/Feature/FilamentEmptyStatesTest.php`

**Interfaces:**
- Produces page `getSubheading(): ?string` content without duplicate in-view `h1` elements.
- Produces resource-specific empty-state headings, descriptions, icons, and role-safe actions.

- [ ] **Step 1: Write failing hierarchy and empty-state tests**

```php
public function test_custom_pages_do_not_repeat_the_filament_page_title(): void
{
    $calendar = file_get_contents(resource_path('views/filament/admin/pages/booking-calendar.blade.php'));
    $receivables = file_get_contents(resource_path('views/filament/admin/pages/corporate-receivables.blade.php'));

    self::assertStringNotContainsString('<h1', $calendar);
    self::assertStringNotContainsString('<h1', $receivables);
    self::assertNotEmpty((new BookingCalendar)->getSubheading());
    self::assertNotEmpty((new CorporateReceivables)->getSubheading());
}

public function test_booking_table_has_a_domain_specific_empty_state(): void
{
    $table = BookingsTable::configure(Table::make($this->createMock(HasTable::class)));
    self::assertSame('No hotel bookings found', $table->getEmptyStateHeading());
    self::assertNotEmpty($table->getEmptyStateDescription());
}
```

- [ ] **Step 2: Run the tests and verify duplicate headings and generic empty states fail**

Run: `php artisan test --compact tests/Feature/FilamentPageHierarchyTest.php tests/Feature/FilamentEmptyStatesTest.php --do-not-cache-result`

Expected: FAIL.

- [ ] **Step 3: Move page descriptions into Filament subheadings**

```php
public function getSubheading(): ?string
{
    return 'See hotel stays, conference bookings, and restaurant reservations together.';
}
```

Remove the duplicated `h1` elements. Retain useful hero badges and guidance, but label their inner content with `h2` or section headings.

- [ ] **Step 4: Add operational empty states**

```php
return $table
    ->emptyStateIcon('heroicon-o-calendar-days')
    ->emptyStateHeading('No hotel bookings found')
    ->emptyStateDescription('Create a booking or adjust the active filters to see matching stays.')
    ->emptyStateActions([
        Action::make('resetFilters')
            ->label('Reset filters')
            ->icon('heroicon-o-arrow-path')
            ->action(fn ($livewire) => $livewire->resetTableFilters()),
    ]);
```

Use distinct copy and icons for food orders, table reservations, contact messages, guests, and payments. Add create actions only when the resource already permits creation; otherwise provide descriptive recovery text.

- [ ] **Step 5: Run page, table, and Blade verification**

Run: `php artisan test --compact tests/Feature/FilamentPageHierarchyTest.php tests/Feature/FilamentEmptyStatesTest.php tests/Feature/BookingCalendarTest.php tests/Feature/CorporateReceivablesTest.php tests/Feature/ContactMessagesTableTest.php --do-not-cache-result`

Run: `php artisan view:cache && php artisan view:clear`

Expected: PASS.

- [ ] **Step 6: Commit issue 10**

```bash
git add app/Filament/Admin/Pages app/Filament/Admin/Resources resources/views/filament/admin/pages tests/Feature/FilamentPageHierarchyTest.php tests/Feature/FilamentEmptyStatesTest.php
git commit -m "feat: standardize Filament page and empty states"
```

### Task 11: Complete cross-cutting verification

**Files:**
- Verify only; modify the relevant issue commit if a regression is found.

**Interfaces:**
- Consumes all ten remediation commits.
- Produces a verified clean worktree and final implementation report.

- [ ] **Step 1: Lint every modified PHP file**

Run: `git diff --name-only 197c43e..HEAD -- '*.php' | xargs -r -n1 php -l`

Expected: every file reports `No syntax errors detected`.

- [ ] **Step 2: Compile Blade views and list admin routes**

Run: `php artisan view:cache && php artisan view:clear && php artisan route:list --path=admin`

Expected: commands exit successfully; maintained dashboard/calendar routes exist and compatibility routes redirect.

- [ ] **Step 3: Build frontend assets**

Run: `npm run build`

Expected: Vite build exits successfully without missing calendar or Blade asset errors.

- [ ] **Step 4: Run the complete PHPUnit suite**

Run: `LOG_CHANNEL=stderr php artisan test --compact --do-not-cache-result`

Expected: all tests pass. If filesystem logging fails, preserve the exact output and distinguish the environment error from application assertions.

- [ ] **Step 5: Verify commit order and worktree state**

Run: `git log --oneline --decorate -12 && git status --short && git diff --check 197c43e..HEAD`

Expected: one commit for each issue in order, no unintended working-tree changes, and no whitespace errors.
