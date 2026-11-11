# Environment

## Source system

- PHP requirement: `^8.3`; approved development environment: PHP 8.5.
- Laravel: `^13.7` (Laravel 13).
- Node detected during extraction: `v24.18.0`.
- npm detected during extraction: `11.16.0`.
- Vite `8.1.5`; Laravel Vite plugin `3.1.3`.
- Blade templates, Bootstrap 5.3.3, Bootstrap Icons 1.11.3, Inter, native browser JavaScript, and page-specific Chart.js 4.5.1.
- Tailwind CSS 4.3.3 is present in build tooling, but the approved signed-in UI is primarily source-authored CSS plus Bootstrap utilities/components.

## Path A — same stack / maximum fidelity

Use PHP meeting `^8.3`, Laravel `^13.7`, the repository's locked npm dependency versions, its Vite inputs, Bootstrap 5.3.3, Bootstrap Icons 1.11.3, and Inter. In the source system Bootstrap and icons are delivered by jsDelivr and Inter by Google Fonts. A production recipient may self-host the same assets and weights instead. Use the supplied portable CSS/HTML rather than assuming framework defaults.

## Path B — different stack

Laravel, PHP, Node, npm, Vite, Tailwind, and concurrently are **not required merely to match this UI**. React, Vue, plain PHP, another server framework, or static HTML can reproduce the contract with the supplied CSS/HTML/JS. Install a tool only when the receiving build requires it. Bootstrap behavior may be reimplemented if final markup, keyboard behavior, focus behavior, breakpoints, and visual values remain equivalent. Chart.js is needed only for equivalent chart pages, not shared chrome.
