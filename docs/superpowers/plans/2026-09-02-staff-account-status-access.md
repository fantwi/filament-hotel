# Staff Account Status and Access Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace selectable online/offline staff states with an active account state, display live presence separately, and enforce on-leave and suspended access throughout the Filament administration surface.

**Architecture:** A backed enum owns persisted account states, while `last_seen_at` continues to determine live presence. A centralized access service plus ordinary and persistent middleware gates Filament, Livewire, and direct `/admin` requests; the staff profile supplies the final server-side suspension guard.

**Tech Stack:** PHP 8.4, Laravel 12, Filament 5, Livewire 4, Spatie Laravel Permission, Pest/PHPUnit-compatible Laravel feature tests, Tailwind CSS 4, Vite 7.

**Spec:** `docs/superpowers/specs/2026-09-02-staff-account-status-access-design.md`

## Global Constraints

- Persist only `active`, `on_leave`, and `suspended`; online/offline are presence signals, not selectable account states.
- Map existing `online` and `offline` records to `active`, preserve `on_leave` and `suspended`, and map unknown/null/empty staff statuses to `suspended`.
- The `users.status` database default must be `active` after migration.
- Active presence uses the existing five-minute `last_seen_at` threshold.
- On-leave staff may access only their assigned read-only role dashboard, its date/period controls, their editable profile, and sign-out.
- Suspended staff may access only a view-only profile and sign-out.
- Use this exact suspension copy: `Your account has been suspended. Contact an administrator to have your privileges restored.`
- Do not remove or rewrite role assignments when account status changes.
- Guest/public authorization behavior remains unchanged.
- Protect normal Filament routes, persistent Livewire requests, and direct controller routes beneath `/admin`.
- Do not run migrations against the developer's primary MariaDB database during implementation or acceptance; use the test database and an isolated acceptance database.
- Every behavior change follows RED-GREEN-REFACTOR and every task receives its own focused commit and independent review.

---

### Task 1: Separate persisted account status from live presence

**Files:**
- Create: `app/Enums/StaffAccountStatus.php`
- Create: `database/migrations/2026_09_02_000100_normalize_staff_account_statuses.php`
- Create: `tests/Feature/StaffAccountStatusDomainTest.php`
- Create: `tests/Feature/StaffAccountStatusMigrationTest.php`
- Modify: `app/Models/User.php:205-410`
- Modify: `database/factories/UserFactory.php:23-39`
- Modify: `database/seeders/DatabaseSeeder.php:20-38`
- Modify: `database/seeders/DemoOperationalDataSeeder.php:65-84`
- Modify: `database/seeders/RestoreAdminSeeder.php:18-36`
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php:47-72`
- Modify: `tests/Feature/FilamentStaffProfileTest.php:35-95`

**Interfaces:**
- Produces: `App\Enums\StaffAccountStatus` with `Active`, `OnLeave`, and `Suspended` cases plus `label(): string` and `color(): string`.
- Produces: `User::isOnline(): bool`, `User::hasActiveStaffAccount(): bool`, `User::isOnLeave(): bool`, and `User::isSuspended(): bool`.
- Produces: enum-cast `User::$status`; consumers compare it to enum cases, never legacy strings.

- [ ] **Step 1: Write failing domain tests**

Create `tests/Feature/StaffAccountStatusDomainTest.php` with focused tests that define the desired enum, default, presence, and panel-entry contract:

```php
<?php

namespace Tests\Feature;

use App\Enums\StaffAccountStatus;
use App\Models\User;
use FilamentFacades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StaffAccountStatusDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_users_default_to_active_account_status(): void
    {
        $user = User::factory()->create();

        self::assertSame(StaffAccountStatus::Active, $user->status);
        self::assertTrue($user->hasActiveStaffAccount());
    }

    public function test_active_status_is_independent_of_online_presence(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        $online = User::factory()->create([
            'status' => StaffAccountStatus::Active,
            'last_seen_at' => now()->subMinutes(4),
        ]);
        $offline = User::factory()->create([
            'status' => StaffAccountStatus::Active,
            'last_seen_at' => now()->subMinutes(6),
        ]);

        self::assertTrue($online->isOnline());
        self::assertFalse($offline->isOnline());
        self::assertSame(StaffAccountStatus::Active, $online->status);
        self::assertSame(StaffAccountStatus::Active, $offline->status);
    }

    public function test_restricted_staff_can_enter_the_panel_for_status_routing(): void
    {
        $panel = Filament::getPanel('admin');

        foreach ([StaffAccountStatus::OnLeave, StaffAccountStatus::Suspended] as $status) {
            $staff = User::factory()->create(['department' => 'admin', 'status' => $status]);
            $staff->syncRoles(['admin']);

            self::assertTrue($staff->canAccessPanel($panel));
        }
    }
}
```

- [ ] **Step 2: Run the domain tests and verify RED**

Run:

```bash
php artisan test --compact tests/Feature/StaffAccountStatusDomainTest.php --do-not-cache-result
```

Expected: FAIL because `StaffAccountStatus`, the active-account helpers, the `last_seen_at` cast, and the new factory default do not exist.

- [ ] **Step 3: Implement the enum and model contract**

Create `app/Enums/StaffAccountStatus.php`:

```php
<?php

