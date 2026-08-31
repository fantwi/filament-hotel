# Staff and Admin WebAuthn MFA Design

## Goal

Require phishing-resistant passkey authentication for privileged staff access to the Filament admin panel while preserving a safe transition path for existing staff and an auditable recovery process.

## Scope

This design applies to users with the following roles: `super_admin`, `admin`, `manager`, `accountant`, `receptionist`, `kitchen_manager`, `kitchen_staff`, and `housekeeping`. Guest accounts continue using the existing authenticator-app TOTP flow with recovery and verified-email fallback.

## Security policy

- `super_admin` and `admin` users must register two passkeys before accessing the Filament panel so one device can be used to recover the other.
- `manager`, `accountant`, `receptionist`, `kitchen_manager`, `kitchen_staff`, and `housekeeping` users must register at least one passkey before accessing the Filament panel.
- A passkey is the normal staff/admin MFA factor. It may be a platform passkey (Windows Hello, Touch ID, Face ID, Android passkey) or a roaming FIDO2 security key.
- TOTP is available as a temporary migration fallback for staff who cannot complete a passkey ceremony. It is never enabled automatically and is not an alternative that bypasses the enrollment requirement indefinitely.
- Email and SMS are not accepted as normal staff/admin MFA methods. Email may be used only by the audited recovery workflow.
- Recovery and credential reset events are logged with the acting administrator, target user, reason, and timestamp. Recovery must not reveal passkey private material.
- Sensitive operations (refunds, role changes, payment adjustments, and account deletion) require a recent MFA verification even when the panel session is active.

## Technology

- Use the maintained official `laravel/passkeys` Composer package and its `@laravel/passkeys` JavaScript client.
- Publish the package migration for encrypted WebAuthn credential data and configure the relying-party ID/origins from `APP_URL`.
- Add `PasskeyUser` and `PasskeyAuthenticatable` to `App\\Models\\User`. Override display-name and username methods because this application stores `first_name` and `last_name` instead of relying on a single `name` column.
- Keep the existing `web` guard, session middleware, CSRF protection, and Filament authentication session middleware.

## User flows

### Enrollment

1. A staff member signs in with the existing password flow.
2. The panel MFA gate redirects the user to a staff security setup page before any admin page is rendered.
3. The page starts a WebAuthn registration ceremony in the browser and stores only the resulting public credential through the package action.
4. The user names the device and may register a second authenticator.
5. After the role-required number of credentials is registered, the MFA gate marks the current session verified and redirects to the intended Filament URL.
6. If the browser does not support WebAuthn, the user is offered the temporary TOTP migration flow and told to complete passkey enrollment later.

### Normal login

1. Filament authenticates the password.
2. The panel middleware checks whether the role requires MFA and whether the session has a recent staff-MFA verification timestamp.
3. If verification is missing or stale, the middleware redirects to the staff MFA challenge page and preserves the intended admin URL.
4. The browser completes a user-bound WebAuthn assertion. The server verifies the challenge against that staff user’s registered credentials.
5. The session is regenerated, the MFA timestamp is stored, and the user is redirected to the intended panel URL.

### Recovery

- A staff member can use TOTP only during the migration period or when a passkey is unavailable.
- A lost-device reset requires an authenticated administrator with permission to manage the target role, the target user’s current password, and a recorded reason. The reset disables existing passkeys, invalidates staff MFA sessions, and requires new enrollment at the next login.
- Super-admin recovery requires a second super-admin approval when more than one super-admin exists; otherwise the event is flagged for immediate audit review.

## Components and boundaries

- `StaffMfaMiddleware`: enforces the panel MFA policy and redirects only unauthenticated-to-MFA staff sessions; excludes setup, challenge, logout, and passkey ceremony endpoints to avoid loops.
- `StaffMfaController`: renders setup/challenge pages, starts user-bound passkey options, records verification, and handles migration fallback.
- `StaffMfaPolicy`: centralizes role requirements, enrollment rules, recent-verification timeout, and sensitive-action step-up checks.
- `User`: owns the passkey relationship supplied by the package and exposes role-aware MFA helper methods.
- `StaffSecurity` Filament page/view: provides enrollment, device listing, revocation, and migration status without exposing public guest controls.
- `UserActivityLog`: receives enrollment, successful verification, failed verification, revocation, and recovery events without storing OTPs or WebAuthn assertions.

## Data handling

- WebAuthn private keys never reach the application. The database stores package-managed public credentials and metadata only.
- TOTP secrets remain encrypted using the existing user casts. TOTP recovery codes remain hashed/encrypted as in the guest implementation.
- MFA challenge state is short-lived, session-bound, and invalidated after successful or failed completion according to the package ceremony rules.
- No passkey assertion payload, OTP, recovery code, or email token is written to activity logs.

## Testing requirements

- Unit tests cover role policy decisions, enrollment requirements, verification freshness, and sensitive-action step-up decisions.
- Feature tests cover panel redirection before enrollment, successful passkey verification, intended-URL restoration, failed/expired challenges, credential revocation, and migration fallback.
- Authorization tests prove guests cannot access staff MFA management and staff cannot manage another user’s credentials without the required permission.
- Regression tests cover every staff role, including kitchen manager and kitchen staff, and confirm that guest TOTP/email authentication remains unchanged.
- Run the full Laravel test suite, PHP syntax checks, and Laravel Pint before committing.

## Deployment and operations

- Install Composer and npm dependencies, publish the passkey migration/configuration, run `php artisan migrate`, and build frontend assets.
- Set the production relying-party ID and allowed origin to the canonical HTTPS hostname. WebAuthn must not be enabled on an insecure non-localhost origin.
- Preserve `APP_KEY`; rotating it without the package’s key-rotation procedure can invalidate encrypted credential data.
- Document the administrator-assisted recovery procedure and audit review expectations for staff.
