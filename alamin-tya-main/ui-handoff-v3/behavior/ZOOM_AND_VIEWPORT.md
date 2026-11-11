# Zoom and viewport

**Do not detect browser zoom.** Do not use `devicePixelRatio`, user-agent branching, or browser-specific zoom logic. Browser zoom changes the effective CSS viewport; ordinary responsive CSS must handle it.

Verify the receiving implementation at 50%, 67%, 80%, 90%, 100%, 110%, 125%, 150%, 175%, and 200%:

- At zoom-out, preserve the centered 1880px maximum, header/shell alignment, readable grouping, and intentional component max-widths.
- At zoom-in, the 991.98px/767.98px/575.98px breakpoints activate as the effective viewport narrows. Navigation collapses, grids stack, controls wrap, and local overflow contains wide regions.
- At every level, ensure no overlap, clipped action, unreachable control, uncontrolled card stretching, or whole-page horizontal overflow.

Zoom levels are a review matrix, not CSS breakpoints.
