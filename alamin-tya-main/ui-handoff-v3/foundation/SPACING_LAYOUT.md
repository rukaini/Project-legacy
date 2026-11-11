# Spacing and layout

The canonical signed-in shell is centered with `max-width: 1880px`.

- Desktop padding: `clamp(22px, 2.2vw, 36px)` top, `clamp(16px, 2.4vw, 44px)` horizontal, `clamp(44px, 4vw, 68px)` bottom.
- At `767.98px` and below: `20px 16px 44px`.
- At `575.98px` and below: horizontal gutter becomes `12px`.
- Header inner content uses the same 1880px maximum but its own horizontal gutter: `clamp(16px, 2.25vw, 42px)`.

Shared safeguards: `box-sizing: border-box`, `min-width: 0` for flexible descendants, headings that can wrap, responsive media, and local overflow wrappers for genuinely wide tables/tabs. Retain readable inner max-widths where the source component provides them; zoom-out does not remove the 1880px shell.
