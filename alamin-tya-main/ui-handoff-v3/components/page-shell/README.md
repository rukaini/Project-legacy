# Page Shell

## Visual ground truth
All screenshots.

## Canonical source
`resources/views/components/app-page-shell-styles.blade.php`.

## Dependencies
None beyond CSS.

## Variants
One signed-in shell contract; a flush variant removes padding only where source explicitly uses it.

## Exact visual contract
Centered `max-width:1880px`; desktop padding is `clamp(22px,2.2vw,36px)` top, `clamp(16px,2.4vw,44px)` inline, and `clamp(44px,4vw,68px)` bottom. Flexible descendants are shrink-safe and media is contained.

## States
Normal and explicit flush shell.

## Responsive behavior
At 767.98px: `20px 16px 44px`. At 575.98px: 12px inline. Wide/zoomed-out view remains centered at 1880px.

## Accessibility
Use a semantic `main`; headings/content must remain in document flow.

## Preview
Open `preview.html`.

## Implementation
Apply `.app-page-shell` to the signed-in main region.

## Do not
Do not remove the maximum width, detect zoom, or allow one child to create whole-page horizontal overflow.
