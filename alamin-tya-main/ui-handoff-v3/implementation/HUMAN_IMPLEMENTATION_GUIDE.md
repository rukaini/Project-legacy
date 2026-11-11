# Human implementation guide

1. Open `START_HERE.md` and identify only the component families your work needs.
2. Read `environment/ENVIRONMENT.md` and `FRONTEND_DEPENDENCIES.md` before installing anything.
3. Copy `foundation/branding/ai-amin-edu-oasis-logo.png` unchanged.
4. Import `foundation/tokens.css` first.
5. For each component, read its README, adapt the supplied HTML structure, and use its CSS. Add JavaScript only for toast or collapse navigator when that behavior is required.
6. Compare combinations against `screenshots/`; screenshot content is illustrative, not data to copy.
7. Preserve the receiving system's business logic, authorization, validation, routes, data model, and workflows.
8. Complete `IMPLEMENTATION_CHECKLIST.md`.

If the receiving architecture allows CSS imports, `implementation/all-components.css` is an optional one-file entry point.

## Path A — maximum fidelity

Deliver Inter weights 400/500/600/700, preserve the official logo, exact tokens/component values, semantic HTML, native/component JS behavior, documented breakpoints, and focus/reduced-motion behavior. Bootstrap 5.3.3 and Bootstrap Icons 1.11.3 match the source delivery, but the portable examples do not require their CDN.

## Path B — different stack

Keep React, Vue, Laravel, plain PHP, or another existing stack. Reproduce the supplied CSS values and behavior in that stack. Bootstrap or its icons may be replaced if the final geometry, visual state, keyboard behavior, accessibility, and responsive outcome remain equivalent. Node/Vite/Tailwind are not automatically required.
