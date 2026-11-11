# Cards

## Visual ground truth
`trainer-dashboard-desktop.png`, `my-training-desktop.png`, and `trainer-attendance-desktop.png`.

## Canonical source
`resources/views/trainer/dashboard.blade.php`, `resources/views/admin/dashboard.blade.php`, `resources/views/trainer/training/my-training.blade.php`, workspace/Attendance styles.

## Dependencies
None beyond CSS; icons may use Bootstrap Icons in the source.

## Variants
1. Admin dashboard KPI: 14px 15px padding, 4px semantic left edge, 10px label, 24px value, hover lifts 2px with a 28px-spread shadow.
2. Trainer dashboard KPI: 16px 17px padding, 11px label, 27px value, hover lifts 3px.
3. My Training compact stat: same base density as Trainer dashboard, hover lifts 2px.
4. Standard section/content panel: 18px or workflow-specific 16px radius, divided header/body.
5. Workspace summary/content cards: legitimate local density; use the relevant source/screenshot rather than forcing KPI styling.

## Exact visual contract
White surface, `#e2e8f0` border, `0 6px 20px rgba(15,23,42,.05)` base stat shadow. Stat labels are 11px/850 uppercase with `.06em`; values 27px/900/1 in the My Training variant. Section panel header uses 16px 20px and body 20px where that exact pattern applies.

## States
Static section card; interactive stat hover/focus only when the card is actionable.

## Responsive behavior
Cards use `min-width:0`; grids stack according to their page family and text wraps.

## Accessibility
Do not make non-actionable cards clickable. Actionable cards need one clear keyboard target and focus state.

## Preview
Open `preview.html`.

## Implementation
Choose the explicit class variant in `cards.html`/`cards.css`.

## Do not
Do not present one fake universal KPI card or copy a hover transform onto static content cards.
