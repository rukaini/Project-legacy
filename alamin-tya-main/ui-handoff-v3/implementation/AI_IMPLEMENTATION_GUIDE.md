# AI implementation guide

- Source snapshots are implementation evidence; screenshots are visual ground truth; component README/CSS/HTML are the portable contract.
- Environment docs describe actual dependencies and distinguish runtime fidelity from source build tooling.
- Preserve receiving-system business rules. Never copy routes, authorization, IDs, or database assumptions from the snapshots.
- Never invent missing styles or merge documented variants into one generic component. If a value is marked context-specific, use the nearest approved screenshot and local requirements.
- For one-component work, load only `foundation/`, that component folder, relevant `behavior/` docs, `terminology/`, and its named screenshots/source snapshot.
- Do not expose raw integration keys; apply the display mappings.
