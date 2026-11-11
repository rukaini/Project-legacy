# Display mappings

| Internal or legacy value | Visible English label |
|---|---|
| `outsider` | Public |
| `guru_new` | New Teacher |
| `Guru Baru` | New Teacher |
| `Guru New` | New Teacher |

The canonical source mapper is `app/Support/ParticipantTypeLabel.php`, copied under `source-exact/`. Comparison is case-insensitive and normalizes whitespace/underscore separators. Preserve raw values in requests, queries, APIs, and persistence when required; never leak them into visible labels.