namespace App\Enums;

enum StaffAccountStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::OnLeave => 'On Leave',
            self::Suspended => 'Suspended',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::OnLeave => 'warning',
            self::Suspended => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
```

Update `User::casts()` and replace the legacy constants with enum-based helpers:

```php
use App\Enums\StaffAccountStatus;

protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'status' => StaffAccountStatus::class,
        'password' => 'hashed',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at' => 'datetime',
    ];
}

public function hasActiveStaffAccount(): bool
{
    return $this->status === StaffAccountStatus::Active;
}

public function isOnLeave(): bool
{
    return $this->status === StaffAccountStatus::OnLeave;
}

public function isSuspended(): bool
{
    return $this->status === StaffAccountStatus::Suspended;
}

public function canAccessPanel(Panel $panel): bool
{
    return $this->hasAnyRole([
        'super_admin', 'admin', 'manager', 'receptionist', 'accountant',
        'housekeeping', 'kitchen_staff', 'kitchen_manager',
    ]);
}
```

Keep `isOnline()` based only on `last_seen_at`. Replace every `User::STATUS_ONLINE` and `User::STATUS_OFFLINE` assignment in the listed factories, seeders, controller, and tests with `StaffAccountStatus::Active`.

- [ ] **Step 4: Run the domain and existing profile tests and verify GREEN**

Run:

```bash
php artisan test --compact \
  tests/Feature/StaffAccountStatusDomainTest.php \
  tests/Feature/FilamentStaffProfileTest.php \
  --do-not-cache-result
