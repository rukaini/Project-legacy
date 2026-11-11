# Aidid reference

- Client ID: `aidid`
- Public application name: Trainer Management System
- Authorized active roles: Staff EDU, Trainer
- Callback path: `/sso/callback`
- Source branch: `login-update`

Aidid independently maps Staff EDU to its Admin dashboard and Trainer to its
Trainer dashboard. Its SSO-origin logout returns to ELIT Portal while direct
logout returns to Aidid login. Other receivers should follow the protocol in
[`README.md`](README.md), not copy Aidid-specific session keys or routes.
