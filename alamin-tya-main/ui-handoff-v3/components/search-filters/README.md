# Search and Filters

## Visual ground truth
`my-training-desktop.png` and `admin-training-monitoring-desktop.png`.

## Canonical source
`resources/views/trainer/training/my-training.blade.php`; Admin Training Monitoring content/styles/scripts.

## Dependencies
Native browser JavaScript for local filtering/pagination; icons optional.

## Variants
My Training search/category/mode/status filter bar; Admin navigator search is a narrower workflow-specific variant.

## Exact visual contract
My Training controls use the compact form values: radius 10px, 9px 12px, 13.5px/600; search padding-left 34px. The container wraps with shrink-safe children and restrained gaps from source.

## States
Focused control, selected option, no matches, filtered count, and pagination reset.

## Responsive behavior
Filters wrap/stack instead of clipping; controls become full-width where the page's small-screen rule requires it.

## Accessibility
Persistent labels or accessible names, native select semantics, useful focus, and live result updates only when correctly announced.

## Preview
Open `preview.html`.

## Implementation
Adapt `search-filters.html`; when criteria change, recompute matches and reset the current page to 1.

## Do not
Do not add a Reset button to My Training (the canonical source does not have one), a Rows selector, or a desktop-only fixed width.