```

Expected: PASS with enum-cast status values and independent presence behavior.

- [ ] **Step 5: Write the failing migration test**

Create `tests/Feature/StaffAccountStatusMigrationTest.php`. Run the new migration's `down()`, insert legacy rows through `DB::table('users')`, run `up()`, and assert the normalization and default:

```php
public function test_migration_normalizes_legacy_statuses_and_sets_active_default(): void
{
    $migration = require database_path('migrations/2026_09_02_000100_normalize_staff_account_statuses.php');
    $migration->down();

    foreach (['online', 'offline', 'on_leave', 'suspended', 'unexpected'] as $index => $status) {
        DB::table('users')->insert([
            'name' => "Legacy {$index}",
            'first_name' => 'Legacy',
            'last_name' => (string) $index,
            'email' => "legacy-{$index}@example.test",
            'password' => Hash::make('password'),
            'department' => 'admin',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $migration->up();

    self::assertSame(
        ['active', 'active', 'on_leave', 'suspended', 'suspended'],
        DB::table('users')->orderBy('email')->pluck('status')->sort()->values()->all(),
    );

    $id = DB::table('users')->insertGetId([
        'name' => 'Default Status',
        'first_name' => 'Default',
        'last_name' => 'Status',
        'email' => 'default-status@example.test',
        'password' => Hash::make('password'),
        'department' => 'admin',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    self::assertSame('active', DB::table('users')->find($id)->status);
}
```

Order the assertion by a deterministic inserted-id map in the final test; do not depend on alphabetical sorting of expected states.

- [ ] **Step 6: Run the migration test and verify RED**

Run:

```bash
php artisan test --compact tests/Feature/StaffAccountStatusMigrationTest.php --do-not-cache-result
```

Expected: FAIL because the migration file does not exist.

- [ ] **Step 7: Implement the reversible normalization migration**

Create `database/migrations/2026_09_02_000100_normalize_staff_account_statuses.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereIn('status', ['online', 'offline'])->update(['status' => 'active']);
        DB::table('users')->whereNull('status')->orWhere('status', '')->update(['status' => 'suspended']);
        DB::table('users')->whereNotIn('status', ['active', 'on_leave', 'suspended'])->update(['status' => 'suspended']);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('status')->default('active')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('status', 'active')->update(['status' => 'offline']);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('status')->default('online')->change();
        });
    }
};
```

Use grouped null/empty conditions if the generated SQL shows precedence ambiguity. Verify both SQLite execution and MySQL-compatible query generation without connecting to the primary application database.

- [ ] **Step 8: Run Task 1 verification**

Run:

```bash
php artisan test --compact \
  tests/Feature/StaffAccountStatusDomainTest.php \
  tests/Feature/StaffAccountStatusMigrationTest.php \
  tests/Feature/FilamentStaffProfileTest.php \
  --do-not-cache-result
vendor/bin/pint --test app/Enums/StaffAccountStatus.php app/Models/User.php database/migrations/2026_09_02_000100_normalize_staff_account_statuses.php tests/Feature/StaffAccountStatusDomainTest.php tests/Feature/StaffAccountStatusMigrationTest.php
git diff --check
```

Expected: all commands PASS.

- [ ] **Step 9: Commit Task 1**

```bash
git add app/Enums/StaffAccountStatus.php app/Models/User.php app/Http/Controllers/Auth/RegisteredUserController.php database/factories/UserFactory.php database/seeders database/migrations/2026_09_02_000100_normalize_staff_account_statuses.php tests/Feature/StaffAccountStatusDomainTest.php tests/Feature/StaffAccountStatusMigrationTest.php tests/Feature/FilamentStaffProfileTest.php
git commit -m "refactor: separate staff status from presence"
```

---

### Task 2: Present account status and live presence in Filament

**Files:**
- Create: `resources/views/filament/admin/components/staff-status.blade.php`
- Create: `tests/Feature/UserStatusAdministrationTest.php`
- Modify: `app/Filament/Admin/Resources/Users/Schemas/UserForm.php:45-61`
- Modify: `app/Filament/Admin/Resources/Users/Tables/UsersTable.php:101-157`
- Modify: `app/Filament/Admin/Pages/Auth/EditProfile.php:50-80`

**Interfaces:**
- Consumes: `StaffAccountStatus::options()`, `StaffAccountStatus::label()`, and `User::isOnline()` from Task 1.
- Produces: reusable `filament.admin.components.staff-status` view accepting a `User $user` variable; Filament table rendering supplies `$record`, which the view assigns to `$user`.
- Produces: User form and filter options containing exactly `active`, `on_leave`, and `suspended`.

- [ ] **Step 1: Write failing administration and presentation tests**

Create `tests/Feature/UserStatusAdministrationTest.php` with assertions for exact form/filter options and rendered presence:

```php
public function test_staff_status_form_exposes_only_account_states(): void
{
    $admin = $this->admin();

    $component = Livewire::actingAs($admin)->test(CreateUser::class);
    $status = $component->instance()->form->getComponent('status');

    self::assertSame([
        'active' => 'Active',
        'on_leave' => 'On Leave',
        'suspended' => 'Suspended',
    ], $status->getOptions());
    self::assertSame('active', $status->getDefaultState());
}

public function test_active_staff_status_renders_online_and_offline_presence_accessibly(): void
{
    Carbon::setTestNow('2026-09-02 12:00:00');

    $online = User::factory()->create([
        'status' => StaffAccountStatus::Active,
        'last_seen_at' => now()->subMinutes(4),
    ]);
    $offline = User::factory()->create([
        'status' => StaffAccountStatus::Active,
        'last_seen_at' => now()->subMinutes(6),
    ]);

    $onlineHtml = view('filament.admin.components.staff-status', ['user' => $online])->render();
    $offlineHtml = view('filament.admin.components.staff-status', ['user' => $offline])->render();

    self::assertStringContainsString('Active', $onlineHtml);
    self::assertStringContainsString('Online', $onlineHtml);
    self::assertStringContainsString('bg-success-500', $onlineHtml);
    self::assertStringContainsString('animate-ping', $onlineHtml);
    self::assertStringContainsString('Active', $offlineHtml);
    self::assertStringContainsString('Offline', $offlineHtml);
    self::assertStringContainsString('bg-danger-500', $offlineHtml);
    self::assertStringContainsString('animate-ping', $offlineHtml);
}
```

Also assert that `On Leave` and `Suspended` render their labels without online/offline presence text.

- [ ] **Step 2: Run the new tests and verify RED**

Run:

```bash
php artisan test --compact tests/Feature/UserStatusAdministrationTest.php --do-not-cache-result
```

Expected: FAIL because the form still contains online/offline and the status view does not exist.

- [ ] **Step 3: Replace form and filter options**

In `UserForm`, use the enum options and default:

```php
Select::make('status')
    ->label('Staff Status')
    ->options(StaffAccountStatus::options())
    ->default(StaffAccountStatus::Active->value)
    ->required();
```

In `UsersTable`, make the status filter consume the same options:

```php
SelectFilter::make('status')
    ->options(StaffAccountStatus::options());
```

- [ ] **Step 4: Implement the reusable accessible status view**

Create `resources/views/filament/admin/components/staff-status.blade.php`:

```blade
@php
    use App\Enums\StaffAccountStatus;

    $user = $user ?? $record;
    $status = $user->status;
    $online = $status === StaffAccountStatus::Active && $user->isOnline();
@endphp

<span class="inline-flex items-center gap-2 text-sm font-medium">
    @if ($status === StaffAccountStatus::Active)
        <span class="relative flex h-2.5 w-2.5" aria-hidden="true">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ $online ? 'bg-success-400' : 'bg-danger-400' }} opacity-75"></span>
            <span class="relative inline-flex h-2.5 w-2.5 rounded-full {{ $online ? 'bg-success-500' : 'bg-danger-500' }}"></span>
        </span>
        <span>Active</span>
        <span class="sr-only">{{ $online ? 'Online' : 'Offline' }}</span>
    @else
        <x-filament::badge :color="$status->color()">{{ $status->label() }}</x-filament::badge>
    @endif
