# Asynchronous UI outcomes

- Prevent duplicate submissions while an action is pending.
- Preserve useful focus and return it to the initiating control after transient overlays close.
- Reset pagination to page 1 when search/filter criteria change.
- When asynchronous filtering is used, discard stale responses that no longer match current criteria.
- Use truthful action-level success/error feedback. Never show success before the server confirms it.
- Keep field validation adjacent to the relevant input.
- Avoid unnecessary full-page refreshes where a safe local update already exists.

These are outcomes, not a required framework or networking implementation.
