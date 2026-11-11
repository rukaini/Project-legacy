# ELIT Portal

**EduOasis Learning & Insight Tracking**

ELIT Portal is the central sign-in and application-selection gateway. It
authenticates existing canonical identities, supports independently verified
multi-role accounts, and issues short-lived one-time authorization codes to
configured applications. The gateway owns no canonical database tables and
must not run migrations or seeders against a shared canonical database.

The portal uses the original tracked Al Amin Edu Oasis logo at
`public/images/al-amin-edu-oasis-logo.jpg` across authenticated and public
screens.

## Authentication and SSO flow

1. The submitted password is verified independently against every eligible
   canonical identity source using the normalized email.
2. A single verified role is selected automatically. Multiple verified roles
   require a server-validated role choice.
3. Only enabled applications authorized by `SystemRegistry` for the selected
   role are shown. Systems from different roles are never unioned.
4. Launching an application stores the real selected canonical identity and
   target in server-side cache. Only an opaque, target-bound, one-time code is
   sent through the browser.
5. The receiver redeems that code using its existing client ID and secret. No
   Administrator role or claim has been added to the receiver contract.

## Role access matrix

The configured role keys in `config/sso.php` are authoritative.

| Selected role | Systems |
|---|---|
| Teacher | EduTrain |
| New Teacher | Teacher Employment System, EduTrain |
| Public (`outsider`) | EduTrain |
| Principal | Teacher Employment System, EduTrain |
| Staff EDU | TrainHub, Trainer Management System, Teacher Employment System |
| HR | Teacher Employment System, Human Resources System, EduTrain |
| Trainer | Trainer Management System |
| Observer | Teacher Employment System |
| External Observer | Teacher Employment System |

## Protected Administrator architecture

The dedicated Administrator is one person with legitimate identities in the
existing canonical role structures. It is not a new business role. Each
credential-bearing identity uses the same normalized email and synchronized
login password, while retaining its real role-specific ID and profile fields.
Observer and External Observer remain legitimate linked identities that use
the Teacher credential under the existing canonical contract.

After authentication, the ordinary multi-role selector contains only roles
whose real canonical credentials and eligibility checks succeeded. Selecting
Trainer sends the real Trainer identity; selecting HR sends the real HR
identity, and so on. Normal assignments and business workflows still apply.
The guiding rule is: **Administrator has all legitimate roles, not all
bypasses.**

Configure the protection marker privately:

```dotenv
ELIT_ADMIN_EMAIL=
```

The value is normalized case-insensitively. A blank or invalid value disables
Administrator-specific protection. This setting is not a password and does
not grant or fabricate any role. It only identifies the one successfully
authenticated account that receives gateway-specific protection and a subtle
UI indicator.

## Password recovery

Normal eligible users retain the simple IC/email generated-password recovery
flow. A protected Administrator IC changes no password and asks the user to
enter the registered email. The protected email sends a random, single-use
link through Laravel Mail; only an HMAC digest is cached, the link expires in
15 minutes, and a successful form submission synchronizes every eligible
Administrator credential atomically. Configure a real production mail
transport; `MAIL_MAILER=log` is suitable only for local verification.

The trusted server console remains available as an operator fallback:

```bash
# Hidden prompt and confirmation (preferred for an operator-chosen password)
php artisan elit:administrator-password

# Generate a high-entropy readable password and display it once after success
php artisan elit:administrator-password --generate
```

The command never accepts a password argument. It requires at least two
eligible, unambiguous credential-bearing canonical targets, locks the target
rows, independently hashes the same new password for every target, verifies
every hash, and commits in one transaction. Any failure rolls back all target
updates. It does not change role profiles, assignments, or workflow data.

## Administrator audit

Automatic provisioning is intentionally not implemented because canonical
roles require real business data and relationships that cannot be inferred
safely. The following command is read-only:

```bash
php artisan elit:audit-administrator
```

It reports existing, missing, conflicting, ineligible, and schema-unavailable
roles plus the role-specific data and normal workflow still required. Review
and create approved canonical records through their owning business processes,
then rerun the audit and the secure password synchronization command.

Do not enable or provision a production Administrator until all canonical
identity data has been reviewed and approved by the responsible data owners.

## Local setup and verification

```powershell
Copy-Item .env.example .env
composer install
php artisan key:generate
npm.cmd install
npm.cmd run build
php artisan serve
```

Use an approved local database only. Automated tests use SQLite `:memory:` and
do not migrate or seed the configured canonical database.

```powershell
php artisan test
php vendor/bin/pint --test
git diff --check
```

`ALAMIN_LOGIN_REQUIRE_HTTPS` remains the compatibility transport-policy key.
Existing `ALAMIN_LOGIN_*` settings and `X-Alamin-SSO-*` headers are unchanged.
See [`SERVER-SETUP.md`](SERVER-SETUP.md) for deployment and provisioning
controls and [`integration-kit/README.md`](integration-kit/README.md) for the
unchanged receiver contract.
