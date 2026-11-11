# Workspace Navigation

## Visual ground truth
`trainer-attendance-desktop.png` and `Material page.png`.

## Canonical source
Trainer `workspace-header.blade.php` and `workspace-header-styles.blade.php`; Simple workspace styles.

## Dependencies
Source icons use Bootstrap Icons; portable structure does not require it.

## Variants
LMS and Simple share the workspace language while exposing workflow-appropriate links. Admin detail tabs are documented separately.

## Exact visual contract
Workspace heading combines a 58px square, 16px-radius, `#bfdbfe`-border icon tile with a 26px/850/1.18 title. Navigation uses the documented workspace tabs: 24px gap, 22px top margin, 2.5px active underline, and local overflow.

## States
Current workspace section, hover/focus, and optional mode/status pills.

## Responsive behavior
Header content wraps; title/actions use `min-width:0`; nav scrolls locally; mobile never loses a destination.

## Accessibility
Use a labelled navigation region and `aria-current="page"`; decorative icon tiles are hidden from assistive technology.

## Preview
Open `preview.html`.

## Implementation
Use `workspace-navigation.html`/`.css` and supply the receiving workflow's valid links.

## Do not
Do not add a separate QR navigation item or expose business routes from the source sample.