</span>
```

Replace the existing status `TextColumn` with a `ViewColumn` that passes the record:

```php
ViewColumn::make('status')
    ->label('Status')
    ->view('filament.admin.components.staff-status')
    ->sortable()
    ->toggleable();
```

- [ ] **Step 5: Reuse the status view on the editable profile**

Update the profile status placeholder:

```php
Placeholder::make('status_display')
    ->label('Status')
    ->content(fn () => view(
        'filament.admin.components.staff-status',
        ['user' => $this->getUser()],
    ));
```

- [ ] **Step 6: Run Task 2 verification**

Run:

```bash
php artisan test --compact \
  tests/Feature/UserStatusAdministrationTest.php \
  tests/Feature/FilamentStaffProfileTest.php \
  tests/Feature/UserDepartmentRoleMappingTest.php \
  --do-not-cache-result
vendor/bin/pint --test app/Filament/Admin/Resources/Users app/Filament/Admin/Pages/Auth/EditProfile.php tests/Feature/UserStatusAdministrationTest.php
git diff --check
```

Expected: PASS; no rendered/form/filter occurrence offers online or offline as an account state.

- [ ] **Step 7: Commit Task 2**

```bash
git add app/Filament/Admin/Resources/Users app/Filament/Admin/Pages/Auth/EditProfile.php resources/views/filament/admin/components/staff-status.blade.php tests/Feature/UserStatusAdministrationTest.php
git commit -m "feat: show staff presence separately from status"
```

---

### Task 3: Enforce active, on-leave, and suspended panel access

**Files:**
- Create: `app/Services/StaffAccountAccess.php`
- Create: `app/Http/Middleware/EnforceStaffAccountStatus.php`
- Create: `resources/views/filament/admin/staff-account-notice.blade.php`
- Create: `tests/Feature/StaffAccountAccessTest.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php:40-125`
- Modify: `bootstrap/app.php:1-25`
- Modify: `app/Filament/Admin/Pages/Dashboards/RoleDashboard.php:15-45`
- Modify: `app/Filament/Admin/Resources/SecureResource.php:10-35`

**Interfaces:**
- Consumes: enum-cast statuses and User helpers from Task 1.
- Produces: `StaffAccountAccess::allows(User $user, ?string $routeName): bool`, `destination(User $user): string`, `dashboardRouteName(User $user): ?string`, and exact notice constants.
- Produces: `EnforceStaffAccountStatus::handle(Request $request, Closure $next): Response` for browser, direct-admin, and persistent Livewire enforcement.

- [ ] **Step 1: Write failing access-service tests**

In `tests/Feature/StaffAccountAccessTest.php`, first define the pure route matrix:

```php
public function test_on_leave_staff_are_limited_to_their_dashboard_profile_and_logout(): void
{
    $manager = $this->staff('manager', StaffAccountStatus::OnLeave);
    $access = app(StaffAccountAccess::class);

    self::assertTrue($access->allows($manager, 'filament.admin.pages.role-dashboard'));
    self::assertTrue($access->allows($manager, 'filament.admin.pages.manager-dashboard'));
    self::assertTrue($access->allows($manager, 'filament.admin.auth.profile'));
    self::assertTrue($access->allows($manager, 'filament.admin.auth.logout'));
    self::assertFalse($access->allows($manager, 'filament.admin.pages.admin-dashboard'));
    self::assertFalse($access->allows($manager, 'filament.admin.resources.bookings.index'));
}

public function test_suspended_staff_are_limited_to_profile_and_logout(): void
{
    $admin = $this->staff('admin', StaffAccountStatus::Suspended);
    $access = app(StaffAccountAccess::class);

    self::assertTrue($access->allows($admin, 'filament.admin.auth.profile'));
    self::assertTrue($access->allows($admin, 'filament.admin.auth.logout'));
    self::assertFalse($access->allows($admin, 'filament.admin.pages.role-dashboard'));
    self::assertFalse($access->allows($admin, 'filament.admin.pages.admin-dashboard'));
    self::assertFalse($access->allows($admin, 'filament.admin.resources.users.index'));
}
```

Add an active-user test proving existing role authorization is left to the downstream page/resource checks.

- [ ] **Step 2: Run the service tests and verify RED**

Run:

```bash
php artisan test --compact tests/Feature/StaffAccountAccessTest.php --filter='route|limited' --do-not-cache-result
```

Expected: FAIL because `StaffAccountAccess` does not exist.

- [ ] **Step 3: Implement the centralized access service**

Create `app/Services/StaffAccountAccess.php`:

```php
<?php

namespace App\Services;

