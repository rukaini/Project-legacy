# Accessibility

- Keep all actions keyboard reachable and preserve visible `:focus-visible` treatment.
- Native disabled controls use `disabled`; non-native disabled controls require equivalent semantics and must not activate.
- Icon-only controls require a meaningful accessible name.
- Collapse controls expose `aria-expanded`; inactive navigator content uses `inert`, `aria-hidden`, delayed visibility, and no pointer events.
- Modals need an accessible name, a close button, normal Escape behavior, focus entry/return, and a contained scrollable body.
- Toasts use an `aria-live="polite"` region; success has role `status`, errors role `alert`.
- Brand images use meaningful alt text unless adjacent identical text would cause duplicate announcements.
- Do not place focusable content inside visually hidden/collapsed regions.
- Respect `prefers-reduced-motion: reduce` with `.01ms` transitions/animations for the approved motion contract.
- Maintain usable control targets: canonical mobile nav links and compact navigator width use 44px; do not shrink critical touch actions below their source contract.
