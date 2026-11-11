# Pagination

## Visual ground truth
`my-training-desktop.png`.

## Canonical source
`resources/views/trainer/training/my-training.blade.php`.

## Dependencies
Native browser JavaScript only when paging client-side.

## Variants
The documented fixed-five variant is My Training product behavior, not a universal page size for every domain.

## Exact visual contract
Summary: `Showing 1–5 of 5 trainings · Page 1 of 1`. Page size is 5. Summary remains visible with any non-zero result count. Previous/Next and page controls hide when only one page. No Rows/page-size selector. Zero results hide the pager and never render `Page 1 of 0`.

## States
First/last page disables unavailable direction; filter/search change resets page to 1; current page is clear.

## Responsive behavior
Summary and controls align on desktop and wrap/stack compactly without overflow on mobile.

## Accessibility
Use navigation labelling, real buttons/links, disabled semantics, and a clear current-page announcement.

## Preview
Open `preview.html`.

## Implementation
Use `pagination.html`/`pagination.css`; compute displayed range from the filtered result set.

## Do not
Do not add Rows, show controls on one page, or use total unfiltered count.