use App\Enums\StaffAccountStatus;
use App\Models\User;

final class StaffAccountAccess
{
    public const LEAVE_MESSAGE = 'Your account is on leave. Operational access is paused until your privileges are restored.';

    public const SUSPENSION_MESSAGE = 'Your account has been suspended. Contact an administrator to have your privileges restored.';

    private const DASHBOARD_ROUTES = [
        'kitchen_manager' => 'filament.admin.pages.kitchen-manager-dashboard',
        'kitchen_staff' => 'filament.admin.pages.kitchen-staff-dashboard',
        'super_admin' => 'filament.admin.pages.super-admin-dashboard',
        'admin' => 'filament.admin.pages.admin-dashboard',
        'accountant' => 'filament.admin.pages.accountant-dashboard',
        'manager' => 'filament.admin.pages.manager-dashboard',
        'receptionist' => 'filament.admin.pages.reception-dashboard',
    ];

    public function allows(User $user, ?string $routeName): bool
    {
        if (! $user->isStaff() || $user->status === StaffAccountStatus::Active) {
            return true;
        }

        if (in_array($routeName, ['filament.admin.auth.profile', 'filament.admin.auth.logout'], true)) {
            return true;
        }

        if ($user->status === StaffAccountStatus::Suspended) {
            return false;
        }

        return in_array($routeName, [
            'filament.admin.pages.role-dashboard',
            $this->dashboardRouteName($user),
        ], true);
    }

    public function dashboardRouteName(User $user): ?string
    {
        foreach (self::DASHBOARD_ROUTES as $role => $routeName) {
            if ($user->hasRole($role)) {
                return $routeName;
            }
        }

        return null;
    }

    public function destination(User $user): string
    {
        if ($user->status === StaffAccountStatus::Suspended) {
            return route('filament.admin.auth.profile');
        }

        return route($this->dashboardRouteName($user) ?? 'filament.admin.auth.profile');
    }

    public function notice(User $user): ?string
    {
        return match ($user->status) {
            StaffAccountStatus::OnLeave => self::LEAVE_MESSAGE,
            StaffAccountStatus::Suspended => self::SUSPENSION_MESSAGE,
            StaffAccountStatus::Active => null,
        };
    }
}
```

- [ ] **Step 4: Run the service tests and verify GREEN**

Run the Step 2 command again. Expected: PASS.

- [ ] **Step 5: Write failing HTTP and resource-gate tests**

Add tests for initial routes, direct admin endpoints, role-dashboard ownership, and unsafe requests:

```php
public function test_on_leave_staff_are_redirected_from_operational_pages_to_their_dashboard(): void
{
    $manager = $this->staff('manager', StaffAccountStatus::OnLeave);

    $this->actingAs($manager)
        ->get('/admin/bookings')
        ->assertRedirect(route('filament.admin.pages.manager-dashboard'))
        ->assertSessionHas('staff_account_notice', StaffAccountAccess::LEAVE_MESSAGE);
}

public function test_suspended_staff_are_redirected_to_profile(): void
{
    $admin = $this->staff('admin', StaffAccountStatus::Suspended);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertRedirect(route('filament.admin.auth.profile'))
        ->assertSessionHas('staff_account_notice', StaffAccountAccess::SUSPENSION_MESSAGE);
}

