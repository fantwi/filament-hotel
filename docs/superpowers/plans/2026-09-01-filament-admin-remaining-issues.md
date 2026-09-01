# Remaining Filament Admin Issues Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Resolve the remaining functional, consistency, responsive-design, accessibility, and navigation issues identified in the latest Filament admin audit, including restoring the missing staff profile page.

**Architecture:** Correct domain behavior first, then establish the official Filament 5 theme pipeline before changing custom page presentation. Reuse a single room-availability service for validation and assignment, retain Filament's built-in profile security workflow through a model-aware subclass, separate draft filters from applied filters, and enforce shared UI expectations through focused regression tests.

**Tech Stack:** PHP 8.4, Laravel 12, Filament 5, Livewire, Blade, Tailwind CSS 4 for the Filament/public Vite pipeline, FullCalendar 6, PHPUnit 11, MariaDB/MySQL in production, SQLite for automated tests.

**Spec:** The Filament admin audit completed on 2026-09-01 and the approved request to restore the staff profile/personal dashboard. This is a delta to `docs/superpowers/plans/2026-08-31-filament-admin-ui-remediation.md`; it does not repeat that plan's completed table, payment-filter, report-period, occupancy-query, Users-table, or general page-hierarchy work.

## Global Constraints

- Preserve the pending admin-dashboard and reporting work already committed in the branch.
- Preserve hotel, conference, restaurant-table, food-order, payment, corporate-credit, kitchen, and guest-facing behavior.
- Do not remove the work-shift database column or workflow in this plan; that separate change was proposed but not approved.
- Do not add a database migration unless a failing test proves one is required. None is expected.
- Keep authorization controlled by existing panel access and resource policies; a profile page must never allow staff to change their own role, department, status, or corporate organization.
- Require the current password before changing email or password and retain Filament's built-in save rate limiting.
- Use the official Filament 5 theme structure and verify both public pages and the admin panel after the Tailwind upgrade.
- Use Filament-native form controls, table columns, filters, buttons, sections, and navigation groups wherever available.
- Maintain light and dark mode behavior, keyboard operation, visible focus states, and mobile-first layouts.
- Write a failing regression test before each behavioral change and commit each task independently.

---

### Task 1: Restore the staff profile page and user-menu link

**Files:**
- Create: `app/Filament/Admin/Pages/Auth/EditProfile.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Create: `tests/Feature/FilamentStaffProfileTest.php`

**Interfaces:**
- Produces: `GET /admin/profile`, route name `filament.admin.auth.profile`, and Filament's built-in `profile` user-menu item.
- Edits: `first_name`, `last_name`, `email`, `phone_number`, and password.
- Displays read-only: department label, role label, and staff status.

- [ ] **Step 1: Write failing panel-registration and route tests**

```php
public function test_admin_panel_registers_the_full_page_staff_profile(): void
{
    $panel = (new AdminPanelProvider($this->app))->panel(Panel::make());

    self::assertSame(EditProfile::class, $panel->getProfilePage());
    self::assertFalse($panel->isProfilePageSimple());
}

public function test_authenticated_staff_can_open_the_profile_page(): void
{
    $admin = User::factory()->create(['department' => 'admin']);

    $this->actingAs($admin)
        ->get(route('filament.admin.auth.profile'))
        ->assertOk()
        ->assertSee('My profile');
}
```

- [ ] **Step 2: Write failing profile-update security tests**

Use `Livewire::test(EditProfile::class)` to prove that:

- first name, last name, phone, and unchanged email save successfully;
- the non-existent writable `name` attribute is never submitted;
- role, department, status, and corporate organization cannot be changed by profile form state;
- duplicate email fails validation;
- changing email or password without `currentPassword` fails;
- a valid current password allows a password change and the stored password remains hashed.

- [ ] **Step 3: Run the profile tests and confirm the missing route/page failure**

Run: `php artisan test --compact tests/Feature/FilamentStaffProfileTest.php --do-not-cache-result`

Expected: FAIL because the panel has no profile page and no `filament.admin.auth.profile` route.

- [ ] **Step 4: Add a User-model-aware Filament profile subclass**

```php
namespace App\Filament\Admin\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    protected static ?string $title = 'My profile';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Personal information')->schema([
                TextInput::make('first_name')->required()->maxLength(255),
                TextInput::make('last_name')->required()->maxLength(255),
                $this->getEmailFormComponent(),
                TextInput::make('phone_number')->tel()->maxLength(50),
            ])->columns(['default' => 1, 'md' => 2]),
            Section::make('Staff account')->schema([
                Placeholder::make('department_display')
                    ->label('Department')
                    ->content(fn (): string => $this->getUser()->department_label),
                Placeholder::make('role_display')
                    ->label('Role')
                    ->content(fn (): string => $this->getUser()->role_name),
                Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn (): string => str($this->getUser()->status)->headline()),
            ])->columns(['default' => 1, 'md' => 3]),
            Section::make('Password')->schema([
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ])->columns(['default' => 1, 'md' => 2]),
        ]);
    }
}
```

Retain `BaseEditProfile::save()`, `handleRecordUpdate()`, password hashing, current-password validation, and rate limiting. Do not duplicate those security-sensitive methods.

- [ ] **Step 5: Register the page in the panel**

```php
use App\Filament\Admin\Pages\Auth\EditProfile;

