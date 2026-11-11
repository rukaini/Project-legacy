# ELIT Portal receiver contract

This contract is application-neutral. Each target implements it in its own
current repository and keeps its own identity validation, session model,
authorization rules, dashboard routes, and direct-login behavior.

## Receiver environment

```dotenv
ALAMIN_LOGIN_URL=
ALAMIN_LOGIN_CLIENT_ID=<assigned-client-id>
ALAMIN_LOGIN_CLIENT_SECRET=<provided-privately>
ALAMIN_LOGIN_REQUIRE_HTTPS=true
ALAMIN_LOGIN_CONNECT_TIMEOUT=3
ALAMIN_LOGIN_TIMEOUT=8
```

`ALAMIN_LOGIN_URL` is the base URL only. Never include `/systems`,
`/sso/redeem`, or a callback route. The client secret is unique to one target
and must not be committed, logged, sent through the browser, or reused as an
`APP_KEY` or password. The tracked example defaults to HTTPS enforcement;
an approved HTTP deployment must explicitly set
`ALAMIN_LOGIN_REQUIRE_HTTPS=false` on ELIT Portal and the receiver.

## Protocol

1. ELIT Portal redirects the browser to the target's configured callback as
   `GET /sso/callback?code=<opaque-code>`.
2. The receiver validates the code format, then performs a server-to-server
   `POST` to `{ALAMIN_LOGIN_URL}/sso/redeem` with the JSON field `code`.
   Authenticate that request using the assigned `X-Alamin-SSO-Client` and
   `X-Alamin-SSO-Secret` headers.
3. On success, ELIT Portal returns the target-bound identity claim and active
   role. The receiver independently confirms the identity and role against its
   canonical source before creating a local session.
4. Redirect to the receiver's own authorized dashboard. Never accept a browser
   supplied dashboard or return URL.

Codes are short-lived, single-use, and bound to one target. Treat invalid,
expired, replayed, malformed, and wrong-target responses as authentication
failures. Do not put codes, secrets, identity payloads, or credentials in logs.

## Session and logout boundary

ELIT Portal and every receiver keep independent sessions and distinct cookie
names. Direct login may remain available. A receiver may record a server-set
authentication source so that an SSO-origin logout returns to the fixed
`{ALAMIN_LOGIN_URL}/systems` page while direct-login logout returns to its own
login page. Do not implement a browser-controlled return URL or global single
logout.

Aidid is a working reference implementation, but its guards, session keys,
models, role mapping, and dashboard paths are Aidid-specific and must not be
copied as protocol requirements.

## Transport configuration

`ALAMIN_LOGIN_REQUIRE_HTTPS=true` rejects HTTP ELIT Portal URLs and target
callbacks. `ALAMIN_LOGIN_REQUIRE_HTTPS=false` explicitly permits HTTP in
production. The current `cendekia.utem.edu.my` endpoints use HTTP, so their
prepared private merge blocks use `false`. When HTTPS is introduced, update
the URLs and set the value to `true`; no source change is required.

## Verification checklist

- valid code creates the correct local role/session;
- invalid, expired, replayed, and wrong-target codes fail safely;
- canonical identity/role validation occurs locally;
- direct login remains correct where required;
- logout destroys only the receiver session and uses a server-owned target;
- no secret or raw code is logged; and
- the configured transport policy is enforced.
