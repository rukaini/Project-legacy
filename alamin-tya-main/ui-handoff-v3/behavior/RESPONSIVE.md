# Responsive behavior

## Desktop and wide views

Keep the centered 1880px shell and aligned header. Grids may use available shell space, but prose/forms retain component-specific readable constraints. Do not stretch cards into detached, unreadable regions.

## Tablet

Review at 768px, 820px, and 991/1024px. At 991.98px the header collapses and master/detail navigators normalize to their mobile/full-width form. Controls wrap; tables/tabs use local scrolling where necessary.

## Mobile

Review at 320px, 360px, 375/390px, 412px, and 480px. The shell uses 16px horizontal padding through 575.99–767.98px and 12px at 575.98px and below. Stack multi-column cards/forms, wrap headings and action groups, retain reachable touch controls, keep dropdowns/toasts/modals inside the viewport, and keep QR/media responsive.

## Structural rules

- Flexible/grid children use `min-width: 0`.
- Long titles and user content wrap; ellipsis is reserved for contexts that preserve access to the full value.
- Wide tables and wide tab strips scroll inside their own wrapper.
- Do not introduce accidental page-level horizontal overflow.
- Modal bodies scroll independently; action rows wrap or stack.
- Images/media use responsive containment; charts resize with their container.
- Loading and empty states must not impose desktop-only fixed widths or heights.