->login()
->profile(EditProfile::class, isSimple: false)
```

Filament will add the profile item to the top-right user menu automatically.

- [ ] **Step 6: Run the profile and authentication regressions**

Run: `php artisan test --compact tests/Feature/FilamentStaffProfileTest.php tests/Feature/Auth/TwoFactorAuthenticationTest.php tests/Feature/Auth/AuthenticationTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 7: Commit the staff profile fix**

```bash
git add app/Filament/Admin/Pages/Auth/EditProfile.php app/Providers/Filament/AdminPanelProvider.php tests/Feature/FilamentStaffProfileTest.php
git commit -m "feat: add Filament staff profile dashboard"
```

### Task 2: Make department choices and automatic role assignment consistent

**Files:**
- Modify: `app/Models/User.php`
- Modify: `app/Filament/Admin/Resources/Users/Schemas/UserForm.php`
- Create: `tests/Feature/UserDepartmentRoleMappingTest.php`

- [ ] **Step 1: Write failing department/role tests**

```php
public function test_every_configured_department_is_available_to_the_user_form(): void
{
    self::assertSame(User::DEPARTMENTS, User::getDepartments());
    self::assertArrayHasKey('kitchen_manager', User::getDepartments());
    self::assertArrayHasKey('kitchen_staff', User::getDepartments());
}

#[DataProvider('departmentRoles')]
public function test_department_assigns_the_expected_role(string $department, string $role): void
{
    $user = User::factory()->create(['department' => $department]);

    self::assertTrue($user->fresh()->hasRole($role));
}
```

The provider must include `housekeeping => housekeeping`, `kitchen => kitchen_staff`, `kitchen_manager => kitchen_manager`, and `kitchen_staff => kitchen_staff`.

- [ ] **Step 2: Run the mapping tests and verify the housekeeping and kitchen failures**

Run: `php artisan test --compact tests/Feature/UserDepartmentRoleMappingTest.php --do-not-cache-result`

Expected: FAIL because `getDepartments()` omits kitchen roles and housekeeping maps to `housekeeper` instead of the seeded/panel role `housekeeping`.

- [ ] **Step 3: Use one canonical department list and correct role map**

```php
public const DEPARTMENT_ROLE_MAP = [
    'super_admin' => 'super_admin',
    'admin' => 'admin',
    'reception' => 'receptionist',
    'housekeeping' => 'housekeeping',
    'accounting' => 'accountant',
    'management' => 'manager',
    'kitchen' => 'kitchen_staff',
    'kitchen_manager' => 'kitchen_manager',
    'kitchen_staff' => 'kitchen_staff',
    'guest' => 'guest',
];

public static function getDepartments(): array
{
    return self::DEPARTMENTS;
}
```

Keep the User form's super-admin restriction, but have both branches derive options from this canonical list.

- [ ] **Step 4: Test create and department-change role synchronization**

Extend the test to change a housekeeping user to `kitchen_manager`, save, and assert the old role is removed and the new role is assigned.

- [ ] **Step 5: Run Users resource and authorization regressions**

Run: `php artisan test --compact tests/Feature/UserDepartmentRoleMappingTest.php tests/Feature/UserResourceAuthorizationTest.php tests/Feature/FilamentResourceAuthorizationTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 6: Commit the role consistency fix**

```bash
git add app/Models/User.php app/Filament/Admin/Resources/Users/Schemas/UserForm.php tests/Feature/UserDepartmentRoleMappingTest.php
git commit -m "fix: align staff departments with roles"
```

### Task 3: Enforce valid dates and room availability in the Filament booking form

**Files:**
- Create: `app/Services/RoomAvailabilityService.php`
- Modify: `app/Services/RoomAssignmentService.php`
- Modify: `app/Filament/Admin/Resources/Bookings/Schemas/BookingForm.php`
- Create: `tests/Feature/AdminBookingAvailabilityTest.php`
- Modify: `tests/Feature/BookingWorkflowTest.php`

**Interfaces:**
- Produces: `RoomAvailabilityService::query(CarbonInterface|string $checkIn, CarbonInterface|string $checkOut, ?int $exceptBookingId = null): Builder`.
- Produces: `RoomAvailabilityService::isAvailable(int $roomId, CarbonInterface|string $checkIn, CarbonInterface|string $checkOut, ?int $exceptBookingId = null): bool`.

- [ ] **Step 1: Write failing service tests for domain rules**

Cover these cases:

- maintenance rooms are never selectable;
- overlapping confirmed stays are excluded;
- cancelled, no-show, and expired-hold bookings do not block the room;
- adjacent stays where a prior checkout equals a new check-in are allowed;
- the record currently being edited does not conflict with itself;
- today/future dates are accepted and past, equal, or reversed ranges are rejected.

- [ ] **Step 2: Run the availability tests and confirm the service is missing**

Run: `php artisan test --compact tests/Feature/AdminBookingAvailabilityTest.php --do-not-cache-result`

Expected: FAIL because `RoomAvailabilityService` does not exist.

- [ ] **Step 3: Implement one reusable room query**

```php
public function query(string|CarbonInterface $checkIn, string|CarbonInterface $checkOut, ?int $exceptBookingId = null): Builder
{
    return Room::query()
        ->with('roomType')
        ->where('status', '!=', 'maintenance')
        ->whereDoesntHave('bookings', function (Builder $query) use ($checkIn, $checkOut, $exceptBookingId): void {
            $query
                ->when($exceptBookingId, fn (Builder $query) => $query->whereKeyNot($exceptBookingId))
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->where(fn (Builder $query) => $query
                    ->whereNull('hold_status')
                    ->orWhere('hold_status', '!=', 'expired'))
                ->overlapping($checkIn, $checkOut);
        });
}
```

- [ ] **Step 4: Reuse the service in automatic assignment**

Replace `RoomAssignmentService`'s room loop and duplicated conflict query with:

```php
return app(RoomAvailabilityService::class)
    ->query($checkIn, $checkOut)
    ->where('room_type_id', $roomTypeId)
    ->orderBy('room_number')
    ->first();
