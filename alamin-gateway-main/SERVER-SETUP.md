# ELIT Portal server setup

**EduOasis Learning & Insight Tracking** is the user-facing gateway name.
Existing `ALAMIN_LOGIN_*` environment keys, SSO headers, cache/session keys,
and receiver routes remain compatibility identifiers and are unchanged.

This document separates the senior/server administrator's deployment work
from the receiver work owned by the other system teams. The current deployment
uses explicitly permitted HTTP transport and requires no HTTPS or reverse
proxy work for this feature.

## A. Senior / Server Administrator

### Files provided privately

The workstation handoff includes these files outside Git and the public ZIP:

- `PRIVATE-DEPLOYMENT/alamin-login.env`
- `PRIVATE-DEPLOYMENT/aidid.env`
- `PRIVATE-DEPLOYMENT/nureen.env`
- `PRIVATE-DEPLOYMENT/syiqin.env`
- `PRIVATE-DEPLOYMENT/izz.env`
- `PRIVATE-DEPLOYMENT/tya.env`

The five unique SSO client secrets and the new ELIT Portal `APP_KEY` are
already prepared. The senior does not generate, choose, calculate, or match
these values.

### Current HTTP transport policy

One environment setting controls the transport policy on ELIT Portal and
every receiver:

```dotenv
ALAMIN_LOGIN_REQUIRE_HTTPS=false
```

With `false`, HTTP ELIT Portal communication and HTTP target callbacks are
explicitly permitted, including in production. There is no additional
production-only HTTPS rejection.

The current target URLs are:

```dotenv
SYSTEM_IZZ_URL=http://cendekia.utem.edu.my:8081
SYSTEM_NUREEN_URL=http://cendekia.utem.edu.my:8082
SYSTEM_SYIQIN_URL=http://cendekia.utem.edu.my:8083
SYSTEM_TYA_URL=http://cendekia.utem.edu.my:8084
SYSTEM_AIDID_URL=http://cendekia.utem.edu.my:8085
```

If HTTPS is introduced later, change all public URLs to their verified
`https://` values and set `ALAMIN_LOGIN_REQUIRE_HTTPS=true` on ELIT Portal
and each receiver. No PHP, controller, route, or Blade changes are required.

### Values still requiring completion

The ELIT Portal URL/port has not been assigned. After it is assigned, enter
the same base URL, with no trailing path, in:

```dotenv
# ELIT Portal server
APP_URL=

# Existing Aidid server
ALAMIN_LOGIN_URL=
```

Do not append `/systems`, `/sso/redeem`, or `/sso/callback`.

The prepared `alamin-login.env` already contains the known database host,
port, database name, and username. Supply `DB_PASSWORD` only if it is still
blank. Do not replace Aidid's existing `APP_KEY`, database, mail, session, or
other unrelated environment values.

### Protected Administrator configuration

The protected Administrator is one person with legitimate identities in the
existing canonical role structures. There is no new downstream Administrator
role or claim. Credential-bearing records use the same normalized email and
synchronized password while keeping their real role-specific IDs, profiles,
statuses, and relationships. Observer and External Observer use the linked
Teacher credential under the existing contract.

Configure this private marker only after the canonical data is approved:

```dotenv
APP_NAME="ELIT Portal"
ELIT_ADMIN_EMAIL=
ELIT_ADMIN_RESET_TTL=900
ELIT_ADMIN_RESET_CACHE_STORE=file

# Configure an approved production mail transport. Do not use log in production.
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="ELIT Portal"
```

`ELIT_ADMIN_EMAIL` is not a password and does not grant or fabricate roles.
It only identifies the successfully authenticated account that receives
gateway protection. The normal multi-role selector contains only identities
that pass real canonical credential and eligibility checks. The selected role
still controls the system matrix and the real identity sent to the receiver.
All normal assignments and workflows continue to apply.

Do not enable or provision a production Administrator until the canonical
identity data has been reviewed and approved by the responsible data owners.

### Read-only Administrator audit

Automatic creation is intentionally unavailable because required canonical
data differs by role and cannot be inferred safely. Run:

```bash
php artisan elit:audit-administrator
```

The command reports ready, missing, ineligible, conflicting, and
schema-unavailable roles without changing data. Manual review may include:

- Teacher identity and approved school assignment;
- New Teacher identity, IC, status, placement, and onboarding fields;
- Public `users`, `outsider`, `roles`, and `model_has_roles` relationships;
- Principal identity, IC, status, and school leadership relationships;
- Staff EDU identity, IC, and active status;
- HR identity and accepted canonical HR role value;
- Trainer identity, IC, active status, and later course/training assignments;
- active Observer and External Observer rows linked to the real Teacher ID.

A role may be login-ready while still lacking workflow data until it receives
normal assignments.