public function test_restricted_staff_cannot_forge_direct_admin_mutations(): void
{
    $manager = $this->staff('manager', StaffAccountStatus::OnLeave);
    $booking = Booking::factory()->create();

    $this->actingAs($manager)
        ->post(route('admin.bookings.reschedule', $booking), [
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-12',
        ])
        ->assertForbidden();

    self::assertNotSame('2026-09-10', $booking->fresh()->check_in?->toDateString());
}
```

Also assert `SecureResource::canAccess()` is false for on-leave and suspended users and that an on-leave manager cannot access the admin dashboard.

- [ ] **Step 6: Run HTTP tests and verify RED**

Run:

```bash
php artisan test --compact tests/Feature/StaffAccountAccessTest.php --do-not-cache-result
```

Expected: FAIL because restricted staff still reach operational routes or are rejected before the profile-only session can be established.

- [ ] **Step 7: Implement and register status middleware**

Create `app/Http/Middleware/EnforceStaffAccountStatus.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\StaffAccountAccess;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnforceStaffAccountStatus
{
    public function __construct(private readonly StaffAccountAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isStaff() || ! $this->isAdminRequest($request)) {
            return $next($request);
        }

        if ($this->access->allows($user, $request->route()?->getName())) {
            return $next($request);
        }

        if (! $request->isMethodSafe() || $request->expectsJson()) {
            abort(403, $this->access->notice($user));
        }

        return redirect($this->access->destination($user))
            ->with('staff_account_notice', $this->access->notice($user));
    }

    private function isAdminRequest(Request $request): bool
    {
        return $request->is('admin', 'admin/*')
            || filament()->getCurrentPanel()?->getId() === 'admin';
    }
}
```

Append it to the web stack in `bootstrap/app.php` after `UpdateLastSeen` so direct `/admin` routes are protected:

```php
$middleware->web(append: [
    UpdateLastSeen::class,
    EnforceStaffAccountStatus::class,
]);
```

In `AdminPanelProvider`, add it after `Authenticate` and register it as persistent middleware without changing authentication persistence:

```php
->authMiddleware([
    Authenticate::class,
    EnforceStaffAccountStatus::class,
])
->persistentMiddleware([
    EnforceStaffAccountStatus::class,
]);
```

- [ ] **Step 8: Add defense-in-depth resource gating**

Override `SecureResource::canAccess()` before existing capability methods:

```php
public static function canAccess(): bool
{
    $user = auth()->user();

    if ($user?->isStaff() && ! $user->hasActiveStaffAccount()) {
        return false;
    }

    return parent::canAccess();
}
```

This retains each resource's existing `canViewAny()` decision for active users while preventing restricted Livewire resource hydration.

- [ ] **Step 9: Centralize the role-dashboard mapping and hide restricted navigation**

Update `RoleDashboard::mount()` to consume `StaffAccountAccess::dashboardRouteName()` and redirect by route name, preserving the existing role priority. In `AdminPanelProvider`, add:

```php
->navigation(function (): bool {
    $user = auth()->user();

    return ! $user?->isStaff() || $user->hasActiveStaffAccount();
})
```

Add the status notice render hook:

```php
->renderHook(
    PanelsRenderHook::PAGE_START,
    fn () => view('filament.admin.staff-account-notice'),
)
```

Create `resources/views/filament/admin/staff-account-notice.blade.php`:

```blade
@php
    $user = auth()->user();
    $notice = $user instanceof \App\Models\User
        ? app(\App\Services\StaffAccountAccess::class)->notice($user)
        : null;
@endphp

@if ($notice)
    <div
        class="mb-6 rounded-xl border px-4 py-3 text-sm font-medium {{ $user->isSuspended() ? 'border-danger-300 bg-danger-50 text-danger-800 dark:border-danger-500/40 dark:bg-danger-500/10 dark:text-danger-200' : 'border-warning-300 bg-warning-50 text-warning-800 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-200' }}"
        role="alert"
    >
        {{ $notice }}
    </div>
@endif
```

- [ ] **Step 10: Run Task 3 tests and verify GREEN**

Run:

```bash
php artisan test --compact \
  tests/Feature/StaffAccountAccessTest.php \
  tests/Feature/LegacyAdminPageAccessTest.php \
  tests/Feature/FilamentNavigationGroupingTest.php \
  tests/Feature/FilamentResourceAuthorizationTest.php \
  tests/Feature/UserResourceAuthorizationTest.php \
  --do-not-cache-result
vendor/bin/pint --test app/Services/StaffAccountAccess.php app/Http/Middleware/EnforceStaffAccountStatus.php app/Providers/Filament/AdminPanelProvider.php app/Filament/Admin/Pages/Dashboards/RoleDashboard.php app/Filament/Admin/Resources/SecureResource.php tests/Feature/StaffAccountAccessTest.php
git diff --check
```

Expected: all commands PASS.

- [ ] **Step 11: Commit Task 3**

```bash
git add app/Services/StaffAccountAccess.php app/Http/Middleware/EnforceStaffAccountStatus.php app/Providers/Filament/AdminPanelProvider.php app/Filament/Admin/Pages/Dashboards/RoleDashboard.php app/Filament/Admin/Resources/SecureResource.php bootstrap/app.php resources/views/filament/admin/staff-account-notice.blade.php tests/Feature/StaffAccountAccessTest.php
git commit -m "feat: enforce staff account status access"
```

---

### Task 4: Make suspended staff profiles view-only

**Files:**
- Modify: `app/Filament/Admin/Pages/Auth/EditProfile.php:20-115`
- Modify: `tests/Feature/FilamentStaffProfileTest.php`

**Interfaces:**
- Consumes: `User::isSuspended()` and `StaffAccountAccess::SUSPENSION_MESSAGE`.
- Produces: suspended profile schema with placeholders only, `getFormActions(): array` returning no actions, `save(): void` returning HTTP 403 when forged, and no MFA-management component.

- [ ] **Step 1: Write failing suspended-profile tests**

Add these behaviors to `FilamentStaffProfileTest`:

```php
public function test_suspended_staff_profile_is_view_only_and_shows_the_exact_notice(): void
{
    $admin = User::factory()->create([
        'department' => 'admin',
        'status' => StaffAccountStatus::Suspended,
    ]);
    $admin->syncRoles(['admin']);

    $this->actingAs($admin)
        ->get('/admin/profile')
        ->assertOk()
        ->assertSee(StaffAccountAccess::SUSPENSION_MESSAGE)
        ->assertDontSee('New password');

    $this->profileComponent($admin)
        ->assertFormFieldDoesNotExist('first_name')
        ->assertFormFieldDoesNotExist('last_name')
        ->assertFormFieldDoesNotExist('email')
        ->assertFormFieldDoesNotExist('phone_number')
        ->assertFormFieldDoesNotExist('password')
        ->assertSchemaComponentExists('first_name_display')
        ->assertSchemaComponentExists('email_display')
        ->assertDontSee('Save changes');
}