```

- [ ] **Step 5: Make the Filament booking fields reactive and valid**

```php
DatePicker::make('check_in')
    ->minDate(today())
    ->live()
    ->required();

DatePicker::make('check_out')
    ->minDate(fn (Get $get) => filled($get('check_in')) ? Carbon::parse($get('check_in'))->addDay() : today()->addDay())
    ->after('check_in')
    ->live()
    ->required();
```

Build `room_id` options from `RoomAvailabilityService` only after both dates are present. Clear `room_id` when either date changes, except when the selected room is still available for the edited record. Add a submit-time field rule that calls `isAvailable()` so crafted Livewire requests cannot bypass the option list.

Add `private static function updateTotal(Get $get, Set $set): void` to `BookingForm` and call it from the room, check-in, and check-out callbacks. The helper must return without updating when the room is missing or the dates do not form a valid positive range.

- [ ] **Step 6: Add Livewire form regressions**

Use the Booking resource create/edit pages to assert maintenance rooms, past dates, reversed dates, and conflicting rooms fail validation while an adjacent stay succeeds.

- [ ] **Step 7: Run booking and public-room regressions**

Run: `php artisan test --compact tests/Feature/AdminBookingAvailabilityTest.php tests/Feature/BookingWorkflowTest.php tests/Feature/PublicPagesTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 8: Commit the booking safeguards**

```bash
git add app/Services/RoomAvailabilityService.php app/Services/RoomAssignmentService.php app/Filament/Admin/Resources/Bookings/Schemas/BookingForm.php tests/Feature/AdminBookingAvailabilityTest.php tests/Feature/BookingWorkflowTest.php
git commit -m "fix: validate admin room booking availability"
```

### Task 4: Retire the remaining directly routable legacy pages

**Files:**
- Modify: `app/Filament/Admin/Pages/Dashboard.php`
- Modify: `app/Filament/Admin/Pages/LiveStaffDashboard.php`
- Modify: `resources/views/filament/admin/pages/redirecting.blade.php`
- Delete: `resources/views/filament/admin/pages/dashboard.blade.php`
- Delete: `resources/views/filament/admin/pages/live-staff-dashboard.blade.php`
- Create: `tests/Feature/LegacyAdminPageAccessTest.php`
- Modify: `tests/Feature/LegacyDashboardNavigationTest.php`

- [ ] **Step 1: Write failing authenticated redirect tests**

```php
public function test_legacy_dashboard_redirects_to_role_dashboard(): void
{
    $admin = User::factory()->create(['department' => 'admin']);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertRedirect(RoleDashboard::getUrl());
}

public function test_legacy_live_staff_page_redirects_to_users(): void
{
    $admin = User::factory()->create(['department' => 'admin']);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.live-staff-dashboard'))
        ->assertRedirect(UserResource::getUrl('index'));
}
```

Also assert unauthenticated requests redirect to the Filament login and unauthorized staff cannot use the legacy staff URL to bypass Users resource access.

- [ ] **Step 2: Run the legacy tests and confirm both pages render instead of redirecting**

Run: `php artisan test --compact tests/Feature/LegacyAdminPageAccessTest.php tests/Feature/LegacyDashboardNavigationTest.php --do-not-cache-result`

Expected: FAIL.

- [ ] **Step 3: Convert both pages to dependency-free compatibility shims**

```php
protected string $view = 'filament.admin.pages.redirecting';

public static function shouldRegisterNavigation(): bool
{
    return false;
}

public function mount(): void
{
    $this->redirect(RoleDashboard::getUrl());
}
```

Use `UserResource::getUrl('index')` for `LiveStaffDashboard`; remove its `$staff` property and query. Change the shared view text to the generic “Redirecting to the maintained page…” and delete the obsolete views.

- [ ] **Step 4: Run route, navigation, and resource authorization tests**

