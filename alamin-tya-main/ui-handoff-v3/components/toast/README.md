# Toast

## Visual ground truth
Shared behavior may appear transiently on any signed-in page; no static screenshot is authoritative for its animation.

## Canonical source
`resources/views/components/app-toast-styles.blade.php`, `app-toast-scripts.blade.php`, and `app-toasts.blade.php`.

## Dependencies
Native browser JavaScript only.

## Variants
Success and error.

## Exact visual contract
Region top/right 24px, 10px stack gap; toast min-width 280px, max `min(380px,100vw - 48px)`, padding 13px 16px, 4px semantic left border, 11px radius, white surface, shadow `0 12px 30px rgba(15,23,42,.16)`, text 13.5px/700/1.45. Entry `.25s ease-out` from 30px; leave `.3s ease` to 20px. Display 3500ms; removal follows after 320ms.

## States
Success (`status`) and error (`alert`), entering, visible, leaving. Server toasts deduplicate identical type/message pairs.

## Responsive behavior
At 575.98px region uses 16px on top/left/right and toast fills available width. Reduced motion removes animation/transition.

## Accessibility
Region `aria-live="polite"` and `aria-atomic="false"`; message wraps anywhere.

## Preview
Open `preview.html`; button calls the portable API.

## Implementation
Use all three files; call `showAppToast('success'|'error', message)`.

## Do not
Do not invent warning/info variants or claim timing not present in source.
