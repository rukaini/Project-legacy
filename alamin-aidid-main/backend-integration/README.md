# Canonical shared-database integration

This directory is the database deployment and ownership handoff for the
Trainer Management System. It contains structure and contract evidence only;
it contains no application rows, credentials, or database backup.

## Shared schema authority

Import the latest owner-approved V4 shared schema first, then configure Aidid's
database connection through `.env`. A local handoff copy may be placed at
`incoming/main_database_CANONICAL_PRE_INSERT_V4.sql`; `incoming/` is ignored and
must not be treated as versioned source.

V4 uses canonical `VARCHAR` business identifiers. The former `_combined_id`
approach is obsolete. The final `guru_new` owner contract retains `created_at`
and `updated_at` and omits `service_date` and `remember_token`. Synthetic
fixtures, credentials, and disposable runtimes are local-only test data.

## Authoritative deployment files

- `deployment/trainer-canonical-schema.sql` — tracked structure-only Aidid
  integration reference. It supports isolated validation but does not replace
  the latest owner-approved shared schema.
- `deployment/trainer-owned-schema.sql` — deployable structure-only Trainer
  contribution for the shared production database. It creates only Trainer-owned
  tables and database program objects and applies the approved `course` extension
  after authoritative owners have provisioned its prerequisites.
- `deployment/CANONICAL_SCHEMA_INVENTORY.md` — table ownership, identities,
  keys, dependencies, and database-program inventory.
- `deployment/validate-canonical-schema.php` — guarded validator intended for a
  disposable canonical schema.

Do not run the validator or schema deployment against production or a
populated shared database without the required backup and deployment approval.

## Supporting maintenance documentation

- `APPLICATION_USAGE.md` — runtime table usage and source locations.
- `MIGRATION_GAPS.md` — active production migration organization and deliberate
  ownership boundaries.
- `FYP2_SELECTED_OWNER_CONTRACTS.md` and
  `PSM_ASSESSMENT_SELECTED_OWNER_CONTRACTS.md` — retained owner-selection audit
  evidence; their legacy conversion notes are historical, not deployment
  instructions.
- `share/TRAINER_OWNED_DATA_CONTRACT.md` — current Trainer-owned table and
  database-program contract.
- `history/retired-migrations/` — superseded no-op migration files retained for
  traceability outside Laravel's active migration discovery path.

## Reading order

Read the canonical inventory first, followed by the Trainer-owned data
contract, application usage, and migration gaps. Use the owner-approved V4
schema as the cross-system authority and `trainer-owned-schema.sql` as the
Trainer deployment contribution when prose and historical evidence differ.