Run: `php artisan test --compact tests/Feature/LegacyAdminPageAccessTest.php tests/Feature/LegacyDashboardNavigationTest.php tests/Feature/FilamentNavigationGroupingTest.php tests/Feature/FilamentResourceAuthorizationTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 5: Commit the legacy-route fix**

```bash
git add app/Filament/Admin/Pages/Dashboard.php app/Filament/Admin/Pages/LiveStaffDashboard.php resources/views/filament/admin/pages/redirecting.blade.php tests/Feature/LegacyAdminPageAccessTest.php tests/Feature/LegacyDashboardNavigationTest.php
git rm resources/views/filament/admin/pages/dashboard.blade.php resources/views/filament/admin/pages/live-staff-dashboard.blade.php
git commit -m "fix: redirect remaining legacy admin pages"
```

### Task 5: Enable an official Filament 5 custom theme without breaking public pages

**Files:**
- Create: `resources/css/filament/admin/theme.css`
- Modify: `resources/css/app.css`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `vite.config.js`
- Modify: `package.json`
- Modify: `package-lock.json`
- Delete: `postcss.config.js`
- Delete: `tailwind.config.js`
- Create: `tests/Feature/AdminPanelThemeTest.php`

**Interfaces:**
- Produces: Vite entry `resources/css/filament/admin/theme.css` registered by `Panel::viteTheme()`.
- Preserves: Vite entries for public CSS/JavaScript and FullCalendar.

- [ ] **Step 1: Write failing theme registration tests**

```php
public function test_admin_panel_uses_the_dedicated_filament_theme(): void
{
    $panel = (new AdminPanelProvider($this->app))->panel(Panel::make());

    self::assertSame('resources/css/filament/admin/theme.css', $panel->getViteTheme());
    self::assertFileExists(resource_path('css/filament/admin/theme.css'));
}
```

Also assert `vite.config.js` contains all four entries: `app.css`, `app.js`, `calendar.js`, and the admin theme.

- [ ] **Step 2: Run the test and confirm the commented theme fails**

Run: `php artisan test --compact tests/Feature/AdminPanelThemeTest.php --do-not-cache-result`

Expected: FAIL because the panel returns no Vite theme and the theme file is absent.

- [ ] **Step 3: Upgrade the Vite Tailwind pipeline using Filament 5's supported structure**

Update dev dependencies to Tailwind 4 and `@tailwindcss/vite`, then configure:

```js
import tailwindcss from '@tailwindcss/vite';

plugins: [
    tailwindcss(),
    laravel({
        input: [
            'resources/css/app.css',
            'resources/css/filament/admin/theme.css',
            'resources/js/app.js',
            'resources/js/calendar.js',
        ],
        refresh: true,
    }),
],
```

Remove the Tailwind 3 PostCSS plugin/config after the CSS entries are migrated. Do not leave two Tailwind compilers active.

- [ ] **Step 4: Create explicit source-aware public and Filament CSS entries**

```css
/* resources/css/filament/admin/theme.css */
@import '../../../../vendor/filament/filament/resources/css/theme.css';
@source '../../../../app/Filament/Admin/**/*.php';
@source '../../../../resources/views/filament/admin/**/*.blade.php';
@source '../../../../resources/views/components/filament/**/*.blade.php';
```

Migrate `resources/css/app.css` from `@tailwind` directives to Tailwind 4 imports and explicit `@source` directives for public Blade views, Laravel pagination, and compiled views. Preserve the class-driven dark-mode variant and Figtree font definition now supplied by `tailwind.config.js`.

- [ ] **Step 5: Register the dedicated theme**

```php
->viteTheme('resources/css/filament/admin/theme.css')
```

Delete the commented `->viteTheme('resources/css/app.css')` line.

- [ ] **Step 6: Build assets and verify both CSS entries are emitted**

Run: `npm install`

Run: `npm run build`

Expected: PASS with manifest entries for `resources/css/app.css` and `resources/css/filament/admin/theme.css`; no Tailwind/PostCSS compatibility errors.

- [ ] **Step 7: Run presentation and public-page regressions**

Run: `php artisan test --compact tests/Feature/AdminPanelThemeTest.php tests/Feature/FilamentPageHierarchyTest.php tests/Feature/PublicPagesTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 8: Commit the theme pipeline**

```bash
git add app/Providers/Filament/AdminPanelProvider.php resources/css vite.config.js package.json package-lock.json tests/Feature/AdminPanelThemeTest.php
git rm postcss.config.js tailwind.config.js
git commit -m "feat: enable the Filament admin theme"
```

### Task 6: Make the booking calendar usable on small screens

**Files:**
- Create: `resources/js/booking-calendar-layout.js`
- Modify: `resources/views/filament/admin/pages/booking-calendar.blade.php`
- Create: `tests/js/booking-calendar-layout.test.js`
- Modify: `tests/Feature/BookingCalendarTest.php`

- [ ] **Step 1: Write failing JavaScript layout tests**

Use Node's built-in test runner to assert:

```js
assert.equal(calendarLayout(390).initialView, 'dayGridDay');
assert.deepEqual(calendarLayout(390).headerToolbar, {
    left: 'prev,next', center: 'title', right: 'today',
});
assert.equal(calendarLayout(1024).initialView, 'dayGridMonth');
assert.equal(calendarLayout(1024).headerToolbar.right, 'dayGridMonth,dayGridWeek');
```

- [ ] **Step 2: Run the JavaScript test and confirm the helper is missing**

Run: `node --test tests/js/booking-calendar-layout.test.js`

