# Frontend dependencies

| Dependency | Exact version | Source | Purpose | Classification | Required for shared fidelity? | Different-stack note |
|---|---:|---|---|---|---|---|
| Inter | weights 400, 500, 600, 700 | Google Fonts in source layouts | Shared typography | Shared visual fidelity | Yes, for closest fidelity | Self-hosting is acceptable; source CSS also uses synthesized 750–900 weights |
| Bootstrap | 5.3.3 | jsDelivr CDN | Grid, responsive navbar/collapse, modal/utilities | Shared/component, replaceable | No exact package requirement | Reimplement supplied structure and behavior if Bootstrap is absent |
| Bootstrap Icons | 1.11.3 | jsDelivr CDN | Interface icons | Shared/component, replaceable | No exact package requirement | Replace with equivalent accessible icons without changing geometry |
| Native browser JavaScript | browser runtime | Local scripts | Toast, navigator, async UI | Required for interactive components | Yes where behavior is used | Framework code may implement the same contract |
| Chart.js | 4.5.1 | npm bundle | Analytics charts | Page-specific | No | Only analytics-equivalent pages need a chart library |
| Vite | 8.1.5 | npm | Asset compilation | Build tooling only | No | Use the receiving stack's pipeline |
| laravel-vite-plugin | 3.1.3 | npm | Laravel/Vite integration | Build tooling only | No | Laravel-specific |
| Tailwind CSS | 4.3.3 | npm | Source build tooling | Build tooling only | No | Portable components do not require Tailwind |
| @tailwindcss/vite | 4.3.3 | npm | Tailwind/Vite integration | Build tooling only | No | Not needed outside that pipeline |
| concurrently | 9.2.4 | npm | Development process runner | Build/development only | No | Not a UI runtime dependency |

Source external URLs are recorded in `dependency-reference.json`. Portable previews intentionally use no CDN, Laravel, npm bundle, or remote resource.
