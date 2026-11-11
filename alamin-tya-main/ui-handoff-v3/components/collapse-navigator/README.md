# Collapse Navigator

## Visual ground truth
`trainer-attendance-desktop.png` and `admin-training-monitoring-desktop.png`.

## Canonical source
Trainer Attendance styles/scripts and Admin Training Monitoring styles/scripts.

## Dependencies
Native browser JavaScript for state, focus, inert, and ARIA synchronization.

## Variants
Canonical Trainer: 360px to 44px. Admin: 360px, 320px at 992–1279.98px, then 44px compact.

## Exact visual contract
Width and flex-basis transition `.18s ease`. Expanded content fades and translates `-6px` on collapse; compact content starts at `translateX(6px)`. Visibility waits `.18s` while hiding, pointer events gate immediately, and the compact control occupies 44px. Reduced-motion duration is `.01ms` with no delay.

## States
Expanded/compact; JS synchronizes class, `inert`, `aria-hidden`, `aria-expanded`, and focus.

## Responsive behavior
Admin normalizes at 991.98px; Trainer Attendance at 900px. Mobile shows normal full-width content and hides the compact-only control.

## Accessibility
Use a button with an explicit label; ensure hidden content is inert, not merely transparent; move focus to the available toggle.

## Preview
Open `preview.html` and activate either toggle.

## Implementation
Use all three files. Portable selectors are adapted; visual values come from source.

## Do not
Do not use early `display:none`, timeout hacks, abrupt width changes, or remove reduced motion.