public function test_suspended_staff_cannot_forge_a_profile_save(): void
{
    $admin = User::factory()->create([
        'first_name' => 'Suspended',
        'department' => 'admin',
        'status' => StaffAccountStatus::Suspended,
    ]);
    $admin->syncRoles(['admin']);

    $this->profileComponent($admin)
        ->set('data.first_name', 'Changed')
        ->call('save')
        ->assertForbidden();

    self::assertSame('Suspended', $admin->fresh()->first_name);
}
```

Add a regression proving on-leave staff retain editable personal/profile password fields.

- [ ] **Step 2: Run the profile tests and verify RED**

Run:

```bash
php artisan test --compact tests/Feature/FilamentStaffProfileTest.php --do-not-cache-result
```

Expected: FAIL because suspended users still receive the editable form and `save()` is unguarded.

- [ ] **Step 3: Add the read-only suspended schema**

At the start of `EditProfile::form()`, branch on suspension and use placeholders rather than dehydrated inputs:

```php
if ($this->getUser()->isSuspended()) {
    return $schema->components([
        Section::make('Personal information')->schema([
            Placeholder::make('first_name_display')->label('First name')->content(fn (): string => (string) $this->getUser()->first_name),
            Placeholder::make('last_name_display')->label('Last name')->content(fn (): string => (string) $this->getUser()->last_name),
            Placeholder::make('email_display')->label('Email')->content(fn (): string => (string) $this->getUser()->email),
            Placeholder::make('phone_display')->label('Phone number')->content(fn (): string => (string) ($this->getUser()->phone_number ?: 'Not set')),
        ])->columns(['default' => 1, 'md' => 2]),
        $this->staffAccountSection(),
    ]);
}
```

Extract the existing staff metadata section to `private function staffAccountSection(): Section` so active, on-leave, and suspended schemas share one status presentation.

- [ ] **Step 4: Remove suspended actions and guard save/MFA server-side**

Add these overrides:

```php
/** @return array<\Filament\Actions\Action | \Filament\Actions\ActionGroup> */
protected function getFormActions(): array
{
    return $this->getUser()->isSuspended() ? [] : parent::getFormActions();
}

public function save(): void
{
    abort_if(
        $this->getUser()->isSuspended(),
        403,
        StaffAccountAccess::SUSPENSION_MESSAGE,
    );

    parent::save();
}

public function getMultiFactorAuthenticationContentComponent(): ?Component
{
    if ($this->getUser()->isSuspended()) {
        return null;
    }

    return parent::getMultiFactorAuthenticationContentComponent();
}
```

Import `Filament\Schemas\Components\Component` and `App\Services\StaffAccountAccess`. Do not make status, department, or role editable in any profile state.

- [ ] **Step 5: Run Task 4 verification**

Run:

```bash
php artisan test --compact \
  tests/Feature/FilamentStaffProfileTest.php \
  tests/Feature/StaffAccountAccessTest.php \
  tests/Feature/UserStatusAdministrationTest.php \
  --do-not-cache-result
vendor/bin/pint --test app/Filament/Admin/Pages/Auth/EditProfile.php tests/Feature/FilamentStaffProfileTest.php
git diff --check
```

Expected: PASS; the suspended record remains unchanged after the forged save attempt.

- [ ] **Step 6: Commit Task 4**

```bash
git add app/Filament/Admin/Pages/Auth/EditProfile.php tests/Feature/FilamentStaffProfileTest.php
git commit -m "fix: make suspended staff profiles view only"
```

---

### Task 5: Cross-cutting regression and authenticated acceptance

**Files:**
- Modify only when a failing acceptance check demonstrates a regression in files listed by Tasks 1-4.
- Test: all focused tests from Tasks 1-4 plus the existing Filament/profile/navigation/authorization suites.

**Interfaces:**
- Consumes: the complete status, presence, middleware, navigation, and profile behavior from Tasks 1-4.
- Produces: verified migration, server authorization, UI/accessibility, and browser acceptance evidence.

- [ ] **Step 1: Run the focused status and access suite**

```bash
php artisan test --compact \
  tests/Feature/StaffAccountStatusDomainTest.php \
  tests/Feature/StaffAccountStatusMigrationTest.php \
  tests/Feature/UserStatusAdministrationTest.php \
  tests/Feature/StaffAccountAccessTest.php \
  tests/Feature/FilamentStaffProfileTest.php \
  tests/Feature/UserDepartmentRoleMappingTest.php \
  tests/Feature/FilamentNavigationGroupingTest.php \
  tests/Feature/LegacyAdminPageAccessTest.php \
  --do-not-cache-result
