# Badges and Status

## Visual ground truth
`my-training-desktop.png`, `trainer-dashboard-desktop.png`, and `trainer-attendance-desktop.png`.

## Canonical source
My Training, dashboard, workspace header, and Attendance styles.

## Dependencies
None beyond CSS.

## Variants
Semantic pill badges for neutral/info, success, warning, and danger; workspace pills use 5px 11px, radius 999px, 12px/750. Exact compact table badges may use a denser local variant.

## Exact visual contract
Use semantic token colors and source-defined soft backgrounds/borders. Pills never rely on color alone; keep labels concise and truthful.

## States
Static status; do not style badges as controls unless they actually activate filtering/action.

## Responsive behavior
Allow badge groups to wrap; individual short status labels remain intact.

## Accessibility
Visible text communicates meaning; add hidden context only when the label is ambiguous.

## Preview
Open `preview.html`.

## Implementation
Use semantic classes from `badges-status.css`.

## Do not
Do not expose raw keys such as `outsider` or `guru_new`; use Public and New Teacher.
