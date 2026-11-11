# Design tokens

`tokens.css` contains values recurring in approved shared source. It intentionally does not pretend every page value is globally tokenized.

- Shell: 1880px centered maximum, responsive page gutters.
- Header: 58px, its own responsive gutter and logo measurements.
- Semantic palette: source-derived surface, text, border, primary, success, warning, and danger values.
- Shape: controls commonly use 9–10px; cards/panels legitimately use 16px and 18px; pills use 999px.
- Motion: `.18s ease`; reduced motion `.01ms`.
- Navigator widths: 360px normal, 320px intermediate Admin variant, 44px compact.

Component-specific measurements remain beside their canonical component. Do not add values merely to make the token set look complete.
