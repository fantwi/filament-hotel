# Staff Account Status and Access Design

**Date:** 2026-09-02  
**Status:** Approved for implementation planning

## Purpose

Separate a staff member's administrative account state from their live online presence. Replace the selectable `online` and `offline` staff states with `active`, retain `on_leave` and `suspended`, and enforce each state consistently throughout the Filament administration panel.

This design applies to staff access. Guest and public authentication flows remain unchanged.

## Status model

Create a backed `StaffAccountStatus` enum with three persisted values:

- `active`: the staff member receives the permissions granted by their role.
- `on_leave`: the staff member may view their own role dashboard and edit their own profile, but cannot open or operate any other administrative feature.
- `suspended`: the staff member may view a read-only profile and sign out, but has no other administrative privilege.

The `users.status` column remains a string so the change does not require a platform-specific database enum. The model casts the column to `StaffAccountStatus` and exposes focused helpers for full access, leave access, suspension, and live presence.

Unknown stored staff statuses fail closed. The data migration normalizes every existing value so the enum cast cannot encounter historical invalid data.

## Migration and compatibility

Add a reversible migration that:

1. Converts existing `online` and `offline` values to `active`.
2. Preserves existing `on_leave` and `suspended` values.
3. Converts null, empty, or unknown values to `suspended` so unexpected records do not receive privileges.
4. Changes the column default from `online` to `active`.

The rollback changes `active` records to `offline` and restores the previous default. It cannot reconstruct whether an active account was online or offline before migration; online presence is now intentionally derived from `last_seen_at`.

Factories, seeders, registration code, tests, and user-creation defaults will use `active`. Legacy `STATUS_ONLINE` and `STATUS_OFFLINE` constants will be removed rather than retained as aliases, preventing new code from treating presence as authorization state.

## Presence display

`User::isOnline()` remains based on `last_seen_at` being within five minutes. `UpdateLastSeen` continues refreshing authenticated-user presence at its existing throttled interval.

For an active user, every maintained staff-status display uses:

- a green pulsing circle followed by the word **Active** when `isOnline()` is true;
- a red pulsing circle followed by the word **Active** when `isOnline()` is false.

The visual indicator includes assistive text that identifies the user as online or offline without changing the account-state label. `On Leave` and `Suspended` use distinct, non-presence badges because their account state takes priority over activity recency.

The status select and status filter expose only `Active`, `On Leave`, and `Suspended`. Online and offline are never selectable account states.

## Access-control architecture

Use a centralized staff-access policy/service plus middleware instead of duplicating checks in every resource and page. The middleware is registered for ordinary admin requests and as persistent Filament middleware so Livewire hydration and actions receive the same enforcement.

Direct controller routes beneath `/admin`, including booking/calendar actions that do not originate from a Filament resource route, receive the same status enforcement. Existing role and resource authorization remains in force after the status gate passes.

### Access matrix

| Capability | Active | On leave | Suspended |
| --- | --- | --- | --- |
| Sign in and sign out | Yes | Yes | Yes |
| Own role dashboard | Yes | Read-only | No |
| Dashboard date/period filters | Yes | Yes | No |
| Own profile | Editable | Editable | View-only |
| Filament resources and custom reports | Role-authorized | No | No |
| Operational Livewire actions | Role-authorized | No | No |
| Direct `/admin` controller actions | Role-authorized | No | No |
| Administrative navigation | Yes | Hidden | Hidden |

An on-leave request for a prohibited destination redirects to the staff member's own role dashboard and displays a leave notice. A suspended request for any destination other than the profile or sign-out redirects to the profile and displays the suspension notice.

The exact suspended-account message is:

> Your account has been suspended. Contact an administrator to have your privileges restored.

Only another active user who already has authority to edit that account may restore its status. Restricted users cannot use their own profile to change their status.

## Filament profile behavior

Active and on-leave staff retain the existing editable personal-information and password sections. Department, role, and account status remain read-only metadata.

For suspended staff, the profile page:

- shows the suspension notice prominently;
- renders personal information, department, role, and status as read-only values;
- omits password inputs and the Save action;
- rejects a forged Livewire `save` call server-side;
- keeps sign-out available.

The server-side guard is required even though the controls are absent from the rendered page.

## Navigation and redirection

Filament navigation is dynamically disabled for on-leave and suspended staff. The top bar and user menu remain available so on-leave users can reach their profile and restricted users can sign out.

The panel root continues resolving the correct role dashboard. Middleware validates that an on-leave user may access only the dashboard assigned to their current role; manually requesting another role's dashboard remains forbidden.

Suspended staff remain eligible to authenticate into the panel because `canAccessPanel()` must allow the profile-only session. The status middleware, page guards, hidden navigation, and profile save guard provide the actual post-authentication restriction.

## Error handling and audit behavior

- Prohibited browser navigation redirects to the permitted destination with a clear warning instead of returning a generic authorization error.
- Forged state-changing requests return HTTP 403 and do not mutate data.
- Unknown staff status values fail closed as suspended.
- Existing activity logging continues to record authorized account changes. No role assignments are removed when a status changes, so restoration immediately returns the user's prior role-based privileges.

## Testing strategy

Implementation follows test-driven development. New tests must fail for the missing behavior before production code is changed.

Automated coverage includes:

1. Migration mapping and the `active` database default.
2. Enum/model helpers and the five-minute online threshold.
3. User form options/defaults and status filtering.
4. Active online/offline presentation, including accessible indicator text.
5. On-leave access to only the assigned dashboard and editable profile.
6. Suspended login redirection, exact notice, hidden navigation, view-only profile, and rejected forged save.
7. Protection of Filament pages, Livewire actions, and direct `/admin` controller endpoints.
8. Restoration of normal role-based access after an authorized administrator changes the status back to active.
9. Regression coverage for existing role, profile, navigation, and user-management behavior.

Final verification runs focused tests, the full PHP suite, Pint, JavaScript tests when affected, the production asset build, migration checks on SQLite and the application's MariaDB-compatible SQL path, and authenticated desktop/mobile browser acceptance in light and dark themes.

## Delivery

The implementation will be committed with the focused statement:

`feat: enforce staff account status access`

The existing Filament-administration branch will receive a new whole-branch review after this feature is complete so the earlier remediation work and this access-control change are assessed together.
