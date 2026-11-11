# Page Heading

## Visual ground truth
`my-training-desktop.png`, `trainer-profile-desktop.png`, and `Analytics page.png`.

## Canonical source
`resources/views/trainer/training/my-training.blade.php` and representative shared/profile styles.

## Dependencies
Inter.

## Variants
Canonical large page title; compact section headings are context-specific and are not forced into this component.

## Exact visual contract
Title uses 26px, weight 850, line-height 1.18, letter-spacing `-.035em`, main text color, and safe wrapping. The My Training subtitle is 13.5px/600/1.6 and muted; exact subtitle density may vary elsewhere.

## States
Optional subtitle and optional action group.

## Responsive behavior
Heading/actions wrap with `min-width:0`; My Training title becomes 21px on small screens.

## Accessibility
Use one logical `h1`; visual size does not replace semantic hierarchy.

## Preview
Open `preview.html`.

## Implementation
Use `page-heading.html` plus `page-heading.css`.

## Do not
Do not truncate meaningful titles or invent a universal section-heading scale.
