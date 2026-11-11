# Buttons

## Visual ground truth
`trainer-dashboard-desktop.png`, `my-training-desktop.png`, and `trainer-attendance-desktop.png`.

## Canonical source
Trainer dashboard, My Training, Attendance, and `resources/views/admin/partials/system-styles.blade.php`.

## Dependencies
None for CSS; source icons use Bootstrap Icons 1.11.3.

## Variants
Primary dashboard action; outline/secondary action; compact workspace action; icon-only; destructive only in workflows whose source defines it.

## Exact visual contract
Primary dashboard: blue background, white text, radius 11px, padding 11px 17px, 13px/850, `0 6px 16px rgba(37,99,235,.18)` shadow. Outline action: `#bfdbfe` border, white, blue, radius 9px, padding 7px 13px, 12.5px/850. Attendance compact: `#cbd5e1` border, radius 8px, padding 7px 13px, 12px/800. Shared Admin actions use at least 40px height; disabled source opacity is `.5` there and context-specific elsewhere.

## States
Hover changes source colors; focus-visible uses the approved blue ring; disabled blocks action and dims; pending prevents duplicate submission.

## Responsive behavior
Allow labels/actions to wrap or stack. Icon-only controls preserve their source square target.

## Accessibility
Use native buttons, accessible names for icons, correct disabled semantics, and visible focus.

## Preview
Open `preview.html`.

## Implementation
Select the density variant in `buttons.html`/`buttons.css`.

## Do not
Do not add translate/scale hover effects to variants whose source only changes color.
