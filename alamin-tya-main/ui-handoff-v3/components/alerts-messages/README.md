# Alerts and Messages

## Visual ground truth
Use the nearest workflow screenshot; transient validation/error messages may not be visible in the supplied set.

## Canonical source
Admin shared styles, profile form styles, and Attendance results/styles.

## Dependencies
None beyond CSS; toast is a separate action-level pattern.

## Variants
Inline field error, page/section error, and quiet informational helper. Exact page-alert padding is context-specific rather than globally standardized.

## Exact visual contract
Use source semantic danger `#dc2626`/`#fff1f2`, warning `#d97706`/`#fffbeb`, success `#059669`/`#ecfdf5`, and standard borders. Inline messages sit next to the affected field; action outcomes normally use the shared toast.

## States
Information, success, warning, error; only recommend variants actually required by the workflow.

## Responsive behavior
Wrap long text and keep controls inside the message container.

## Accessibility
Use role `alert` for urgent errors and avoid duplicate live announcements. Link errors to fields when relevant.

## Preview
Open `preview.html`.

## Implementation
Use the small semantic examples in `alerts-messages.html`/`.css` and retain local page density.

## Do not
Do not fake success, hide validation in a toast alone, or invent a single global alert size.