Expected: FAIL because `resources/js/booking-calendar-layout.js` does not exist.

- [ ] **Step 3: Extract responsive calendar configuration**

```js
export const calendarLayout = (width) => width < 640
    ? {
        initialView: 'dayGridDay',
        headerToolbar: { left: 'prev,next', center: 'title', right: 'today' },
      }
    : {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,dayGridWeek' },
      };
```

Import it through `resources/js/calendar.js` and expose it alongside the FullCalendar constructors, or import it in a page-specific module. Avoid copying the breakpoint logic into Blade.

- [ ] **Step 4: Apply the mobile layout and responsive styles**

Spread `calendarLayout(window.innerWidth)` into the FullCalendar options. Add scoped small-screen rules to:

- stack the toolbar chunks without overlap;
- keep button labels and the calendar title readable;
- reduce event padding and wrap long titles;
- make the three hero summary tiles one column at the narrowest width and three columns from `sm` upward;
- render the guide below the calendar on mobile and sticky at desktop widths.

On a breakpoint change, call `calendar.changeView(nextLayout.initialView)` and `calendar.setOption('headerToolbar', nextLayout.headerToolbar)` only when the mobile/desktop mode actually changes.

- [ ] **Step 5: Extend Blade-level calendar regressions**

Assert the view imports the helper, uses the one-day mobile view, has no hard-coded `window.innerWidth < 640 ? 'dayGridWeek'`, and retains event modal, empty, error, teardown, and sidebar semantics.

- [ ] **Step 6: Run calendar tests and build**

Run: `node --test tests/js/booking-calendar-layout.test.js`

Run: `php artisan test --compact tests/Feature/BookingCalendarTest.php --do-not-cache-result`

Run: `npm run build`

Expected: PASS.

- [ ] **Step 7: Commit the mobile calendar fix**

```bash
git add resources/js/booking-calendar-layout.js resources/js/calendar.js resources/views/filament/admin/pages/booking-calendar.blade.php tests/js/booking-calendar-layout.test.js tests/Feature/BookingCalendarTest.php
git commit -m "fix: make booking calendar mobile friendly"
```

### Task 7: Standardize report and corporate-receivables filter controls

**Files:**
- Modify: `resources/views/components/filament/report-period-controls.blade.php`
- Modify: `app/Filament/Admin/Pages/CorporateReceivables.php`
- Modify: `resources/views/filament/admin/pages/corporate-receivables.blade.php`
- Modify: `tests/Feature/ReportPeriodControlsTest.php`
- Modify: `tests/Feature/CorporateReceivablesTest.php`

- [ ] **Step 1: Write failing applied-state tests**

For `CorporateReceivables`, assert changing `draftSearch`, `draftTransactionType`, `draftOrganizationId`, `draftFromDate`, or `draftUntilDate` does not change `summary()` until `applyFilters()` runs. Assert invalid ranges leave the applied query unchanged, and `clearFilters()` resets draft and applied state.

- [ ] **Step 2: Write failing Filament-control rendering tests**

Assert the shared period control and receivables view use `<x-filament::input.wrapper>`, `<x-filament::input.select>`, and `<x-filament::input>` and no longer use bare `class="fi-input"` or a mixture of live bindings with an Apply button.

- [ ] **Step 3: Run the targeted tests and confirm current live filtering fails the contract**

Run: `php artisan test --compact tests/Feature/ReportPeriodControlsTest.php tests/Feature/CorporateReceivablesTest.php --do-not-cache-result`

Expected: FAIL.

- [ ] **Step 4: Separate draft and applied receivables filters**

```php
public string $draftTransactionType = 'all';
public string $draftSearch = '';
public string $draftOrganizationId = '';
public string $draftFromDate = '';
public string $draftUntilDate = '';

public string $transactionType = 'all';
public string $search = '';
public string $organizationId = '';
public string $fromDate = '';
public string $untilDate = '';
```

`applyFilters()` validates draft dates, copies every draft value to its applied counterpart, and resets pagination. `clearFilters()` resets both sets. Keep `perPage` live because it is pagination state rather than a search predicate; remove the broad `updated()` hook for draft properties.

- [ ] **Step 5: Replace native controls with Filament components**

```blade
<x-filament::input.wrapper>
    <x-filament::input.select wire:model="draftTransactionType" id="receivables-type">
        <option value="all">All services</option>
        @foreach ($typeLabels as $type => $label)
            <option value="{{ $type }}">{{ $label }}</option>
        @endforeach
    </x-filament::input.select>
</x-filament::input.wrapper>

<x-filament::input.wrapper>
    <x-filament::input wire:model="draftFromDate" type="date" id="receivables-from" />
</x-filament::input.wrapper>
```

Apply the same structure to the shared report-period controls. Keep explicit labels, error messages, loading status, Apply, and Clear/Reset actions.

- [ ] **Step 6: Run all report and receivables regressions**

