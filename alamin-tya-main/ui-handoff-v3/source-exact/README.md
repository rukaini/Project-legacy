# Source-exact snapshots

These files are byte-identical snapshots of canonical application source at commit `4c5762a`. They exist for auditability and may contain Blade, Laravel helpers, business selectors, or application logic. Do not use them as portable drop-ins.

Use `components/` for framework-neutral implementation. `implementation/SOURCE_MANIFEST.json` records each original path, both SHA-256 hashes, and the derived component families.
