# ELIT Portal deployment

Use [`SERVER-SETUP.md`](SERVER-SETUP.md) as the authoritative production
checklist. It separates the server administrator's configuration-only work
from the receiver work owned by Nureen, Syiqin, Izz, and TYA teams.

Deployment values belong in ignored environment files or the approved secret
manager. Never add actual client secrets, database passwords, or `APP_KEY`
values to this source tree.

The current private server configuration explicitly permits HTTP with
`ALAMIN_LOGIN_REQUIRE_HTTPS=false`. The tracked `.env.example` remains secure
by default with `true`.
