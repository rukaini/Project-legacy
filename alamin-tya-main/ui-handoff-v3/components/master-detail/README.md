# Master Detail

## Visual ground truth
`admin-training-monitoring-desktop.png` and `trainer-attendance-desktop.png`.

## Canonical source
Admin Training Monitoring styles/content/scripts and Trainer Attendance styles/results/scripts.

## Dependencies
CSS flex/grid plus native JS when collapsible behavior is enabled.

## Variants
Trainer Attendance uses 360px expanded master; Admin Training Monitoring uses 360px, becomes 320px between 992px and 1279.98px, and both compact to 44px. Detail is flexible with `min-width:0`.

## Exact visual contract
Horizontal flex layout with 16px source gap, fixed-basis master, flexible detail, panel borders/radii from the local workflow, and local overflow for list/detail tables.

## States
Expanded, compact, selected list item, empty selection, loading/error detail.

## Responsive behavior
At 991.98px and below Admin stacks/full-width and suppresses compact desktop control; Attendance normalizes at its source 900px breakpoint. Keep each legitimate workflow breakpoint rather than forcing one.

## Accessibility
Selected item semantics, keyboard-operable controls, `aria-expanded`, `inert` collapsed content, and no hidden focus targets.

## Preview
Open `preview.html`.

## Implementation
Use `master-detail.html`/`.css`; pair with collapse-navigator when compact behavior is needed.

## Do not
Do not use fixed detail width, clip detail actions, or unify distinct mobile breakpoints without product review.
