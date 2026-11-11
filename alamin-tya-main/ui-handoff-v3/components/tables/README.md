# Tables

## Visual ground truth
`trainer-dashboard-desktop.png`, `trainer-attendance-desktop.png`, and `Directory page.png`.

## Canonical source
Trainer dashboard, Attendance styles/results, Admin shared styles.

## Dependencies
None beyond CSS.

## Variants
Dashboard table: 11px/850 uppercase headings with `.05em`, 11px 18px heading cells and 14px 18px body cells. Dense Attendance report: 10px/900 headings with `.09em`, 11px 14px headings, 10px 14px body, 12.5px table text and nowrap cells. Other report tables retain local density.

## Exact visual contract
White surface, subtle dividers, `#f8fafc` head, muted uppercase labels, local horizontal scroll wrapper, and source-specific row hover. Numeric/action alignment follows content.

## States
Default rows, hover where source uses it, empty/loading/error, and action cells.

## Responsive behavior
The wrapper—not the page—scrolls horizontally. Preserve table semantics and useful minimum content widths; wrap long prose where appropriate rather than forcing every cell nowrap.

## Accessibility
Use `table`, scoped headers, caption or accessible name, and real controls in action cells.

## Preview
Open `preview.html`.

## Implementation
Choose an explicit table density in `tables.html`/`tables.css`.

## Do not
Do not invent one global cell padding or convert all tables to cards.
