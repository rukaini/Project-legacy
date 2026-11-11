# Dropdown Actions

## Visual ground truth
`trainer-dashboard-desktop.png` and `admin-training-monitoring-desktop.png` where applicable.

## Canonical source
Admin shared styles, signed-in header/navbar partials, and workflow-local action menus.

## Dependencies
Source may use Bootstrap 5.3.3 dropdown behavior and Bootstrap Icons; both are replaceable.

## Variants
Account/navigation collapse controls and page-specific action menus. No single global menu width is standardized.

## Exact visual contract
Trigger geometry follows its button variant. Menu surface is white with `#e2e8f0` boundary and source-local radius/shadow; use the closest canonical workflow because width and density are not globally tokenized.

## States
Closed/open, hover, focus, selected where applicable, and disabled action.

## Responsive behavior
Keep the menu within viewport and actions reachable; align inward near the right edge.

## Accessibility
Use a labelled trigger with `aria-expanded`; support keyboard opening, navigation, Escape, outside close, and focus return.

## Preview
Open `preview.html`.

## Implementation
`dropdown-actions.html` provides structure; receiving code owns open/close behavior.

## Do not
Do not invent a universal fixed width or hide unavailable actions without preserving workflow clarity.
