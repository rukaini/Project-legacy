# Modal

## Visual ground truth
`trainer-attendance-desktop.png` (modal behavior belongs to the same workspace family).

## Canonical source
Attendance styles/scripts and submission `styles.blade.php`/`file-preview-modal.blade.php`; QR styles for a smaller context.

## Dependencies
Native browser JS or equivalent framework focus/Escape behavior. Bootstrap modal behavior is replaceable.

## Variants
Attendance calendar/report modal: max-width 1280px, 24px overlay gutter, 16px radius. Submission preview: max-width 1180px, viewport height `calc(100vh - 40px)` / `min(94vh, calc(100dvh - 40px))`, 18px radius. QR/material dialogs are context-specific. There is no global 720px modal.

## Exact visual contract
Attendance overlay is `rgba(15,23,42,.6)` with blur 2px; panel shadow `0 25px 50px rgba(0,0,0,.25)`, header 14px 20px, body 20px. Submission overlay is darker and panel shadow `0 24px 56px rgba(15,23,42,.32)`. Size must follow content variant.

## States
Closed/open, entering, loading/error body, and wrapped actions.

## Responsive behavior
Viewport-constrained height, internally scrollable body, safe 10–12px mobile gutters, and stacked/wrapped actions at the source breakpoint.

## Accessibility
Labelled dialog, close control, Escape, trapped focus, focus return, background inertness.

## Preview
Open `preview.html`.

## Implementation
Choose the explicit variant in `modal.html`/`modal.css`.

## Do not
Do not invent a global width, remove Escape, add QR fullscreen, or allow body content outside the viewport.
