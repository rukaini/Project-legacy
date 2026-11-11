# Forms

## Visual ground truth
`trainer-profile-desktop.png` and `my-training-desktop.png`.

## Canonical source
`resources/views/trainer/profile/partials/styles.blade.php`, `resources/views/trainer/training/my-training.blade.php`, and Admin shared styles.

## Dependencies
None beyond CSS.

## Variants
Profile/full form and compact filter-control variants. Admin and Trainer use locally compatible but not perfectly universal spacing.

## Exact visual contract
Compact filters use radius 10px, 9px 12px padding, 13.5px/600, `#e2e8f0` border, `#bfdbfe` focus border, and `0 0 0 3px rgba(37,99,235,.09)`. Search inputs reserve 34px on the left. Common labels use 11px/850 uppercase and `.06em`. Admin shared focus-visible uses 3px `rgba(37,99,235,.32)` with 2px offset.

## States
Default, focus, invalid with adjacent error, disabled, and optional helper text.

## Responsive behavior
Controls fill available columns, grid children use `min-width:0`, and multi-column forms stack at their local breakpoint.

## Accessibility
Associate labels and descriptions, preserve keyboard focus, expose errors programmatically, and use native disabled/read-only semantics.

## Preview
Open `preview.html`.

## Implementation
Use the appropriate form density from `forms.html`/`forms.css`.

## Do not
Do not use placeholders as labels or invent a global field width.
