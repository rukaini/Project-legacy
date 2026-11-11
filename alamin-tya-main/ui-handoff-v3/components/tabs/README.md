# Tabs

## Visual ground truth
`trainer-attendance-desktop.png`, `Material page.png`, and `admin-training-monitoring-desktop.png`.

## Canonical source
`resources/views/trainer/training/partials/workspace-header-styles.blade.php`; Admin Training Monitoring content/styles.

## Dependencies
Native links/buttons; no package is required for portable styling.

## Variants
Trainer workspace navigation and Admin detail tabs are legitimate variants.

## Exact visual contract
Workspace tabs use a 24px gap, top margin 22px, local horizontal overflow, muted inactive text, blue active text, and a 2.5px active underline. Admin detail tabs retain their source-local density.

## States
Default, hover/focus, active/current, disabled only when workflow genuinely disallows a tab.

## Responsive behavior
Keep every tab reachable through local horizontal scrolling; do not let the page overflow.

## Accessibility
Use links for navigation or the ARIA tab pattern only when switching an in-page tabpanel; never mix the two semantics.

## Preview
Open `preview.html`.

## Implementation
Use `tabs.html`/`tabs.css` and preserve the receiving system's route/panel behavior.

## Do not
Do not wrap one tab label internally or hide tabs at narrow widths.
