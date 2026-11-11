# Interaction contract

- **Buttons:** preserve source hover/focus/disabled states; do not add movement or shadows to variants that do not have them. While an action is pending, disable duplicate submission and communicate state truthfully.
- **Search/filters:** keep focus usable, update the matching result set, and reset pagination to page 1 when criteria change. Avoid a full-page refresh when a local update is safe.
- **Pagination:** My Training's approved variant always shows a truthful result/page summary when results exist, hides controls for one page, has no page-size selector, and never says `Page 1 of 0`.
- **Tabs:** retain active semantics; keep every tab reachable by wrapping or local scrolling according to the component variant.
- **Modal:** supply a labelled close control, normal Escape behavior, focus management, viewport containment, body overflow, and wrapped/stacked actions on narrow screens. Width is context-specific.
- **Toast:** use the shared `showAppToast(message, type)` behavior, `status`/`alert` roles, polite live region, 3500ms display, and 320ms removal delay after the leaving class.
- **Collapse navigator:** `.18s ease`, width/flex-basis transition, opacity and 6px translation, delayed visibility, pointer-event gating, `inert`, synchronized `aria-hidden`/`aria-expanded`, and `.01ms` reduced motion.
- **Empty/loading/error:** render truthful, non-demo state. Loading blocks duplicate action; field validation stays near its field; action-level success/error uses the shared toast where suitable.

Do not implement QR fullscreen or a direct external QR new-tab action. The approved QR action uses the internal authorized display.
