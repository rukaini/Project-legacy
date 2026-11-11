# Retired migration history

These files are superseded no-op migrations retained only for migration-name
traceability and contract audit tests. They do not own external tables, perform
legacy data conversion, or run during normal Laravel migration discovery.

Their approved end state is already represented by
`../../deployment/trainer-canonical-schema.sql` and the active canonical
migrations under `database/migrations/`.
