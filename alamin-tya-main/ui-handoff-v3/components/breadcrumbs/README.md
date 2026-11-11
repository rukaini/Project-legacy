# Breadcrumbs

## Visual ground truth
`trainer-attendance-desktop.png` and `Material page.png`.

## Canonical source
`resources/views/trainer/training/partials/workspace-header-styles.blade.php` and `workspace-header.blade.php`.

## Dependencies
None; icons are optional separators in source context.

## Variants
Workspace breadcrumb only; no separate global breadcrumb style was found.

## Exact visual contract
Wrapping flex row with 8px gap and 15px bottom margin. Text is 11px/850, uppercase, `.08em`; inactive text `#94a3b8`, links `#64748b`, current `#334155`, hover `#2563eb`.

## States
Link, hover/focus, current item.

## Responsive behavior
Wrap instead of overflow; descendants use `min-width:0` and safe word breaking.

## Accessibility
Use `nav aria-label="Breadcrumb"`, an ordered list, and `aria-current="page"`.

## Preview
Open `preview.html`.

## Implementation
Use `breadcrumbs.html` and `breadcrumbs.css`.

## Do not
Do not force nowrap or use breadcrumbs as primary workspace tabs.