Run: `php artisan test --compact tests/Feature/ReportPeriodControlsTest.php tests/Feature/CorporateReceivablesTest.php tests/Feature/CorporatePaymentServiceTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 7: Commit standardized filters**

```bash
git add resources/views/components/filament/report-period-controls.blade.php app/Filament/Admin/Pages/CorporateReceivables.php resources/views/filament/admin/pages/corporate-receivables.blade.php tests/Feature/ReportPeriodControlsTest.php tests/Feature/CorporateReceivablesTest.php
git commit -m "refactor: standardize Filament filter controls"
```

### Task 8: Make the Conference Rooms table operational and responsive

**Files:**
- Modify: `app/Filament/Admin/Resources/ConferenceRooms/Tables/ConferenceRoomsTable.php`
- Modify: `tests/Feature/FilamentResponsiveTablesTest.php`
- Modify: `tests/Feature/FilamentOperationalTableFiltersTest.php`
- Modify: `tests/Feature/FilamentEmptyStatesTest.php`

- [ ] **Step 1: Add failing table-contract tests**

Assert:

- `id` and `description` are toggleable and hidden by default;
- name, capacity, hourly price, availability, and published state remain visible;
- `description` is line-clamped or limited and wraps safely;
- `price_per_hour` uses `money('GHS')`;
- filters exist for availability, publication, and a practical capacity band;
- the table has a conference-specific empty heading, description, and icon.

- [ ] **Step 2: Run the three table test files and verify failure**

Run: `php artisan test --compact tests/Feature/FilamentResponsiveTablesTest.php tests/Feature/FilamentOperationalTableFiltersTest.php tests/Feature/FilamentEmptyStatesTest.php --do-not-cache-result`

Expected: FAIL for Conference Rooms.

- [ ] **Step 3: Configure responsive columns and formatting**

```php
TextColumn::make('id')->toggleable(isToggledHiddenByDefault: true);
TextColumn::make('description')
    ->limit(80)
    ->wrap()
    ->toggleable(isToggledHiddenByDefault: true);
TextColumn::make('capacity')->numeric()->sortable();
TextColumn::make('price_per_hour')->money('GHS')->sortable();
IconColumn::make('is_available')->boolean();
IconColumn::make('is_published')->boolean();
```

- [ ] **Step 4: Add domain filters and empty state**

Use `TernaryFilter` for availability and publication. Use one select filter for capacity ranges (`1-20`, `21-50`, `51-100`, `100+`) with portable numeric query clauses. Add “No conference rooms found” with an actionable description and presentation-chart icon.

- [ ] **Step 5: Run Conference Room and public conference regressions**

Run: `php artisan test --compact tests/Feature/FilamentResponsiveTablesTest.php tests/Feature/FilamentOperationalTableFiltersTest.php tests/Feature/FilamentEmptyStatesTest.php tests/Feature/PublicPagesTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 6: Commit the Conference Rooms table update**

```bash
git add app/Filament/Admin/Resources/ConferenceRooms/Tables/ConferenceRoomsTable.php tests/Feature/FilamentResponsiveTablesTest.php tests/Feature/FilamentOperationalTableFiltersTest.php tests/Feature/FilamentEmptyStatesTest.php
git commit -m "feat: improve conference room administration"
```

### Task 9: Add semantic table accessibility to custom Filament views

**Files:**
- Modify: `resources/views/filament/admin/widgets/corporate-billing-overview.blade.php`
- Modify: `resources/views/filament/admin/pages/corporate-receivables.blade.php`
- Modify: `resources/views/filament/admin/pages/guest-report.blade.php`
- Modify: `resources/views/filament/admin/pages/kitchen-production-report.blade.php`
- Modify: `resources/views/filament/admin/pages/restaurant-order-report.blade.php`
- Create: `tests/Feature/FilamentCustomTableAccessibilityTest.php`

- [ ] **Step 1: Write a failing semantic-markup test**

For every maintained custom admin Blade file containing `<table>`, assert each table has a non-empty `<caption>` and every `<th>` has `scope="col"` or `scope="row"`. Exclude the legacy live-staff view because Task 4 deletes it.

- [ ] **Step 2: Run the accessibility test and confirm all 32 maintained headers fail**

Run: `php artisan test --compact tests/Feature/FilamentCustomTableAccessibilityTest.php --do-not-cache-result`

Expected: FAIL on the five maintained files.

- [ ] **Step 3: Add concise screen-reader captions and scoped headers**

```blade
<table class="w-full min-w-[800px] text-left text-sm">
    <caption class="sr-only">Outstanding corporate transactions awaiting settlement</caption>
    <thead>
        <tr>
            <th scope="col" class="px-3 py-3 font-semibold">Transaction</th>
            <th scope="col" class="px-3 py-3 font-semibold">Organisation</th>
        </tr>
    </thead>
</table>
```

If a table has row headers, change the identifying first cell from `<td>` to `<th scope="row">`. Do not alter visual table layout.

- [ ] **Step 4: Verify interactive controls and status regions**

Ensure icon-only buttons retain an `aria-label`, modal titles are referenced by `aria-labelledby`, and loading/success/error messages use the existing `role="status"` or `role="alert"` semantics.

- [ ] **Step 5: Run accessibility and page-render regressions**

