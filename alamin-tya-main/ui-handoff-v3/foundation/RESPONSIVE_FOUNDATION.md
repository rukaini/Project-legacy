# Responsive foundation

Responsive behavior follows effective viewport width; the application does not detect browser zoom.

- Header navigation collapses at `991.98px`.
- Admin master/detail normalizes to stacked/full-width behavior at `991.98px`.
- Page shell tightens at `767.98px` and `575.98px`.
- Use `min-width: 0` on flexible/grid children and `overflow-wrap: anywhere` for user-controlled long text.
- Use local `overflow-x: auto` around tables or intentionally wide tabs. Do not make the page itself scroll horizontally.
- Images, canvases, videos, iframes, and QR media remain within their container.
- Actions and filters wrap or stack without becoming unreachable.
- At `prefers-reduced-motion: reduce`, meaningful transition/animation durations become `.01ms`.

See `behavior/RESPONSIVE.md` and `behavior/ZOOM_AND_VIEWPORT.md` for the verification matrix.
