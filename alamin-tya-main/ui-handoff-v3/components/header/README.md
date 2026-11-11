# Header

## Visual ground truth
All desktop screenshots, especially `trainer-dashboard-desktop.png` and `admin-training-monitoring-desktop.png`.

## Canonical source
`resources/views/components/app-signed-in-header-styles.blade.php`; Admin/Trainer navbar partials and layouts.

## Dependencies
Inter; source uses Bootstrap 5.3.3 collapse and Bootstrap Icons 1.11.3. Portable markup may use equivalent dependency-free behavior.

## Variants
One shared signed-in contract; Admin and Trainer differ only in navigation items and current route.

## Exact visual contract
Sticky 58px white `.96` header, bottom `#e2e8f0` border, `0 1px 10px rgba(15,23,42,.04)` shadow, blur 10px. Inner maximum 1880px; gutter `clamp(16px,2.25vw,42px)`. Logo 48px high, auto width, max-width 52px, contain. Nav items are 58px high, 14px/600, and active with blue/soft-blue plus a 2px bottom indicator. Logout is 36px square.

## States
Hover, active/current route, focus-visible, collapsed navigation, account truncation.

## Responsive behavior
At 1199.98px nav becomes 13px with 9px inline padding. At 991.98px desktop nav collapses; header row remains at least 58px. At 575.98px the logo is 44px and account text hides.

## Accessibility
Useful logo alt text, current link semantics, labelled menu/logout controls, visible focus, and synchronized `aria-expanded` for collapse.

## Preview
Open `preview.html`.

## Implementation
Use `header.html` and `header.css`; adapt link targets and collapse state without changing geometry.

## Do not
Do not use AE/AA initials, duplicate the wordmark, add Edu Admin/Edu Trainer/role subtitles, increase header height, or move the 991.98px breakpoint.