### Secure Administrator password synchronization

Public Forgot Password is unavailable to the protected account by either
email or role-specific IC and returns the ordinary unavailable response. Use a
trusted server console instead:

```bash
# Hidden prompt and confirmation
php artisan elit:administrator-password

# Generated high-entropy password, shown once after success
php artisan elit:administrator-password --generate
```

No password command-line argument exists. The command requires at least two
eligible, unambiguous credential-bearing canonical targets, but does not
require every possible role. It locks rows, hashes the same password
independently for each credential column (including `trainerPassword`),
verifies each hash, and commits in one transaction. Any failure rolls back all
updates. It does not change assignments or workflow data.

### Deploy ELIT Portal

1. Extract `ALAMIN-LOGIN.zip`; its root folder is `ALAMIN-LOGIN`.
2. Create the server `.env` from `.env.example`, then apply all values from the
   private `alamin-login.env` file.
3. Fill `APP_URL` and, if blank, `DB_PASSWORD`.
4. Point the web root to `ALAMIN-LOGIN/public`.
5. Give the PHP service account write access to `storage` and
   `bootstrap/cache`.
6. Run:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Do not run `php artisan key:generate`; the production `APP_KEY` is already in
the private ELIT Portal environment file. The application owns no canonical
tables, so do not run migrations or seeders against the shared database.

For multiple ELIT Portal hosts, use a shared lock-capable cache such as
Redis for both `CACHE_STORE` and `SSO_CACHE_STORE`, plus a shared session
driver.

### Deploy Aidid

1. Pull/deploy branch `login-update` from
   `https://github.com/snizam2022/alamin-aidid.git` using the server's normal
   release process. A GitHub push does not update the live `:8085` folder by
   itself.
2. Merge the four `ALAMIN_LOGIN_*` lines from private `aidid.env` into Aidid's
   existing server `.env`. Do not replace the whole file.
3. Fill `ALAMIN_LOGIN_URL` with the same base URL used for ELIT Portal's
   `APP_URL`.
4. Install/build through Aidid's normal release process, then run:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

5. Restart or reload the relevant web/PHP services.

The senior does not edit source code, generate SSO secrets, decide the role
matrix, or implement Aidid SSO.

### Smoke test

1. Confirm ELIT Portal `/up` and `/login` load at the assigned HTTP URL.
2. Confirm a single-role account proceeds without an unnecessary selector.
3. Confirm a legitimate multi-role account can change role without another
   password and sees only that role's systems.
4. Confirm Staff EDU can enter Nureen, Aidid Admin, and Syiqin's local Admin,
   while Trainer enters Aidid Trainer.
5. Confirm SSO-origin Aidid logout returns to the still-active ELIT Portal
   systems page after confirmation.
6. Confirm ELIT Portal logout returns to its login page.
7. Confirm direct Aidid logout returns to Aidid login.
8. After approved Administrator provisioning, confirm only genuinely
   authenticated roles appear, a fabricated role POST is rejected, and each
   selected role exposes only its normal systems.
9. Confirm protected email and role-specific IC recovery attempts make no
   password change, while normal user recovery remains unchanged.

There is no global single logout and no browser-controlled logout destination.

## B. Other System Owners

Nureen, Syiqin, Izz, and TYA owners later implement the contract in
[`integration-kit/README.md`](integration-kit/README.md) inside their own
current applications and use the matching private merge block.

Each receiver must:

1. accept the opaque code only at its fixed callback;
2. redeem it server-to-server using its own client ID and private secret;
3. reject invalid, expired, replayed, malformed, or wrong-target codes;
4. independently validate the returned identity and active role;
5. create its own normal local session and choose its own authorized
   dashboard;
6. preserve direct login where required; and
7. keep logout destinations server-controlled.

Do not copy Aidid-specific guards, models, session keys, role mapping, or
dashboard paths.

## Role matrix

| Active role | Internal system keys |
|---|---|
| Teacher | Izz |
| New Teacher | Syiqin, Izz |
| Public | Izz |
| Principal | Syiqin, Izz |
| Staff EDU | Nureen, Aidid, Syiqin |
| HR | Syiqin, TYA, Izz |
| Trainer | Aidid |
| Observer | Syiqin |
| External Observer | Syiqin |

Role is selected first; systems are never unioned across roles. Staff EDU
does not have Izz. HR does have Izz.

## Password recovery

ELIT Portal keeps the simple generated-password recovery flow for eligible
normal users. A protected Administrator IC directs the user to enter the
registered email; the protected email sends a single-use reset link that
expires after 15 minutes. Configure Laravel's production mail transport and a
lock-capable cache. `MAIL_MAILER=log` is local testing only. The server-side
synchronization command remains the operator fallback documented above.