Run: `php artisan test --compact tests/Feature/FilamentCustomTableAccessibilityTest.php tests/Feature/FilamentPageHierarchyTest.php tests/Feature/CorporateReceivablesTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 6: Commit the accessibility changes**

```bash
git add resources/views/filament/admin tests/Feature/FilamentCustomTableAccessibilityTest.php
git commit -m "fix: add semantics to admin report tables"
```

### Task 10: Complete empty states and Activity Log filters

**Files:**
- Modify: `app/Filament/Admin/Resources/ActivityLogs/Tables/ActivityLogsTable.php`
- Modify: `app/Filament/Admin/Resources/ConferenceFacilities/Tables/ConferenceFacilitiesTable.php`
- Modify: `app/Filament/Admin/Resources/Facilities/Tables/FacilitiesTable.php`
- Modify: `app/Filament/Admin/Resources/Ingredients/Tables/IngredientsTable.php`
- Modify: `app/Filament/Admin/Resources/KitchenStockMovements/Tables/KitchenStockMovementsTable.php`
- Modify: `app/Filament/Admin/Resources/MenuCategories/Tables/MenuCategoriesTable.php`
- Modify: `app/Filament/Admin/Resources/MenuItems/Tables/MenuItemsTable.php`
- Modify: `app/Filament/Admin/Resources/RestaurantOrderItems/Tables/RestaurantOrderItemsTable.php`
- Modify: `app/Filament/Admin/Resources/RestaurantTables/Tables/RestaurantTablesTable.php`
- Modify: `app/Filament/Admin/Resources/Restaurants/Tables/RestaurantsTable.php`
- Modify: `app/Filament/Admin/Resources/RoomTypes/Tables/RoomTypesTable.php`
- Modify: `app/Filament/Admin/Resources/Rooms/Tables/RoomsTable.php`
- Modify: `app/Filament/Admin/Resources/Users/Tables/UsersTable.php`
- Modify: `tests/Feature/FilamentEmptyStatesTest.php`
- Create: `tests/Feature/ActivityLogTableFiltersTest.php`

- [ ] **Step 1: Expand the failing empty-state data provider**

Add all remaining resource table classes to `FilamentEmptyStatesTest`. Require a domain-specific heading, a non-empty recovery-oriented description, and a domain icon. Do not require a create action for audit logs or transaction-derived records that users should not create manually.

- [ ] **Step 2: Write failing Activity Log filter tests**

Assert filters exist for:

- event/description, including created, updated, deleted, checked in, checked out, payment added, login, and logout values;
- acting user;
- subject/model type;
- start and end dates;
- the existing Today shortcut.

Also assert Log ID is toggleable and subject type and IP address can be enabled as secondary columns.

- [ ] **Step 3: Run the empty-state and Activity Log tests**

Run: `php artisan test --compact tests/Feature/FilamentEmptyStatesTest.php tests/Feature/ActivityLogTableFiltersTest.php --do-not-cache-result`

Expected: FAIL for the listed tables and missing log filters.

- [ ] **Step 4: Add specific empty states to each table**

Use this contract:

```php
->emptyStateHeading('No ingredients found')
->emptyStateDescription('Add an ingredient to begin tracking kitchen stock.')
->emptyStateIcon('heroicon-o-beaker')
```

Choose wording that explains whether the user should create master data, clear filters, or wait for operational records. Add a reset-filters action only to tables that expose filters.

- [ ] **Step 5: Add portable Activity Log filters**

Use `SelectFilter` for description. Build acting-user options from `User::query()->orderBy('first_name')`, then constrain both `causer_type` and `causer_id`. Build subject-type options from distinct stored types and display `class_basename()`. Use a custom `Filter` with two `DatePicker` fields and `whereDate()` clauses for the date range.

Expose `subject_type` and `properties.ip_address` as toggleable columns hidden by default; sanitize or escape all displayed state through Filament's normal text rendering.

- [ ] **Step 6: Run activity security and resource regressions**

Run: `php artisan test --compact tests/Feature/FilamentEmptyStatesTest.php tests/Feature/ActivityLogTableFiltersTest.php tests/Feature/ActivityLogXssTest.php tests/Feature/UserActivityLogSecurityTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 7: Commit the operational-state improvements**

```bash
git add app/Filament/Admin/Resources tests/Feature/FilamentEmptyStatesTest.php tests/Feature/ActivityLogTableFiltersTest.php
git commit -m "feat: complete admin empty states and log filters"
```

### Task 11: Reduce super-admin sidebar density with collapsible groups

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `tests/Feature/FilamentNavigationGroupingTest.php`

- [ ] **Step 1: Write failing navigation-group behavior tests**

Assert group labels remain in the current operational order. Assert Dashboards and Accommodation start expanded while Conferences, Restaurant Sales, Kitchen & Inventory, Guests & Communications, Finance, Reports, Access & Administration, and Hotel Configuration are collapsible and initially collapsed.

- [ ] **Step 2: Run the test and confirm string groups expose no collapsed state**

Run: `php artisan test --compact tests/Feature/FilamentNavigationGroupingTest.php --do-not-cache-result`

Expected: FAIL because the panel registers ten plain strings.

- [ ] **Step 3: Replace string groups with explicit Filament NavigationGroup objects**

