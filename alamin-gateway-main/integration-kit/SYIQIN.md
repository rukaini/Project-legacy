# Syiqin receiver handoff

- Client ID: `syiqin`
- Public application name: Teacher Employment System
- Authorized active roles: New Teacher, Principal,
  Staff EDU, HR, Observer, External Observer
- Private configuration: `PRIVATE-DEPLOYMENT/syiqin.env`

Implement the generic contract in [`README.md`](README.md) in Syiqin's current
application and independently validate the claimed identity and role before
creating its normal local session. Syiqin maps the canonical Staff EDU role to
its local Admin role internally.
