# Database boundary

The gateway reads canonical identity tables but owns no database tables.
Do not run Laravel migrations or seeders against the shared database. Gateway
sessions and one-time SSO codes use the configured session/cache stores.