```php
use Filament\Navigation\NavigationGroup;

->navigationGroups([
    NavigationGroup::make('Dashboards'),
    NavigationGroup::make('Accommodation'),
    NavigationGroup::make('Conferences')->collapsed(),
    NavigationGroup::make('Restaurant Sales')->collapsed(),
    NavigationGroup::make('Kitchen & Inventory')->collapsed(),
    NavigationGroup::make('Guests & Communications')->collapsed(),
    NavigationGroup::make('Finance')->collapsed(),
    NavigationGroup::make('Reports')->collapsed(),
    NavigationGroup::make('Access & Administration')->collapsed(),
    NavigationGroup::make('Hotel Configuration')->collapsed(),
])
```

Do not change resource group labels or role visibility. Confirm Filament opens the active group when navigation lands on one of its pages; if it does not, use a closure on `collapsed()` that returns false for the active group.

- [ ] **Step 4: Run navigation, route, and authorization regressions**

Run: `php artisan test --compact tests/Feature/FilamentNavigationGroupingTest.php tests/Feature/LegacyDashboardNavigationTest.php tests/Feature/FilamentResourceAuthorizationTest.php --do-not-cache-result`

Expected: PASS.

- [ ] **Step 5: Commit the sidebar change**

```bash
git add app/Providers/Filament/AdminPanelProvider.php tests/Feature/FilamentNavigationGroupingTest.php
git commit -m "feat: collapse secondary admin navigation groups"
```

### Task 12: Cross-cutting verification and UI acceptance

**Files:**
- Modify only if verification finds a regression in files already listed above.

- [ ] **Step 1: Run the focused Filament suite**

```bash
php artisan test --compact \
  tests/Feature/FilamentStaffProfileTest.php \
  tests/Feature/UserDepartmentRoleMappingTest.php \
  tests/Feature/AdminBookingAvailabilityTest.php \
  tests/Feature/LegacyAdminPageAccessTest.php \
  tests/Feature/AdminPanelThemeTest.php \
  tests/Feature/BookingCalendarTest.php \
  tests/Feature/ReportPeriodControlsTest.php \
  tests/Feature/CorporateReceivablesTest.php \
  tests/Feature/FilamentResponsiveTablesTest.php \
  tests/Feature/FilamentOperationalTableFiltersTest.php \
  tests/Feature/FilamentEmptyStatesTest.php \
  tests/Feature/FilamentCustomTableAccessibilityTest.php \
  tests/Feature/ActivityLogTableFiltersTest.php \
  tests/Feature/FilamentNavigationGroupingTest.php \
  --do-not-cache-result
```

Expected: PASS.

- [ ] **Step 2: Run static formatting and asset verification**

Run: `vendor/bin/pint --test`

Run: `npm run build`

Expected: PASS with no PHP formatting errors and both public/admin CSS manifest entries.

- [ ] **Step 3: Run the entire application test suite**

Run: `php artisan test --compact --do-not-cache-result`

Expected: PASS with no new warnings, failures, or errors.

- [ ] **Step 4: Perform authenticated browser acceptance checks**

Check at desktop width (1440×900) and mobile width (390×844):

- the top-right user menu contains “My profile” and it opens `/admin/profile`;
- personal information saves and role/department/status cannot be edited;
- the admin booking form excludes maintenance/conflicting rooms and rejects invalid dates;
- `/admin/dashboard` and `/admin/live-staff-dashboard` redirect safely;
- custom admin styles are present in light and dark mode;
- the booking calendar toolbar does not overlap and the day view is readable on mobile;
- report and receivables controls look and behave consistently;
- Conference Rooms remains usable without horizontal collisions;
- custom tables expose meaningful captions to accessibility inspection;
- empty states explain the next action;
- Activity Logs can be filtered by event, user, subject, and date;
- secondary sidebar groups are collapsed but accessible.

- [ ] **Step 5: Review the final diff for scope and placeholders**

Run: `git diff --check`

Run: `rg -n "TO[D]O|TB[D]|FIXM[E]|implement[ ]later|similar[ ]to" app/Filament resources/views/filament resources/css/filament tests/Feature tests/js`

Expected: no whitespace errors and no newly introduced placeholders.

- [ ] **Step 6: Resolve verification failures in their owning task**

If verification finds a regression, return to the task that introduced it, add a failing regression test there, correct that task's listed files, rerun its targeted command, and amend that task's commit before rerunning this verification sequence. Do not create an unscoped cleanup commit.

---

## Completion Criteria

- Staff users can open and securely update their personal profile from the top-right Filament user menu.
- Department choices and automatically assigned roles are consistent for housekeeping and kitchen staff.
- Admin-created hotel bookings cannot use maintenance/conflicting rooms or invalid dates.
- Obsolete dashboard and live-staff URLs no longer render blank or broken pages.
- The dedicated Filament 5 theme is compiled, registered, and does not regress public pages.
- The booking calendar is usable at phone, tablet, and desktop widths.
- Report and receivables controls share Filament styling and explicit applied-filter behavior.
- Conference Rooms has responsive columns, currency formatting, useful filters, and a specific empty state.
- Every maintained custom admin table has a caption and scoped headers.
- Remaining resource tables have meaningful empty states and Activity Logs have complete operational filters.
- The super-admin sidebar preserves all destinations while reducing initial visual density.
- Focused tests, the full PHPUnit suite, Pint, and the Vite build all pass.
