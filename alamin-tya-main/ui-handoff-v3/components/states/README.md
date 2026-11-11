# States

## Visual ground truth
Use relevant screenshots for container styling; transient states may not be captured.

## Canonical source
Admin shared empty-state styles, Attendance results/styles/scripts, My Training empty/pagination logic.

## Dependencies
Native browser JavaScript for dynamic loading/error replacement.

## Variants
Empty, loading, disabled, and error. State copy is workflow-specific.

## Exact visual contract
Admin shared empty state uses minimum height 150px and padding `clamp(30px,4vw,52px) 22px`. My Training zero results hides pagination. Attendance provides explicit loading/error containers. Disabled actions use the owning component's opacity/semantics.

## States
Empty states are truthful; loading preserves layout and blocks duplicate action; error explains recovery; disabled visibly and functionally prevents action.

## Responsive behavior
No desktop-only fixed width. Text wraps and state containers remain inside their parent.

## Accessibility
Announce asynchronous status appropriately, avoid focus theft, use native disabled when possible, and never leave hidden loading controls focusable.

## Preview
Open `preview.html`.

## Implementation
Choose one state block from `states.html`/`states.css`; replace copy with truthful workflow content.

## Do not
Do not render fake/demo business data, `Page 1 of 0`, or an enabled control that cannot act.
