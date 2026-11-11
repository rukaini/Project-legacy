# Typography

The signed-in layouts request Inter from Google Fonts at weights 400, 500, 600, and 700 and set `font-family: "Inter", sans-serif`. For closest fidelity, deliver those weights by an approved CDN or self-hosting. Source CSS also uses numeric weights 750, 800, 850, and 900; browsers synthesize these because the source import stops at 700.

| Role | Approved source pattern |
|---|---|
| Body | Inter; page-specific size where defined; normal line-height generally inherited |
| Header navigation | 14px / 600; 13px below 1199.98px |
| Page heading | 26px / 850 / 1.18 / `-.035em`; 21px on small screens in My Training |
| Page subtitle | 13.5px / 600 / 1.6 in My Training; other approved pages vary |
| Section/card heading | Context-specific; common values include 15px / 850 and 17px / 850 |
| Form label | Common compact form pattern 11px / 850 / uppercase / `.06em` |
| Input/select | 13.5px / 600 in My Training filters |
| Table header | Dashboard 11px / 850 / `.05em`; dense Attendance 10px / 900 / `.09em` |
| Button | 12–13.5px and 800–850 by density/variant |
| Badge/pill | Common workspace pill 12px / 750 |
| Metadata/helper | Commonly 11.5–13.5px, context-specific |

There is no single globally tokenized type scale. Use the documented component variant and closest approved screenshot; do not average legitimate differences.