```

Expected: PASS with no warnings or deprecations.

- [ ] **Step 2: Run formatting, JavaScript, and full PHP regression gates**

```bash
vendor/bin/pint --test
npm test
php artisan test --compact --do-not-cache-result
git diff --check
```

Expected: all commands PASS. Record exact test and assertion counts in the task report.

- [ ] **Step 3: Build production assets and verify both CSS entries**

Use the established mapped-drive Node workaround when WSL cannot execute the Windows Node binary from the UNC worktree. Run the real build:

```bash
npm run build
```

Confirm `public/build/manifest.json` contains existing public application entries plus `resources/css/filament/admin/theme.css`. Remove any temporary ignored PostCSS boundary file immediately after the build.

- [ ] **Step 4: Run isolated migration acceptance**

Create an ignored SQLite acceptance database and environment under the plan workspace. Run:

```bash
php artisan migrate:fresh --force --no-interaction
php artisan migrate:status --no-interaction
```

Seed one active-online, active-offline, on-leave, and suspended staff record. Confirm stored statuses are exactly `active`, `active`, `on_leave`, and `suspended`, while presence differs only through `last_seen_at`. Do not connect this acceptance run to MariaDB.

- [ ] **Step 5: Run authenticated desktop and mobile browser acceptance**

Use the Codex in-app browser client against the isolated local server. Test desktop `1440x900` and mobile `390x844`, with one viewport in light mode and the other in dark mode, then invert themes for the status indicators.

Verify visibly:

1. User create/edit status choices are only Active, On Leave, and Suspended.
2. Active-online shows a green pulsing circle and Active; active-offline shows a red pulsing circle and Active.
3. On-leave login reaches only its assigned role dashboard and editable profile; operational URLs redirect to the assigned dashboard with the leave notice.
4. Suspended login lands on `/admin/profile`, shows the exact suspension message, has no sidebar, editable inputs, password/MFA controls, or Save action, and can sign out.
5. Direct prohibited `/admin` GET requests redirect correctly and prohibited POST/Livewire actions return 403 without mutation.
6. Restoring the suspended record to Active from a separate authorized admin session restores its original role-based dashboard and navigation without reassigning roles.
7. No page has horizontal overflow, inaccessible status-only color communication, JavaScript errors, or blocked dialogs.

- [ ] **Step 6: Scan for legacy status references and placeholders**

```bash
rg -n "STATUS_ONLINE|STATUS_OFFLINE|'online'\s*=>\s*'Online'|'offline'\s*=>\s*'Offline'" app database tests resources
rg -n "TO[D]O|TB[D]|FIXM[E]|implement[ ]later|similar[ ]to" app/Enums app/Services app/Http/Middleware app/Filament resources/views/filament tests/Feature
```

Expected: the legacy status scan finds no account-state constant/options references; the placeholder scan returns no findings introduced by this plan.

- [ ] **Step 7: Commit only demonstrated acceptance fixes**

If Step 1-6 exposed a regression and a focused correction was required:

```bash
git add app/Enums/StaffAccountStatus.php app/Models/User.php app/Http/Controllers/Auth/RegisteredUserController.php app/Http/Middleware/EnforceStaffAccountStatus.php app/Services/StaffAccountAccess.php app/Providers/Filament/AdminPanelProvider.php app/Filament/Admin/Pages/Auth/EditProfile.php app/Filament/Admin/Pages/Dashboards/RoleDashboard.php app/Filament/Admin/Resources/SecureResource.php app/Filament/Admin/Resources/Users bootstrap/app.php database/factories/UserFactory.php database/seeders database/migrations/2026_09_02_000100_normalize_staff_account_statuses.php resources/views/filament/admin/components/staff-status.blade.php resources/views/filament/admin/staff-account-notice.blade.php tests/Feature/StaffAccountStatusDomainTest.php tests/Feature/StaffAccountStatusMigrationTest.php tests/Feature/UserStatusAdministrationTest.php tests/Feature/StaffAccountAccessTest.php tests/Feature/FilamentStaffProfileTest.php
git commit -m "fix: resolve staff status acceptance regressions"
```

If every gate passed without source changes, create no empty commit.

- [ ] **Step 8: Perform the final whole-branch review**

Generate a review package from the plan's merge base through the final HEAD. A fresh reviewer inspects correctness, security, authorization, migration reversibility, accessibility, responsive behavior, test adequacy, and all prior Filament-remediation changes still present on the branch. Resolve any concrete finding with one bounded fix round and one scoped re-review.

Record every task commit and review verdict in the subagent-driven progress ledger, then use `superpowers:finishing-a-development-branch` to present the integration options without merging or pushing unless the user chooses one.
